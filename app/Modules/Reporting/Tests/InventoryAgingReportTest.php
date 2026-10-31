<?php

declare(strict_types=1);

namespace Modules\Reporting\Tests;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Modules\Inventory\DTOs\StockMovementData;
use Modules\Inventory\Enums\InventoryDirection;
use Modules\Inventory\Enums\InventoryMovementType;
use Modules\Inventory\Services\StockMovementService;
use Modules\Inventory\Tests\InventoryTestCase;
use Modules\Item\Enums\CostingMethod;
use Modules\Reporting\Services\ReportService;

class InventoryAgingReportTest extends InventoryTestCase
{
    public function test_current_fifo_stock_has_document_link_cost_and_location_filter(): void
    {
        [$tenantId, $warehouseId, $item] = $this->stockContext();
        $locationId = $this->createWarehouseLocation($tenantId, $warehouseId, 'AGE-A');
        $otherLocationId = $this->createWarehouseLocation($tenantId, $warehouseId, 'AGE-B');

        $this->travelTo(CarbonImmutable::parse('2026-06-10 09:00:00 UTC'));
        $grnId = (int) DB::table('goods_receipt_notes')->insertGetId([
            'tenant_id' => $tenantId,
            'warehouse_id' => $warehouseId,
            'grn_number' => 'GRN-AGE-001',
            'received_date' => '2026-06-10',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->withTenantExecutionContext($tenantId, fn () => app(StockMovementService::class)->record(new StockMovementData(
            tenantId: $tenantId,
            movementDate: '2026-06-10',
            movementType: InventoryMovementType::Receipt,
            direction: InventoryDirection::In,
            itemId: (int) $item->id,
            warehouseId: $warehouseId,
            warehouseLocationId: $locationId,
            quantity: '5.000000',
            unitCost: '10.000000',
            sourceType: 'goods_receipt_note',
            sourceId: $grnId,
        )));
        $this->receipt($tenantId, $warehouseId, $item, '2.000000', '10.000000', warehouseLocationId: $otherLocationId);

        $this->travelTo(CarbonImmutable::parse('2026-06-13 09:00:00 UTC'));
        $input = [
            'date_from' => '2026-06-10',
            'date_to' => '2026-06-10',
            'filters' => ['warehouse_id' => $warehouseId, 'warehouse_location_id' => $locationId],
        ];
        $report = $this->withTenantExecutionContext($tenantId, fn (): array => app(ReportService::class)->run('inventory.aging', $tenantId, null, $input, 25));

        $this->assertSame(1, $report['meta']['total']);
        $row = $report['data'][0];
        $this->assertSame('GRN-AGE-001', $row['document']);
        $this->assertSame('/purchase/goods-receipts/'.$grnId, $row['document_url']);
        $this->assertSame('2026-06-10 09:00:00', $row['transaction_at']);
        $this->assertSame('10.000000', $row['unit_cost']);
        $this->assertSame('5.000000', $row['original_quantity']);
        $this->assertSame('5.000000', $row['remaining_quantity']);
        $this->assertSame(3, $row['age_days']);
        $this->assertSame('Location AGE-A', $row['location']);
        $this->assertArrayNotHasKey('batch', $row);
        $this->assertArrayNotHasKey('remaining_value', $row);

        $valuation = $this->withTenantExecutionContext($tenantId, fn (): array => app(ReportService::class)->run('inventory.valuation', $tenantId, null, [
            'search' => (string) $item->code,
            'date_from' => '2026-06-10',
            'date_to' => '2026-06-10',
        ], 25));
        $this->assertSame($row['age_days'], $valuation['data'][0]['age_days']);

        $document = $this->withTenantExecutionContext($tenantId, fn () => app(ReportService::class)->document('inventory.aging', $tenantId, null, $input));
        $this->assertSame('GRN-AGE-001', $document->rows[0]['document']);
        $this->assertSame(['Rows'], array_column($document->summary, 'label'));
        $this->assertSame([], array_filter($document->filters, fn (array $filter): bool => str_contains($filter['label'], 'Id')));

        $csv = $this->withTenantExecutionContext($tenantId, fn () => app(ReportService::class)->export('inventory.aging', 'csv', $tenantId, null, $input));
        $this->assertSame(200, $csv->getStatusCode());
        $this->assertStringContainsString('GRN-AGE-001', (string) $csv->getContent());

        $outside = $this->withTenantExecutionContext($tenantId, fn (): array => app(ReportService::class)->run('inventory.aging', $tenantId, null, [
            ...$input,
            'date_from' => '2026-06-11',
            'date_to' => '2026-06-11',
        ], 25));
        $this->assertSame(0, $outside['meta']['total']);
    }

    public function test_weighted_average_receipts_remain_one_pool_without_a_false_document_link(): void
    {
        [$tenantId, $warehouseId] = $this->stockContext();
        $item = $this->createItem($tenantId, 'AGE-POOL', costing: CostingMethod::WeightedAverage);

        $this->travelTo(CarbonImmutable::parse('2026-06-10 09:00:00 UTC'));
        $this->receipt($tenantId, $warehouseId, $item, '2.000000', '10.000000');
        $this->travelTo(CarbonImmutable::parse('2026-06-11 09:00:00 UTC'));
        $this->receipt($tenantId, $warehouseId, $item, '3.000000', '20.000000');

        $report = $this->withTenantExecutionContext($tenantId, fn (): array => app(ReportService::class)->run('inventory.aging', $tenantId, null, [
            'search' => 'AGE-POOL',
        ], 25));

        $this->assertSame(1, $report['meta']['total']);
        $row = $report['data'][0];
        $this->assertSame('Weighted-average pool', $row['document']);
        $this->assertNull($row['document_url']);
        $this->assertSame('5.000000', $row['original_quantity']);
        $this->assertSame('5.000000', $row['remaining_quantity']);
        $this->assertSame('16.000000', $row['unit_cost']);
    }
}
