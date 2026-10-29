<?php

declare(strict_types=1);

namespace Modules\Finance\Tests;

use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Finance\Http\Requests\CustomerOutstandingReportRequest;
use Modules\Finance\Services\CustomerOutstandingReportService;
use Tests\TestCase;

final class CustomerOutstandingReportServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_as_of_date_is_valid_without_an_invoice_start_date(): void
    {
        $tenantId = $this->tenant();
        $request = CustomerOutstandingReportRequest::create('/api/v1/finance/customer-outstanding', 'GET', [
            'as_of_date' => '2026-02-28',
        ]);
        $request->attributes->set('current_tenant_id', $tenantId);
        $request->merge(['tenant_id' => $tenantId]);

        $validator = Validator::make($request->all(), $request->rules());

        self::assertTrue($validator->passes(), $validator->errors()->toJson());
    }

    public function test_as_of_date_uses_the_report_timezone_at_the_utc_day_boundary(): void
    {
        $tenantId = $this->tenant();
        CarbonImmutable::setTestNow('2026-10-28 23:34:48 UTC');

        try {
            $localRequest = CustomerOutstandingReportRequest::create('/api/v1/finance/customer-outstanding', 'GET', [
                'as_of_date' => '2026-10-29',
                'timezone' => 'Asia/Colombo',
            ]);
            $localRequest->attributes->set('current_tenant_id', $tenantId);
            $localRequest->merge(['tenant_id' => $tenantId]);

            self::assertSame('2026-10-29', $localRequest->reportToday());
            self::assertTrue(Validator::make($localRequest->all(), $localRequest->rules())->passes());

            $localRequest->merge(['as_of_date' => '2026-10-30']);
            self::assertFalse(Validator::make($localRequest->all(), $localRequest->rules())->passes());

            $localRequest->merge(['as_of_date' => '2026-10-29', 'timezone' => 'UTC']);
            self::assertSame('2026-10-28', $localRequest->reportToday());
            self::assertFalse(Validator::make($localRequest->all(), $localRequest->rules())->passes());

            $localRequest->merge(['timezone' => 'Invalid/Timezone']);
            self::assertFalse(Validator::make($localRequest->all(), $localRequest->rules())->passes());
        } finally {
            CarbonImmutable::setTestNow();
        }
    }

    public function test_report_returns_customer_invoices_as_of_the_selected_date(): void
    {
        $tenantId = $this->tenant();
        $customerId = $this->customer($tenantId);

        $matchingInvoiceId = $this->invoice($tenantId, $customerId, 'INV-MATCH', '2026-02-10', '120.000000');
        DB::table('invoice_sources')->insert([
            'tenant_id' => $tenantId,
            'invoice_id' => $matchingInvoiceId,
            'source_type' => 'vehicle_service_job',
            'source_id' => 25,
            'source_document_number' => 'VSJ-000024',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->invoice($tenantId, $customerId, 'INV-OUTSIDE-DATE', '2026-03-10', '300.000000');

        $request = CustomerOutstandingReportRequest::create('/api/v1/finance/customer-outstanding', 'GET', [
            'as_of_date' => '2026-02-28',
            'page' => 1,
            'per_page' => 25,
        ]);
        $request->attributes->set('current_tenant_id', $tenantId);

        $result = app(CustomerOutstandingReportService::class)->page($request);

        self::assertSame('2026-02-28', $result['as_of_date']);
        self::assertSame(1, $result['summary']['customer_count']);
        self::assertSame(1, $result['summary']['overdue_invoice_count']);
        self::assertCount(1, $result['data']);
        self::assertSame(1, $result['data'][0]['invoice_count']);
        self::assertEquals(120, (float) $result['data'][0]['outstanding_total']);

        $request->merge(['currency_code' => 'LKR']);
        $invoiceResult = app(CustomerOutstandingReportService::class)->invoices($request, $customerId);
        self::assertCount(1, $invoiceResult['data']);
        self::assertSame('INV-MATCH', $invoiceResult['data'][0]['invoice']['number']);
        self::assertSame(18, $invoiceResult['data'][0]['invoice_age_days']);
        self::assertSame('VSJ-000024', $invoiceResult['data'][0]['invoice']['service_job_number']);
        self::assertEquals(120, (float) $invoiceResult['data'][0]['outstanding_amount']);

        $exportRows = app(CustomerOutstandingReportService::class)->exportRows($request);
        $rows = iterator_to_array($exportRows, false);
        self::assertSame(['invoice', 'customer_total', 'location_total', 'grand_total'], array_column($rows, 'row_type'));
        self::assertSame('INV-MATCH', $rows[0]['invoice']);
        self::assertEquals(120, (float) $rows[3]['balance']);
    }

    public function test_historical_balance_applies_payments_credits_and_later_reversals_on_their_dates(): void
    {
        $tenantId = $this->tenant();
        $customerId = $this->customer($tenantId);
        $this->creditProfile($tenantId, $customerId);
        $invoiceId = $this->invoice($tenantId, $customerId, 'INV-HISTORY', '2026-10-01', '100.000000');
        DB::table('invoice_sources')->insert([
            'tenant_id' => $tenantId, 'invoice_id' => $invoiceId, 'source_type' => 'vehicle_service_job',
            'source_id' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $paymentId = (int) DB::table('payments')->insertGetId([
            'tenant_id' => $tenantId, 'payment_number' => 'PAY-HISTORY', 'payment_type' => 'receipt',
            'direction' => 'inbound', 'payment_date' => '2026-10-10', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $allocationId = (int) DB::table('payment_allocations')->insertGetId([
            'tenant_id' => $tenantId, 'payment_id' => $paymentId, 'invoice_id' => $invoiceId,
            'invoice_number_snapshot' => 'INV-HISTORY', 'invoice_total' => '100.000000',
            'invoice_balance_before' => '100.000000', 'allocated_amount' => '40.000000',
            'invoice_balance_after' => '60.000000', 'allocation_date' => '2026-10-10',
            'status' => 'reversed', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $originalJournal = (int) DB::table('finance_journal_entries')->insertGetId([
            'tenant_id' => $tenantId, 'journal_number' => 'J-ORIGINAL', 'journal_date' => '2026-10-10',
            'source_module' => 'payment', 'source_type' => 'payment_allocation', 'source_id' => $allocationId,
            'status' => 'posted', 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('finance_journal_entries')->insert([
            'tenant_id' => $tenantId, 'journal_number' => 'J-REVERSE', 'journal_date' => '2026-10-25',
            'reversal_of_id' => $originalJournal, 'status' => 'posted',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $warehouseId = (int) DB::table('warehouses')->insertGetId([
            'tenant_id' => $tenantId, 'name' => 'Main', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $saleId = (int) DB::table('sales')->insertGetId([
            'tenant_id' => $tenantId, 'customer_id' => $customerId, 'warehouse_id' => $warehouseId,
            'invoice_id' => $invoiceId, 'sale_number' => 'SALE-HISTORY', 'sale_date' => '2026-10-01',
            'status' => 'posted', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $returnId = (int) DB::table('sale_returns')->insertGetId([
            'tenant_id' => $tenantId, 'sale_id' => $saleId, 'invoice_id' => $invoiceId,
            'return_number' => 'RETURN-HISTORY', 'return_date' => '2026-10-20', 'reason' => 'Damaged item',
            'credit_amount' => '10.000000', 'credit_allocated_amount' => '10.000000',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('invoice_credit_allocations')->insert([
            'tenant_id' => $tenantId, 'credit_source_type' => 'sale_return', 'credit_source_id' => $returnId,
            'invoice_id' => $invoiceId, 'allocated_amount' => '10.000000',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('invoice_balances')->where('invoice_id', $invoiceId)->update([
            'paid_amount' => '0.000000', 'credit_allocated_amount' => '10.000000', 'remaining_amount' => '90.000000',
        ]);

        $service = app(CustomerOutstandingReportService::class);
        self::assertEquals(100, (float) $service->page($this->request($tenantId, '2026-10-05'))['data'][0]['outstanding_total']);
        self::assertEquals(60, (float) $service->page($this->request($tenantId, '2026-10-15'))['data'][0]['outstanding_total']);
        self::assertEquals(50, (float) $service->page($this->request($tenantId, '2026-10-22'))['data'][0]['outstanding_total']);
        self::assertEquals(90, (float) $service->page($this->request($tenantId, '2026-10-28'))['data'][0]['outstanding_total']);
    }

    public function test_historical_report_rejects_a_reversal_without_an_effective_date(): void
    {
        $tenantId = $this->tenant();
        $customerId = $this->customer($tenantId);
        $invoiceId = $this->invoice($tenantId, $customerId, 'INV-UNKNOWN-REVERSAL', '2026-10-01', '100.000000');
        $paymentId = (int) DB::table('payments')->insertGetId([
            'tenant_id' => $tenantId, 'payment_number' => 'PAY-UNKNOWN-REVERSAL', 'payment_type' => 'receipt',
            'direction' => 'inbound', 'payment_date' => '2026-10-10', 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('payment_allocations')->insert([
            'tenant_id' => $tenantId, 'payment_id' => $paymentId, 'invoice_id' => $invoiceId,
            'invoice_number_snapshot' => 'INV-UNKNOWN-REVERSAL', 'invoice_total' => '100.000000',
            'invoice_balance_before' => '100.000000', 'allocated_amount' => '40.000000',
            'invoice_balance_after' => '60.000000', 'allocation_date' => '2026-10-10',
            'status' => 'reversed', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $service = app(CustomerOutstandingReportService::class);
        $request = $this->request($tenantId, '2026-10-15');
        try {
            $service->exportRows($request);
            self::fail('CSV export must reject unverifiable history before streaming begins.');
        } catch (ValidationException) {
            // The error must be raised before returning the export iterable.
        }

        $this->expectException(ValidationException::class);
        $service->page($request);
    }

    public function test_selected_location_must_be_accessible(): void
    {
        $tenantId = $this->tenant();
        $request = $this->request($tenantId, '2026-10-15');
        $request->merge(['location_id' => 999]);

        $this->expectException(AuthorizationException::class);
        app(CustomerOutstandingReportService::class)->page($request);
    }

    public function test_cancellation_only_removes_an_invoice_from_its_cancellation_date(): void
    {
        $tenantId = $this->tenant();
        $customerId = $this->customer($tenantId);
        $invoiceId = $this->invoice($tenantId, $customerId, 'INV-CANCELLED', '2026-10-01', '100.000000');
        DB::table('invoices')->where('id', $invoiceId)->update([
            'status' => 'cancelled',
            'cancelled_at' => '2026-10-20 09:00:00',
        ]);

        $service = app(CustomerOutstandingReportService::class);
        self::assertSame(1, $service->page($this->request($tenantId, '2026-10-15'))['summary']['customer_count']);
        self::assertSame(0, $service->page($this->request($tenantId, '2026-10-25'))['summary']['customer_count']);
    }

    private function request(int $tenantId, string $asOfDate): CustomerOutstandingReportRequest
    {
        $request = CustomerOutstandingReportRequest::create('/api/v1/finance/customer-outstanding', 'GET', [
            'as_of_date' => $asOfDate,
        ]);
        $request->attributes->set('current_tenant_id', $tenantId);

        return $request;
    }

    private function tenant(): int
    {
        $suffix = Str::lower(Str::random(8));

        return (int) DB::table('tenants')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'code' => 'TEN-OUT-'.$suffix,
            'name' => 'Outstanding report '.$suffix,
            'slug' => 'outstanding-report-'.$suffix,
            'status' => 'active',
            'status_changed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function customer(int $tenantId): int
    {
        return (int) DB::table('customers')->insertGetId([
            'tenant_id' => $tenantId,
            'customer_number' => 'CUS-OUT-001',
            'code' => 'CUS-OUT-001',
            'name' => 'Outstanding Customer',
            'customer_type' => 'company',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function creditProfile(int $tenantId, int $customerId): void
    {
        DB::table('customer_credit_profiles')->insert([
            'tenant_id' => $tenantId,
            'customer_id' => $customerId,
            'credit_allowed' => true,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function invoice(int $tenantId, int $customerId, string $number, string $date, string $amount): int
    {
        $invoiceId = (int) DB::table('invoices')->insertGetId([
            'tenant_id' => $tenantId,
            'invoice_number' => $number,
            'invoice_type' => 'sales',
            'direction' => 'outbound',
            'party_type' => 'customer',
            'party_id' => $customerId,
            'invoice_date' => $date,
            'due_date' => $date,
            'currency_code_snapshot' => 'LKR',
            'status' => 'posted',
            'grand_total' => $amount,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('invoice_balances')->insert([
            'tenant_id' => $tenantId,
            'invoice_id' => $invoiceId,
            'invoice_total' => $amount,
            'remaining_amount' => $amount,
            'status' => 'unpaid',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $invoiceId;
    }
}
