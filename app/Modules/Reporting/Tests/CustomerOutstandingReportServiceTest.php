<?php

declare(strict_types=1);

namespace Modules\Reporting\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Modules\Reporting\Http\Requests\CustomerOutstandingReportRequest;
use Modules\Reporting\Services\CustomerOutstandingReportService;
use Tests\TestCase;

final class CustomerOutstandingReportServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_date_to_is_valid_without_an_optional_start_date(): void
    {
        $tenantId = $this->tenant();
        $request = CustomerOutstandingReportRequest::create('/api/v1/reports/customer-outstanding', 'GET', [
            'date_to' => '2026-02-28',
        ]);
        $request->attributes->set('current_tenant_id', $tenantId);
        $request->merge(['tenant_id' => $tenantId]);

        $validator = Validator::make($request->all(), $request->rules());

        self::assertTrue($validator->passes(), $validator->errors()->toJson());
    }

    public function test_report_returns_credit_customer_invoices_matching_invoice_date_range(): void
    {
        $tenantId = $this->tenant();
        $customerId = $this->customer($tenantId);
        $this->creditProfile($tenantId, $customerId);

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

        $request = CustomerOutstandingReportRequest::create('/api/v1/reports/customer-outstanding', 'GET', [
            'date_from' => '2026-02-01',
            'date_to' => '2026-02-28',
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
        self::assertSame('VSJ-000024', $invoiceResult['data'][0]['invoice']['service_job_number']);
        self::assertSame('historical_inferred', $invoiceResult['data'][0]['invoice']['credit_basis']);
        self::assertEquals(120, (float) $invoiceResult['data'][0]['outstanding_amount']);

        $exportRows = app(CustomerOutstandingReportService::class)->exportRows($request);
        self::assertCount(1, iterator_to_array($exportRows));
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
