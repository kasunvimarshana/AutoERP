<?php

declare(strict_types=1);

namespace Modules\Inventory\Tests;

use InvalidArgumentException;
use Modules\Inventory\DTOs\AllocationData;
use Modules\Inventory\DTOs\ReservationData;
use Modules\Inventory\DTOs\StockBalanceData;
use Modules\Inventory\Enums\AllocationMethod;
use Modules\Inventory\Enums\AllocationStatus;
use Modules\Inventory\Enums\InventoryStockState;
use Modules\Inventory\Enums\ReservationStatus;
use Modules\Inventory\Enums\SerialStatus;
use Modules\Inventory\Models\InventoryAllocationIssue;
use Modules\Inventory\Models\InventoryStockBalance;
use Modules\Inventory\Services\BatchTrackingService;
use Modules\Inventory\Services\InventoryMethodResolver;
use Modules\Inventory\Services\SerialTrackingService;
use Modules\Inventory\Services\StockAllocationService;
use Modules\Inventory\Services\StockAvailabilityService;
use Modules\Inventory\Services\StockMovementService;
use Modules\Inventory\Services\StockReservationService;
use Modules\Item\Enums\TrackingType;
use Modules\Item\Models\ItemCategory;
use Modules\Warehouse\Models\WarehouseModel;

final class InventoryReservationAllocationTest extends InventoryTestCase
{
    public function test_reservation_allocation_issue_and_release_update_availability(): void
    {
        [$tenantId, $warehouseId, $item] = $this->stockContext();
        $this->receipt($tenantId, $warehouseId, $item, '10.000000', '5.000000');

        $reservation = app(StockReservationService::class)->reserve(new ReservationData(
            tenantId: $tenantId,
            reservationDate: '2026-06-06',
            itemId: (int) $item->getKey(),
            warehouseId: $warehouseId,
            quantityReserved: '6.000000',
        ));

        $this->assertSame(ReservationStatus::Active, $reservation->status);
        $this->assertSame('4.000000', app(StockAvailabilityService::class)->availability(new StockBalanceData($tenantId, (int) $item->getKey(), $warehouseId))->quantityAvailable);

        $allocation = app(StockAllocationService::class)->allocate(new AllocationData(
            tenantId: $tenantId,
            allocationDate: '2026-06-06',
            itemId: (int) $item->getKey(),
            warehouseId: $warehouseId,
            quantityAllocated: '4.000000',
            reservationId: (int) $reservation->getKey(),
        ));

        $this->assertSame(AllocationStatus::Active, $allocation->status);
        $this->assertSame('4.000000', app(StockAvailabilityService::class)->availability(new StockBalanceData($tenantId, (int) $item->getKey(), $warehouseId))->quantityAvailable);

        app(StockAllocationService::class)->issue($allocation);
        $availability = app(StockAvailabilityService::class)->availability(new StockBalanceData($tenantId, (int) $item->getKey(), $warehouseId));
        $this->assertSame('6.000000', $availability->quantityOnHand);
        $this->assertSame('4.000000', $availability->quantityAvailable);
        $this->assertDatabaseHas('inventory_stock_state_changes', [
            'item_id' => $item->getKey(),
            'from_state' => InventoryStockState::Available->value,
            'to_state' => InventoryStockState::Reserved->value,
            'source_type' => 'inventory_reservation',
        ]);
        $this->assertDatabaseHas('inventory_stock_state_changes', [
            'item_id' => $item->getKey(),
            'from_state' => InventoryStockState::Reserved->value,
            'to_state' => InventoryStockState::Allocated->value,
            'source_type' => 'inventory_allocation',
        ]);
        $this->assertDatabaseHas('inventory_stock_state_changes', [
            'item_id' => $item->getKey(),
            'from_state' => InventoryStockState::Allocated->value,
            'to_state' => InventoryStockState::Issued->value,
            'source_type' => 'inventory_allocation',
        ]);
    }

    public function test_release_reservation_restores_availability(): void
    {
        [$tenantId, $warehouseId, $item] = $this->stockContext();
        $this->receipt($tenantId, $warehouseId, $item, '10.000000', '5.000000');
        $reservation = app(StockReservationService::class)->reserve(new ReservationData($tenantId, '2026-06-06', (int) $item->getKey(), $warehouseId, '3.000000'));

        app(StockReservationService::class)->release($reservation);

        $availability = app(StockAvailabilityService::class)->availability(new StockBalanceData($tenantId, (int) $item->getKey(), $warehouseId));
        $this->assertSame('10.000000', $availability->quantityAvailable);
        $this->assertSame(ReservationStatus::Released, $reservation->refresh()->status);
    }

    public function test_fefo_batch_serial_and_manual_allocation_strategies(): void
    {
        $tenantId = $this->createTenant();
        $warehouseId = $this->createWarehouse($tenantId, 'WH-ALLOC');

        $fefoItem = $this->createItem($tenantId, 'FEFO-ITEM');
        $fefoItem->metadata = ['inventory' => ['allocation_method' => 'fefo']];
        $fefoItem->save();
        $lateBatch = app(BatchTrackingService::class)->create($tenantId, (int) $fefoItem->getKey(), 'LATE');
        $lateBatch->expiry_date = '2027-12-31';
        $lateBatch->save();
        $earlyBatch = app(BatchTrackingService::class)->create($tenantId, (int) $fefoItem->getKey(), 'EARLY');
        $earlyBatch->expiry_date = '2026-12-31';
        $earlyBatch->save();
        $this->receipt($tenantId, $warehouseId, $fefoItem, '3.000000', '4.000000', (int) $lateBatch->getKey());
        $this->receipt($tenantId, $warehouseId, $fefoItem, '3.000000', '4.000000', (int) $earlyBatch->getKey());

        $fefo = app(StockAllocationService::class)->allocate(new AllocationData(
            $tenantId,
            '2026-06-06',
            (int) $fefoItem->getKey(),
            $warehouseId,
            '4.000000',
        ));
        $this->assertSame(AllocationMethod::FEFO, $fefo->allocation_method);
        $this->assertCount(2, $fefo->lines);
        $this->assertSame($earlyBatch->getKey(), $fefo->lines[0]->batch_id);
        $this->assertSame('3.000000', (string) $fefo->lines[0]->quantity_allocated);

        $batchItem = $this->createItem($tenantId, 'BATCH-AUTO', TrackingType::Batch);
        $batch = app(BatchTrackingService::class)->create($tenantId, (int) $batchItem->getKey(), 'B-001');
        $this->receipt($tenantId, $warehouseId, $batchItem, '2.000000', '3.000000', (int) $batch->getKey());
        $batchAllocation = app(StockAllocationService::class)->allocate(new AllocationData(
            $tenantId,
            '2026-06-06',
            (int) $batchItem->getKey(),
            $warehouseId,
            '1.000000',
        ));
        $this->assertSame(AllocationMethod::Batch, $batchAllocation->allocation_method);
        $this->assertSame($batch->getKey(), $batchAllocation->lines[0]->batch_id);

        $serialItem = $this->createItem($tenantId, 'SER-AUTO', TrackingType::Serial);
        $serial = app(SerialTrackingService::class)->create($tenantId, (int) $serialItem->getKey(), 'SN-AUTO');
        $this->receipt($tenantId, $warehouseId, $serialItem, '1.000000', '6.000000', serialId: (int) $serial->getKey());
        $serialAllocation = app(StockAllocationService::class)->allocate(new AllocationData(
            $tenantId,
            '2026-06-06',
            (int) $serialItem->getKey(),
            $warehouseId,
            '1.000000',
        ));
        $this->assertSame(SerialStatus::Reserved, $serial->refresh()->status);
        app(StockAllocationService::class)->issue($serialAllocation);
        $this->assertSame(SerialStatus::Issued, $serial->refresh()->status);
        $this->assertDatabaseHas('inventory_allocation_issues', [
            'allocation_id' => $serialAllocation->getKey(),
            'quantity_issued' => '1',
        ]);
        $serialIssue = InventoryAllocationIssue::query()
            ->where('allocation_id', $serialAllocation->getKey())
            ->with('movement')
            ->firstOrFail();
        $serialReversal = app(StockMovementService::class)->reverse($serialIssue->movement);
        $this->assertSame(SerialStatus::Available, $serial->refresh()->status);
        $this->assertSame(AllocationStatus::Reversed, $serialAllocation->refresh()->status);
        $this->assertSame('1.000000', (string) $serialAllocation->quantity_reversed);
        $this->assertSame($serialReversal->getKey(), $serialIssue->refresh()->reversal_movement_id);

        $manualItem = $this->createItem($tenantId, 'MAN-ALLOC');
        $manualItem->metadata = ['inventory' => ['allocation_method' => 'manual']];
        $manualItem->save();
        $this->receipt($tenantId, $warehouseId, $manualItem, '2.000000', '1.000000');
        $manualAllocation = app(StockAllocationService::class)->allocate(new AllocationData(
            $tenantId,
            '2026-06-06',
            (int) $manualItem->getKey(),
            $warehouseId,
            '1.000000',
        ));
        $this->assertSame(AllocationMethod::Manual, $manualAllocation->allocation_method);
    }

    public function test_partial_allocation_issue_and_release_reconcile_parent_lines_and_audit(): void
    {
        [$tenantId, $warehouseId, $item] = $this->stockContext();
        $this->receipt($tenantId, $warehouseId, $item, '10.000000', '5.000000');
        $allocation = app(StockAllocationService::class)->allocate(new AllocationData(
            $tenantId,
            '2026-06-06',
            (int) $item->getKey(),
            $warehouseId,
            '6.000000',
        ));

        app(StockAllocationService::class)->issue($allocation, '2.000000');
        app(StockAllocationService::class)->release($allocation->refresh(), '1.000000');
        $allocation->refresh();

        $this->assertSame('2.000000', (string) $allocation->quantity_issued);
        $this->assertSame('1.000000', (string) $allocation->quantity_released);
        $this->assertSame('3.000000', (string) $allocation->quantity_remaining);
        $this->assertSame(AllocationStatus::Active, $allocation->status);
        $this->assertSame('2.000000', (string) InventoryAllocationIssue::query()
            ->where('allocation_id', $allocation->getKey())
            ->value('quantity_issued'));
        $balance = InventoryStockBalance::query()->where('item_id', $item->getKey())->firstOrFail();
        $this->assertSame('8.000000', (string) $balance->quantity_on_hand);
        $this->assertSame('3.000000', (string) $balance->quantity_allocated);
        $this->assertSame('5.000000', (string) $balance->quantity_available);
    }

    public function test_scoped_method_priority_uses_item_then_category_then_warehouse(): void
    {
        $tenantId = $this->createTenant();
        $warehouseId = $this->createWarehouse($tenantId, 'WH-PRIORITY');
        $warehouse = WarehouseModel::query()->findOrFail($warehouseId);
        $warehouse->metadata = ['inventory' => ['allocation_method' => 'manual']];
        $warehouse->save();
        $category = ItemCategory::query()->create([
            'tenant_id' => $tenantId,
            'code' => 'ALLOC-CAT',
            'name' => 'Allocation Category',
            'metadata' => ['inventory' => ['allocation_method' => 'fefo']],
            'is_active' => true,
        ]);
        $item = $this->createItem($tenantId, 'PRIORITY');
        $item->item_category_id = $category->getKey();
        $item->metadata = ['inventory' => ['allocation_method' => 'fifo']];
        $item->save();

        $resolver = app(InventoryMethodResolver::class);
        $this->assertSame(AllocationMethod::FIFO, $resolver->allocation($item->refresh(), $warehouseId, null));
        $item->metadata = null;
        $item->save();
        $this->assertSame(AllocationMethod::FEFO, $resolver->allocation($item->refresh(), $warehouseId, null));
        $category->metadata = null;
        $category->save();
        $this->assertSame(AllocationMethod::Manual, $resolver->allocation($item->refresh(), $warehouseId, null));
    }

    public function test_serial_allocation_excludes_expired_batches(): void
    {
        $tenantId = $this->createTenant();
        $warehouseId = $this->createWarehouse($tenantId, 'WH-SERIAL-EXPIRY');
        $item = $this->createItem($tenantId, 'SERIAL-EXPIRY', TrackingType::Serial);
        $batch = app(BatchTrackingService::class)->create($tenantId, (int) $item->getKey(), 'SER-EXP');
        $batch->expiry_date = '2027-12-31';
        $batch->save();
        $serial = app(SerialTrackingService::class)->create(
            $tenantId,
            (int) $item->getKey(),
            'SN-EXP',
            batchId: (int) $batch->getKey(),
        );
        $this->receipt(
            $tenantId,
            $warehouseId,
            $item,
            '1.000000',
            '5.000000',
            (int) $batch->getKey(),
            (int) $serial->getKey(),
        );
        $batch->expiry_date = '2020-01-01';
        $batch->save();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('No available serial number matches the allocation request.');

        app(StockAllocationService::class)->allocate(new AllocationData(
            $tenantId,
            '2026-06-06',
            (int) $item->getKey(),
            $warehouseId,
            '1.000000',
        ));
    }
}
