<?php

declare(strict_types=1);

namespace Modules\Reporting\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Invoice\Enums\InvoicePartyType;
use Modules\Reporting\Http\Requests\CustomerOutstandingReportRequest;
use Modules\Reporting\Services\CustomerOutstandingReportService;
use Tests\TestCase;

final class CustomerOutstandingReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_identity_aging_and_currency_groups_survive_pagination_and_export(): void
    {
        [$tenantId, $customerId] = $this->customer();
        $this->invoice($tenantId, $customerId, 'LKR', '125.000000', '2026-06-15');
        $this->invoice($tenantId, $customerId, 'USD', '20.000000', '2026-07-01');
        [$otherTenant, $otherCustomer] = $this->customer();
        $this->invoice($otherTenant, $otherCustomer, 'LKR', '999.000000', '2026-06-15');

        $request = $this->request($tenantId);
        $service = app(CustomerOutstandingReportService::class);
        $result = $service->page($request);

        $this->assertSame(2, $result['meta']['total']);
        $this->assertSame(1, $result['summary']['customer_count']);
        $this->assertSame([$customerId, $customerId], $result['data']->pluck('customer.id')->all());
        $this->assertSame(['Display Customer', 'Display Customer'], $result['data']->pluck('customer.name')->all());
        $rows = $result['data']->keyBy('currency.code');
        $this->assertEquals(125, $rows['LKR']['days_1_30']);
        $this->assertEquals(20, $rows['USD']['current']);
        $this->assertEquals(125, $rows['LKR']['outstanding_total']);
        $this->assertEquals(20, $rows['USD']['outstanding_total']);

        $exports = collect($service->exportRows($request));
        $this->assertCount(2, $exports);
        $this->assertSame(['Display Customer'], $exports->pluck('customer_name')->unique()->values()->all());
    }

    public function test_invoice_drilldown_normalizes_currency_and_preserves_customer_identity(): void
    {
        [$tenantId, $customerId] = $this->customer();
        $invoiceId = $this->invoice($tenantId, $customerId, 'USD', '20.000000', '2026-06-15');
        $result = app(CustomerOutstandingReportService::class)->invoices(
            $this->request($tenantId, ['currency_code' => 'usd']),
            $customerId,
        );

        $this->assertSame('USD', $result['currency']['code']);
        $this->assertSame($customerId, $result['customer']['id']);
        $this->assertSame([$invoiceId], $result['data']->pluck('invoice.id')->all());
        $this->assertSame(15, $result['data'][0]['days_overdue']);
    }

    public function test_drilldown_does_not_disclose_a_customer_outside_the_report_scope(): void
    {
        [$tenantId, $customerId] = $this->customer();
        [$otherTenant, $otherCustomer] = $this->customer();
        $this->invoice($otherTenant, $otherCustomer, 'USD', '50.000000', '2026-06-15');
        $service = app(CustomerOutstandingReportService::class);
        $request = $this->request($tenantId, ['currency_code' => 'USD']);

        foreach ([$customerId, $otherCustomer] as $hiddenCustomer) {
            $result = $service->invoices($request, $hiddenCustomer);
            $this->assertNull($result['customer']);
            $this->assertCount(0, $result['data']);
        }
    }

    private function request(int $tenantId, array $filters = []): CustomerOutstandingReportRequest
    {
        $request = CustomerOutstandingReportRequest::create('/', 'GET', array_merge([
            'date_to' => '2026-06-30', 'per_page' => 25,
        ], $filters));
        $request->attributes->set('current_tenant_id', $tenantId);

        return $request;
    }

    private function customer(): array
    {
        $suffix = Str::lower(Str::random(8));
        $tenantId = (int) DB::table('tenants')->insertGetId([
            'uuid' => (string) Str::uuid(), 'code' => 'OUT-'.$suffix,
            'name' => 'Outstanding Tenant', 'slug' => 'outstanding-'.$suffix,
            'status' => 'active', 'status_changed_at' => now(),
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $customerId = (int) DB::table('customers')->insertGetId([
            'tenant_id' => $tenantId, 'customer_number' => 'CUS-'.$suffix,
            'code' => 'CUS-'.$suffix, 'name' => 'Registered Customer',
            'display_name' => 'Display Customer', 'customer_type' => 'company', 'status' => 'active',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        DB::table('customer_credit_profiles')->insert([
            'tenant_id' => $tenantId, 'customer_id' => $customerId,
            'credit_allowed' => true, 'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);

        return [$tenantId, $customerId];
    }

    private function invoice(int $tenantId, int $customerId, string $currency, string $remaining, string $due): int
    {
        $invoiceId = (int) DB::table('invoices')->insertGetId([
            'tenant_id' => $tenantId, 'invoice_number' => 'OUT-'.Str::random(8),
            'invoice_type' => 'sales', 'direction' => 'outbound', 'invoice_date' => '2026-06-01',
            'due_date' => $due, 'status' => 'posted', 'grand_total' => $remaining,
            'balance_due' => $remaining, 'currency_code_snapshot' => $currency,
            'party_type' => InvoicePartyType::Customer->value, 'party_id' => $customerId,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('invoice_balances')->insert([
            'tenant_id' => $tenantId, 'invoice_id' => $invoiceId, 'invoice_total' => $remaining,
            'paid_amount' => '0.000000', 'credit_allocated_amount' => '0.000000',
            'debit_allocated_amount' => '0.000000', 'refunded_amount' => '0.000000',
            'remaining_amount' => $remaining, 'status' => 'unpaid',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        DB::table('invoice_sources')->insert([
            'tenant_id' => $tenantId, 'invoice_id' => $invoiceId,
            'source_type' => 'vehicle_service_job', 'source_id' => $invoiceId,
            'source_document_number' => 'VSJ-'.$invoiceId, 'created_at' => now(), 'updated_at' => now(),
        ]);

        return $invoiceId;
    }
}
