<?php

declare(strict_types=1);

namespace Modules\Reporting\Tests;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Inventory\Tests\InventoryTestCase;
use Modules\Reporting\Services\ReportExport;
use Modules\Reporting\Services\StockBalanceReportService;
use Spatie\LaravelPdf\Facades\Pdf;

final class StockBalanceReportTest extends InventoryTestCase
{
    public function test_grouped_stock_quantities_and_batch_details_stay_within_the_selected_location_and_tenant(): void
    {
        [$tenantId, $warehouseId, $item] = $this->stockContext();
        $locationId = $this->createWarehouseLocation($tenantId, $warehouseId, 'REPORT-A');
        $otherLocationId = $this->createWarehouseLocation($tenantId, $warehouseId, 'REPORT-B');
        $firstBatchId = $this->batch($tenantId, (int) $item->id, 'REPORT-1');
        $secondBatchId = $this->batch($tenantId, (int) $item->id, 'REPORT-2');
        $this->balance($tenantId, (int) $item->id, $warehouseId, $locationId, $firstBatchId, '6', '4', '0', '1', '1');
        $this->balance($tenantId, (int) $item->id, $warehouseId, $locationId, $secondBatchId, '3', '1', '1', '1', '0');
        $this->balance($tenantId, (int) $item->id, $warehouseId, $otherLocationId, null, '2', '2', '0', '0', '0');

        $service = app(StockBalanceReportService::class);
        $params = ['tenant_id' => $tenantId, 'organization_unit_id' => null, 'warehouse_id' => $warehouseId, 'location_id' => $locationId];
        $report = $service->run($params);

        self::assertSame(1, $report['meta']['total']);
        $row = $report['data'][0];
        self::assertSame((int) $item->id, $row['item_id']);
        self::assertEquals(9, $row['quantity_on_hand']);
        self::assertEquals(5, $row['quantity_available']);
        self::assertEquals(2, $row['quantity_damaged']);
        self::assertEquals(1, $row['quantity_expired']);
        self::assertCount(2, $service->details([...$params, 'item_id' => (int) $item->id]));
        self::assertSame(2, $service->run(['tenant_id' => $tenantId, 'organization_unit_id' => null])['meta']['total']);
        $zeroItem = $this->createItem($tenantId, 'REPORT-ZERO');
        $this->balance($tenantId, (int) $zeroItem->id, $warehouseId, $locationId, null, '0', '0', '0', '0', '0');
        self::assertSame(3, $service->run(['tenant_id' => $tenantId, 'organization_unit_id' => null])['meta']['total']);
        self::assertSame(2, $service->run(['tenant_id' => $tenantId, 'organization_unit_id' => null, 'include_zero' => false])['meta']['total']);

        Pdf::fake();
        $response = app(ReportExport::class)->response('pdf', $service->exportData($params));
        self::assertSame(200, $response->getStatusCode());
        Pdf::assertRespondedWithPdf(fn ($pdf): bool => $pdf->viewName === 'reports.inventory.report'
            && $pdf->orientation === 'landscape'
            && $pdf->downloadName === StockBalanceReportService::KEY.'.pdf');

        $otherTenant = $this->createTenant();
        $otherWarehouse = $this->createWarehouse($otherTenant, 'REPORT-OTHER');
        $otherItem = $this->createItem($otherTenant, 'REPORT-OTHER');
        $this->balance($otherTenant, (int) $otherItem->id, $otherWarehouse, null, null, '7', '7', '0', '0', '0');
        self::assertSame(3, $service->run(['tenant_id' => $tenantId, 'organization_unit_id' => null])['meta']['total']);
    }

    private function batch(int $tenantId, int $itemId, string $number): int
    {
        return (int) DB::table('inventory_batches')->insertGetId([
            'tenant_id' => $tenantId, 'item_id' => $itemId, 'batch_number' => $number,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function balance(int $tenantId, int $itemId, int $warehouseId, ?int $locationId, ?int $batchId, string $onHand, string $available, string $reserved, string $damaged, string $expired): void
    {
        DB::table('inventory_stock_balances')->insert([
            'tenant_id' => $tenantId, 'item_id' => $itemId, 'warehouse_id' => $warehouseId,
            'warehouse_location_id' => $locationId, 'batch_id' => $batchId,
            'dimension_key' => hash('sha256', Str::uuid()->toString()),
            'quantity_on_hand' => $onHand, 'quantity_available' => $available, 'quantity_reserved' => $reserved,
            'quantity_damaged' => $damaged, 'quantity_expired' => $expired,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }
}
