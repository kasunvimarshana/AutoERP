<?php

declare(strict_types=1);

namespace Modules\Inventory\Tests;

use InvalidArgumentException;
use Modules\Inventory\DTOs\AllocationData;
use Modules\Inventory\DTOs\StockAdjustmentData;
use Modules\Inventory\DTOs\StockAdjustmentLineData;
use Modules\Inventory\DTOs\StockBalanceData;
use Modules\Inventory\DTOs\StockMovementData;
use Modules\Inventory\Enums\AdjustmentType;
use Modules\Inventory\Enums\InventoryDirection;
use Modules\Inventory\Enums\InventoryMovementType;
use Modules\Inventory\Models\InventoryStockBalance;
use Modules\Inventory\Services\SerialTrackingService;
use Modules\Inventory\Services\StockAdjustmentService;
use Modules\Inventory\Services\StockAllocationService;
use Modules\Inventory\Services\StockAvailabilityService;
use Modules\Inventory\Services\StockBalanceService;
use Modules\Inventory\Services\StockMovementService;
use Modules\Item\Enums\CostingMethod;
use Modules\Item\Enums\ItemType;
use Modules\Item\Enums\TrackingType;
use Modules\Reporting\Services\ReportDefinitionRegistry;

final class InventoryMovementTest extends InventoryTestCase
{
    public function test_stock_receipt_increases_balance(): void
    {
        [$tenantId, $warehouseId, $item] = $this->stockContext();

        app(StockMovementService::class)->record(new StockMovementData(
            tenantId: $tenantId,
            movementDate: '2026-06-06',
            movementType: InventoryMovementType::Receipt,
            direction: InventoryDirection::In,
            itemId: (int) $item->getKey(),
            warehouseId: $warehouseId,
            quantity: '10.000000',
            unitCost: '5.000000',
        ));

        $availability = app(StockAvailabilityService::class)->availability(new StockBalanceData(
            tenantId: $tenantId,
            itemId: (int) $item->getKey(),
            warehouseId: $warehouseId,
        ));

        $this->assertSame('10.000000', $availability->quantityOnHand);
        $this->assertSame('10.000000', $availability->quantityAvailable);
        $this->assertDatabaseHas('inventory_valuation_layers', [
            'item_id' => $item->getKey(),
            'remaining_quantity' => '10',
        ]);
    }

    public function test_stock_issue_decreases_balance_and_cannot_exceed_available(): void
    {
        [$tenantId, $warehouseId, $item] = $this->stockContext();
        $this->receipt($tenantId, $warehouseId, $item, '10.000000', '5.000000');

        app(StockMovementService::class)->record(new StockMovementData(
            tenantId: $tenantId,
            movementDate: '2026-06-06',
            movementType: InventoryMovementType::Issue,
            direction: InventoryDirection::Out,
            itemId: (int) $item->getKey(),
            warehouseId: $warehouseId,
            quantity: '4.000000',
        ));

        $availability = app(StockAvailabilityService::class)->availability(new StockBalanceData($tenantId, (int) $item->getKey(), $warehouseId));
        $this->assertSame('6.000000', $availability->quantityOnHand);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Inventory issue quantity cannot exceed available stock.');

        app(StockMovementService::class)->record(new StockMovementData(
            tenantId: $tenantId,
            movementDate: '2026-06-06',
            movementType: InventoryMovementType::Issue,
            direction: InventoryDirection::Out,
            itemId: (int) $item->getKey(),
            warehouseId: $warehouseId,
            quantity: '7.000000',
        ));
    }

    public function test_batch_and_serial_tracking_rules_are_enforced(): void
    {
        $tenantId = $this->createTenant();
        $warehouseId = $this->createWarehouse($tenantId, 'WH-BATCH');
        $batchItem = $this->createItem($tenantId, 'BATCH-ITEM', TrackingType::Batch);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Batch or lot tracked items require a batch reference.');

        $this->receipt($tenantId, $warehouseId, $batchItem, '1.000000', '1.000000');
    }

    public function test_serial_required_quantity_one_and_duplicate_prevention(): void
    {
        $tenantId = $this->createTenant();
        $warehouseId = $this->createWarehouse($tenantId, 'WH-SERIAL');
        $item = $this->createItem($tenantId, 'SERIAL-ITEM', TrackingType::Serial);
        $serial = app(SerialTrackingService::class)->create($tenantId, (int) $item->getKey(), 'SN-001');

        try {
            $this->receipt($tenantId, $warehouseId, $item, '2.000000', '1.000000', serialId: (int) $serial->getKey());
            $this->fail('Expected serial quantity validation to fail.');
        } catch (InvalidArgumentException $exception) {
            $this->assertSame('Serial tracked inventory movement quantity must be 1.', $exception->getMessage());
        }

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Inventory serial number already exists for this tenant.');
        app(SerialTrackingService::class)->create($tenantId, (int) $item->getKey(), 'SN-001');
    }

    public function test_two_issue_and_allocation_callers_cannot_overdraw_stock(): void
    {
        [$tenantId, $warehouseId, $item] = $this->stockContext();
        $this->receipt($tenantId, $warehouseId, $item, '5.000000', '2.000000');
        $movements = app(StockMovementService::class);
        $first = $movements->create(new StockMovementData(
            $tenantId,
            '2026-06-06',
            InventoryMovementType::Issue,
            InventoryDirection::Out,
            (int) $item->getKey(),
            $warehouseId,
            '4.000000',
        ));
        $second = $movements->create(new StockMovementData(
            $tenantId,
            '2026-06-06',
            InventoryMovementType::Issue,
            InventoryDirection::Out,
            (int) $item->getKey(),
            $warehouseId,
            '4.000000',
        ));
        $movements->post($first);
        try {
            $movements->post($second);
            $this->fail('Expected the second issue caller to be rejected.');
        } catch (InvalidArgumentException $exception) {
            $this->assertSame('Inventory issue quantity cannot exceed available stock.', $exception->getMessage());
        }

        $balance = InventoryStockBalance::query()->where('item_id', $item->getKey())->firstOrFail();
        $this->assertSame('1.000000', (string) $balance->quantity_on_hand);
        $this->assertSame('1.000000', (string) $balance->quantity_available);

        $other = $this->createItem($tenantId, 'ALLOC-RACE');
        $this->receipt($tenantId, $warehouseId, $other, '5.000000', '2.000000');
        $allocations = app(StockAllocationService::class);
        $allocations->allocate(new AllocationData($tenantId, '2026-06-06', (int) $other->getKey(), $warehouseId, '4.000000'));
        try {
            $allocations->allocate(new AllocationData($tenantId, '2026-06-06', (int) $other->getKey(), $warehouseId, '4.000000'));
            $this->fail('Expected the second allocation caller to be rejected.');
        } catch (InvalidArgumentException $exception) {
            $this->assertSame('Inventory allocation cannot exceed available stock.', $exception->getMessage());
        }
        $otherBalance = InventoryStockBalance::query()->where('item_id', $other->getKey())->firstOrFail();
        $this->assertSame('4.000000', (string) $otherBalance->quantity_allocated);
        $this->assertSame('1.000000', (string) $otherBalance->quantity_available);
    }

    public function test_service_item_and_scope_mismatch_do_not_affect_stock(): void
    {
        $tenantId = $this->createTenant();
        $otherTenantId = $this->createTenant('OTHER');
        $warehouseId = $this->createWarehouse($tenantId, 'WH-SCOPE');
        $otherWarehouseId = $this->createWarehouse($otherTenantId, 'WH-OTHER');
        $serviceItem = $this->createItem($tenantId, 'SERVICE-ITEM', TrackingType::None, CostingMethod::None, ItemType::Service, false);

        try {
            $this->receipt($tenantId, $warehouseId, $serviceItem, '1.000000', '1.000000');
            $this->fail('Expected service item inventory movement to fail.');
        } catch (InvalidArgumentException $exception) {
            $this->assertSame('Only stockable items can affect inventory balances.', $exception->getMessage());
        }

        $stockItem = $this->createItem($tenantId, 'SCOPE-ITEM');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Inventory reference belongs to a different tenant.');

        $this->receipt($tenantId, $otherWarehouseId, $stockItem, '1.000000', '1.000000');
    }

    public function test_availability_reads_do_not_create_balances_and_scrapped_stock_is_unavailable(): void
    {
        [$tenantId, $warehouseId, $item] = $this->stockContext();
        $data = new StockBalanceData($tenantId, (int) $item->getKey(), $warehouseId);

        $availability = app(StockAvailabilityService::class)->availability($data);

        $this->assertSame('0.000000', $availability->quantityOnHand);
        $this->assertDatabaseCount('inventory_stock_balances', 0);

        $this->receipt($tenantId, $warehouseId, $item, '10.000000', '2.000000');
        $balance = InventoryStockBalance::query()->where('item_id', $item->getKey())->firstOrFail();
        $balance->quantity_scrapped = '2.000000';
        app(StockBalanceService::class)->recalculateAvailable($balance);
        $balance->save();

        $this->assertSame('8.000000', app(StockAvailabilityService::class)->availability($data)->quantityAvailable);
    }

    public function test_serial_movements_require_a_receipt_and_matching_stock_location(): void
    {
        $tenantId = $this->createTenant();
        $firstWarehouseId = $this->createWarehouse($tenantId, 'WH-SERIAL-A');
        $secondWarehouseId = $this->createWarehouse($tenantId, 'WH-SERIAL-B');
        $item = $this->createItem($tenantId, 'SERIAL-LOCATION', TrackingType::Serial);
        $unreceived = app(SerialTrackingService::class)->create($tenantId, (int) $item->getKey(), 'SN-UNRECEIVED');
        $received = app(SerialTrackingService::class)->create($tenantId, (int) $item->getKey(), 'SN-RECEIVED');
        $other = app(SerialTrackingService::class)->create($tenantId, (int) $item->getKey(), 'SN-OTHER');
        $this->receipt($tenantId, $firstWarehouseId, $item, '1.000000', '5.000000', serialId: (int) $received->getKey());
        $this->receipt($tenantId, $secondWarehouseId, $item, '1.000000', '5.000000', serialId: (int) $other->getKey());

        try {
            app(StockMovementService::class)->record(new StockMovementData(
                $tenantId,
                '2026-06-06',
                InventoryMovementType::Issue,
                InventoryDirection::Out,
                (int) $item->getKey(),
                $firstWarehouseId,
                '1.000000',
                serialNumberId: (int) $unreceived->getKey(),
            ));
            $this->fail('Expected an unreceived serial issue to fail.');
        } catch (InvalidArgumentException $exception) {
            $this->assertSame('Inventory serial number has no available receipt to issue.', $exception->getMessage());
        }

        try {
            $this->receipt($tenantId, $secondWarehouseId, $item, '1.000000', '5.000000', serialId: (int) $received->getKey());
            $this->fail('Expected a duplicate serial receipt to fail.');
        } catch (InvalidArgumentException $exception) {
            $this->assertSame('Inventory serial number is already available in stock.', $exception->getMessage());
        }

        try {
            app(StockMovementService::class)->record(new StockMovementData(
                $tenantId,
                '2026-06-06',
                InventoryMovementType::Issue,
                InventoryDirection::Out,
                (int) $item->getKey(),
                $secondWarehouseId,
                '1.000000',
                serialNumberId: (int) $received->getKey(),
            ));
            $this->fail('Expected a serial issue from the wrong warehouse to fail.');
        } catch (InvalidArgumentException $exception) {
            $this->assertSame('Inventory serial number does not match the issue stock location.', $exception->getMessage());
        }

        $this->assertSame('1.000000', app(StockAvailabilityService::class)->availability(
            new StockBalanceData($tenantId, (int) $item->getKey(), $firstWarehouseId),
        )->quantityAvailable);
        $this->assertSame('1.000000', app(StockAvailabilityService::class)->availability(
            new StockBalanceData($tenantId, (int) $item->getKey(), $secondWarehouseId),
        )->quantityAvailable);
    }

    public function test_adjustment_rejects_a_location_from_another_warehouse(): void
    {
        $tenantId = $this->createTenant();
        $warehouseId = $this->createWarehouse($tenantId, 'WH-ADJ-A');
        $otherWarehouseId = $this->createWarehouse($tenantId, 'WH-ADJ-B');
        $otherLocationId = $this->createWarehouseLocation($tenantId, $otherWarehouseId, 'BIN-B');
        $item = $this->createItem($tenantId, 'ADJ-LOCATION');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Warehouse location must belong to the warehouse.');

        app(StockAdjustmentService::class)->create(new StockAdjustmentData(
            tenantId: $tenantId,
            adjustmentDate: '2026-06-06',
            adjustmentType: AdjustmentType::Increase,
            warehouseId: $warehouseId,
            warehouseLocationId: $otherLocationId,
            lines: [
                new StockAdjustmentLineData((int) $item->getKey(), '0.000000', '1.000000', '1.000000'),
            ],
        ));
    }

    public function test_inventory_movement_report_searches_real_columns(): void
    {
        $report = app(ReportDefinitionRegistry::class)->get('inventory.stock-movement');

        $this->assertContains('movement_number', $report->search);
        $this->assertNotContains('reference_number', $report->search);
    }
}
