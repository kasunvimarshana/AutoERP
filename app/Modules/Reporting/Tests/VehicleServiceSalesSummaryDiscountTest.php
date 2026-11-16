<?php

declare(strict_types=1);

namespace Modules\Reporting\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Reporting\Services\ServiceSalesDashboardService;
use Modules\Reporting\Services\VehicleServiceSalesSummaryReportService;
use Tests\TestCase;

final class VehicleServiceSalesSummaryDiscountTest extends TestCase
{
    use RefreshDatabase;

    public function test_stock_and_combo_discounts_and_job_drilldowns_follow_invoice_date(): void
    {
        $now = now();
        $tenantId = (int) DB::table('tenants')->insertGetId([
            'uuid' => (string) Str::uuid(), 'code' => 'TEN-DISCOUNT', 'name' => 'Discount report tenant',
            'slug' => 'discount-report-'.Str::lower(Str::random(6)), 'status' => 'active',
            'status_changed_at' => $now, 'created_at' => $now, 'updated_at' => $now,
        ]);
        $customerId = (int) DB::table('customers')->insertGetId([
            'tenant_id' => $tenantId, 'customer_number' => 'CUS-DISCOUNT', 'code' => 'CUS-DISCOUNT',
            'name' => 'Report customer', 'customer_type' => 'individual', 'created_at' => $now, 'updated_at' => $now,
        ]);
        $vehicleId = (int) DB::table('vehicles')->insertGetId([
            'tenant_id' => $tenantId, 'vehicle_number' => 'VEH-DISCOUNT', 'created_at' => $now, 'updated_at' => $now,
        ]);
        $stockId = $this->item($tenantId, 'STOCK', 'stock', true);
        DB::table('items')->where('id', $stockId)->update(['reorder_level' => '5.000000']);
        $comboId = $this->item($tenantId, 'COMBO', 'combo', false);
        $componentId = $this->item($tenantId, 'COMPONENT', 'service', false);
        $jobId = (int) DB::table('vehicle_service_jobs')->insertGetId([
            'tenant_id' => $tenantId, 'job_number' => 'VSJ-DISCOUNT', 'job_date' => '2026-11-03',
            'type' => 'body_wash',
            'customer_id' => $customerId, 'vehicle_id' => $vehicleId, 'status' => 'invoiced',
            'grand_total' => '423.000000', 'created_at' => $now, 'updated_at' => $now,
        ]);
        $stockLineId = $this->jobLine($tenantId, $jobId, $stockId, 'inventory_item', 1, '2.000000', '100.000000', '20.000000', '190.000000');
        $comboLineId = $this->jobLine($tenantId, $jobId, $comboId, 'combo_parent', 2, '1.000000', '300.000000', '0.000000', '280.000000');
        DB::table('vehicle_service_job_lines')->insert([
            'tenant_id' => $tenantId, 'vehicle_service_job_id' => $jobId, 'parent_line_id' => $comboLineId,
            'line_number' => 3, 'line_source_type' => 'combo_child', 'item_id' => $componentId,
            'description' => 'Component', 'quantity' => '2.000000', 'unit_cost' => '5.000000',
            'unit_price' => '0.000000', 'is_billable' => false, 'status' => 'pending',
            'created_at' => $now, 'updated_at' => $now,
        ]);
        $invoiceId = (int) DB::table('invoices')->insertGetId([
            'tenant_id' => $tenantId, 'invoice_number' => 'SERVICE-DISCOUNT', 'invoice_type' => 'service',
            'direction' => 'outbound', 'party_type' => 'customer', 'party_id' => $customerId,
            'invoice_date' => '2026-11-07', 'status' => 'posted', 'grand_total' => '423.000000',
            'created_at' => $now, 'updated_at' => $now,
        ]);
        DB::table('vehicle_service_invoice_links')->insert([
            'tenant_id' => $tenantId, 'vehicle_service_job_id' => $jobId, 'invoice_id' => $invoiceId,
            'source_line_total' => '470.000000', 'allocated_adjustment_total' => '47.000000',
            'invoice_total' => '423.000000', 'status' => 'active', 'created_at' => $now, 'updated_at' => $now,
        ]);
        $this->invoiceLine($tenantId, $invoiceId, $jobId, $stockLineId, 1, $stockId, '2.000000', '100.000000', '10.000000', '190.000000');
        $this->invoiceLine($tenantId, $invoiceId, $jobId, $comboLineId, 2, $comboId, '1.000000', '300.000000', '20.000000', '280.000000');

        $report = app(VehicleServiceSalesSummaryReportService::class)->run([
            'tenant_id' => $tenantId, 'date_from' => '2026-11-07', 'date_to' => '2026-11-07',
        ]);
        $stock = $report['summary']['stock']['items'][0];
        $combo = $report['summary']['combo']['items'][0];

        self::assertSame('2.000000', $stock['quantity']);
        self::assertSame('29.000000', $stock['discount_amount']);
        self::assertSame('171.000000', $stock['sales_amount']);
        self::assertSame([['id' => $jobId, 'number' => 'VSJ-DISCOUNT', 'quantity' => '2.000000', 'discount_amount' => '29.000000', 'sales_amount' => '171.000000']], $stock['jobs']);
        self::assertSame('1.000000', $combo['quantity']);
        self::assertSame('48.000000', $combo['discount_amount']);
        self::assertSame('252.000000', $combo['sales_amount']);
        self::assertSame([['id' => $jobId, 'number' => 'VSJ-DISCOUNT', 'quantity' => '1.000000', 'discount_amount' => '48.000000', 'sales_amount' => '252.000000']], $combo['jobs']);
        self::assertSame('131.000000', $stock['profit']);
        self::assertSame('242.000000', $combo['profit']);
        self::assertSame('77.000000', $report['summary']['discount_amount']);
        self::assertSame([[
            'type' => 'body_wash', 'label' => 'Body Wash', 'job_count' => 1, 'revenue' => '423.000000', 'profit' => '373.000000',
        ]], $report['summary']['by_job_type']);
        self::assertSame([[
            'week_start' => '2026-11-02', 'job_count' => 1, 'revenue' => '423.000000',
        ]], $report['by_week']);
        $dashboard = app(ServiceSalesDashboardService::class)->run($tenantId, null, '2026-11-07', '2026-11-14');
        self::assertSame('0', $dashboard['by_week'][1]['revenue']);
        self::assertSame(0, $dashboard['by_week'][1]['job_count']);
        self::assertSame('STOCK', $dashboard['top_stock_items'][0]['item']['name']);
        self::assertSame('2.000000', $dashboard['top_stock_items'][0]['quantity_sold']);
        self::assertSame('0', $dashboard['top_stock_items'][0]['quantity_available']);
        self::assertTrue($dashboard['top_stock_items'][0]['needs_reorder']);
        self::assertSame([], app(VehicleServiceSalesSummaryReportService::class)->run([
            'tenant_id' => $tenantId, 'date_from' => '2026-11-03', 'date_to' => '2026-11-03',
        ])['summary']['stock']['items']);
    }

    private function item(int $tenantId, string $code, string $type, bool $stockable): int
    {
        return (int) DB::table('items')->insertGetId([
            'tenant_id' => $tenantId, 'code' => $code, 'name' => $code, 'item_type' => $type,
            'is_stockable' => $stockable, 'is_combo' => $type === 'combo', 'is_active' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function jobLine(int $tenantId, int $jobId, int $itemId, string $type, int $number, string $quantity, string $price, string $cost, string $total): int
    {
        return (int) DB::table('vehicle_service_job_lines')->insertGetId([
            'tenant_id' => $tenantId, 'vehicle_service_job_id' => $jobId, 'line_number' => $number,
            'line_source_type' => $type, 'item_id' => $itemId, 'description' => $type,
            'quantity' => $quantity, 'unit_price' => $price, 'unit_cost' => $cost,
            'line_total' => $total, 'is_billable' => true, 'status' => 'pending',
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function invoiceLine(int $tenantId, int $invoiceId, int $jobId, int $jobLineId, int $number, int $itemId, string $quantity, string $price, string $discount, string $total): void
    {
        $invoiceLineId = (int) DB::table('invoice_lines')->insertGetId([
            'tenant_id' => $tenantId, 'invoice_id' => $invoiceId, 'line_number' => $number,
            'item_id' => $itemId, 'description' => 'Invoiced item', 'quantity' => $quantity,
            'unit_price' => $price, 'discount_amount' => $discount, 'line_total' => $total,
            'source_line_type' => 'vehicle_service_job_line', 'source_line_id' => $jobLineId,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('invoice_source_lines')->insert([
            'tenant_id' => $tenantId, 'invoice_id' => $invoiceId, 'invoice_line_id' => $invoiceLineId,
            'source_type' => 'vehicle_service_job', 'source_id' => $jobId,
            'source_line_type' => 'vehicle_service_job_line', 'source_line_id' => $jobLineId,
            'source_quantity' => $quantity, 'invoiced_quantity' => $quantity,
            'source_unit_price' => $price, 'source_line_total' => $total, 'invoiced_line_total' => $total,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }
}
