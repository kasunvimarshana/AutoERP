<?php

declare(strict_types=1);

namespace Modules\Reporting\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Reporting\Services\PaymentMethodWiseSummaryReportService;
use Tests\TestCase;

final class PaymentMethodWiseSummaryReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_credit_sales_stay_on_invoice_date_and_split_receipts_follow_payment_date(): void
    {
        $now = now();
        $tenantId = (int) DB::table('tenants')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'code' => 'TEN-PMW-'.Str::upper(Str::random(6)),
            'name' => 'Payment report tenant',
            'slug' => 'payment-report-'.Str::lower(Str::random(6)),
            'status' => 'active',
            'status_changed_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $customerId = (int) DB::table('customers')->insertGetId([
            'tenant_id' => $tenantId, 'customer_number' => 'CUS-PMW', 'code' => 'CUS-PMW',
            'name' => 'Report customer', 'customer_type' => 'individual',
            'created_at' => $now, 'updated_at' => $now,
        ]);
        $warehouseId = (int) DB::table('warehouses')->insertGetId([
            'tenant_id' => $tenantId, 'name' => 'Report warehouse',
            'created_at' => $now, 'updated_at' => $now,
        ]);
        $vehicleId = (int) DB::table('vehicles')->insertGetId([
            'tenant_id' => $tenantId, 'vehicle_number' => 'VEH-PMW',
            'created_at' => $now, 'updated_at' => $now,
        ]);
        $jobId = (int) DB::table('vehicle_service_jobs')->insertGetId([
            'tenant_id' => $tenantId, 'job_number' => 'JOB-PMW', 'job_date' => '2026-10-01',
            'customer_id' => $customerId, 'vehicle_id' => $vehicleId, 'status' => 'partially_paid',
            'created_at' => $now, 'updated_at' => $now,
        ]);
        $sellingInvoiceId = $this->invoice($tenantId, $customerId, 'INV-SELL-PMW', 'sales', '100.000000');
        $serviceInvoiceId = $this->invoice($tenantId, $customerId, 'INV-SERVICE-PMW', 'service', '80.000000');
        DB::table('sales')->insert([
            'tenant_id' => $tenantId, 'customer_id' => $customerId, 'warehouse_id' => $warehouseId,
            'invoice_id' => $sellingInvoiceId, 'sale_number' => 'SALE-PMW',
            'sale_date' => '2026-10-01', 'due_date' => '2026-10-31', 'status' => 'posted',
            'created_at' => $now, 'updated_at' => $now,
        ]);
        DB::table('vehicle_service_invoice_links')->insert([
            'tenant_id' => $tenantId, 'vehicle_service_job_id' => $jobId,
            'invoice_id' => $serviceInvoiceId, 'source_line_total' => '80.000000',
            'invoice_total' => '80.000000', 'settlement_mode' => 'credit', 'status' => 'active',
            'created_at' => $now, 'updated_at' => $now,
        ]);

        $cashId = $this->method($tenantId, 'CASH', 'cash');
        $cardId = $this->method($tenantId, 'CARD', 'card');
        $paymentId = (int) DB::table('payments')->insertGetId([
            'tenant_id' => $tenantId, 'payment_number' => 'PAY-PMW',
            'payment_type' => 'customer_receipt', 'direction' => 'inbound',
            'document_status' => 'approved', 'posting_status' => 'posted',
            'payment_date' => '2026-10-05', 'total_amount' => '100.000000',
            'allocated_amount' => '100.000000', 'created_at' => $now, 'updated_at' => $now,
        ]);
        $this->line($tenantId, $paymentId, $cashId, 1, 'CASH', 'cash', '70.000000');
        $this->line($tenantId, $paymentId, $cardId, 2, 'CARD', 'card', '30.000000');
        $this->allocation($tenantId, $paymentId, $sellingInvoiceId, '60.000000');
        $this->allocation($tenantId, $paymentId, $serviceInvoiceId, '40.000000');

        $reports = app(PaymentMethodWiseSummaryReportService::class);
        $all = $reports->run($tenantId, null, '2026-10-01', '2026-10-05', PaymentMethodWiseSummaryReportService::SOURCE_ALL);
        self::assertSame('180.000000', $all['days'][0]['credit_sales']);
        self::assertSame('0.000000', $all['days'][0]['receipts']);
        self::assertSame('100.000000', $all['days'][1]['receipts']);
        self::assertSame('70.000000', $all['days'][1]['methods']['CASH']);
        self::assertSame('30.000000', $all['days'][1]['methods']['CARD']);
        self::assertSame('60.000000', $reports->run($tenantId, null, '2026-10-05', '2026-10-05', PaymentMethodWiseSummaryReportService::SOURCE_SELLING)['totals'][0]['receipts']);
        self::assertSame('40.000000', $reports->run($tenantId, null, '2026-10-05', '2026-10-05', PaymentMethodWiseSummaryReportService::SOURCE_SERVICE)['totals'][0]['receipts']);
        self::assertSame('180.000000', $reports->exportRows($all)[2]['credit_sales']);

        DB::table('invoices')->whereIn('id', [$sellingInvoiceId, $serviceInvoiceId])->update(['status' => 'paid', 'balance_due' => '0.000000']);
        self::assertSame('180.000000', $reports->run($tenantId, null, '2026-10-01', '2026-10-05', PaymentMethodWiseSummaryReportService::SOURCE_ALL)['totals'][0]['credit_sales']);
    }

    private function invoice(int $tenantId, int $customerId, string $number, string $type, string $amount): int
    {
        return (int) DB::table('invoices')->insertGetId([
            'tenant_id' => $tenantId, 'invoice_number' => $number, 'invoice_type' => $type,
            'direction' => 'outbound', 'party_type' => 'customer', 'party_id' => $customerId,
            'invoice_date' => '2026-10-01', 'due_date' => '2026-10-31',
            'status' => 'partially_paid', 'grand_total' => $amount,
            'balance_due' => '40.000000',
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function method(int $tenantId, string $code, string $type): int
    {
        return (int) DB::table('payment_methods')->insertGetId([
            'tenant_id' => $tenantId, 'scope_key' => 'tenant:'.$tenantId,
            'code' => $code, 'name' => $code, 'method_type' => $type,
            'direction_allowed' => 'inbound', 'is_active' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function line(int $tenantId, int $paymentId, int $methodId, int $number, string $code, string $type, string $amount): void
    {
        DB::table('payment_lines')->insert([
            'tenant_id' => $tenantId, 'payment_id' => $paymentId,
            'line_number' => $number, 'payment_method_id' => $methodId,
            'payment_method_code_snapshot' => $code,
            'payment_method_name_snapshot' => $code,
            'payment_method_type_snapshot' => $type, 'amount' => $amount,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function allocation(int $tenantId, int $paymentId, int $invoiceId, string $amount): void
    {
        DB::table('payment_allocations')->insert([
            'tenant_id' => $tenantId, 'payment_id' => $paymentId, 'invoice_id' => $invoiceId,
            'invoice_number_snapshot' => 'Invoice '.$invoiceId,
            'invoice_total' => '100.000000', 'invoice_balance_before' => '100.000000',
            'allocated_amount' => $amount, 'invoice_balance_after' => '40.000000',
            'allocation_date' => '2026-10-05', 'status' => 'active',
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }
}
