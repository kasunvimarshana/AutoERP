<?php

declare(strict_types=1);

namespace Modules\Inventory\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Modules\Inventory\DTOs\AllocationData;
use Modules\Inventory\DTOs\CostAdjustmentData;
use Modules\Inventory\DTOs\CostAdjustmentLineData;
use Modules\Inventory\DTOs\ReservationData;
use Modules\Inventory\DTOs\StockAdjustmentData;
use Modules\Inventory\DTOs\StockAdjustmentLineData;
use Modules\Inventory\DTOs\StockBalanceData;
use Modules\Inventory\DTOs\StockCountData;
use Modules\Inventory\DTOs\StockCountLineData;
use Modules\Inventory\DTOs\StockMovementData;
use Modules\Inventory\DTOs\StockTransferData;
use Modules\Inventory\DTOs\StockTransferLineData;
use Modules\Inventory\Enums\AdjustmentStatus;
use Modules\Inventory\Enums\AdjustmentType;
use Modules\Inventory\Enums\AllocationMethod;
use Modules\Inventory\Enums\AllocationStatus;
use Modules\Inventory\Enums\CostAdjustmentStatus;
use Modules\Inventory\Enums\InventoryDirection;
use Modules\Inventory\Enums\InventoryMovementType;
use Modules\Inventory\Enums\InventoryStockState;
use Modules\Inventory\Enums\ReservationStatus;
use Modules\Inventory\Enums\SerialStatus;
use Modules\Inventory\Enums\StockCountStatus;
use Modules\Inventory\Enums\TransferStatus;
use Modules\Inventory\Models\InventoryAllocationIssue;
use Modules\Inventory\Models\InventoryMovement;
use Modules\Inventory\Models\InventoryStockBalance;
use Modules\Inventory\Models\InventoryValuationConsumption;
use Modules\Inventory\Models\InventoryValuationLayer;
use Modules\Inventory\Services\BatchTrackingService;
use Modules\Inventory\Services\InventoryCostAdjustmentService;
use Modules\Inventory\Services\InventoryMethodResolver;
use Modules\Inventory\Services\InventoryStockCountService;
use Modules\Inventory\Services\SerialTrackingService;
use Modules\Inventory\Services\StockAdjustmentService;
use Modules\Inventory\Services\StockAllocationService;
use Modules\Inventory\Services\StockAvailabilityService;
use Modules\Inventory\Services\StockMovementService;
use Modules\Inventory\Services\StockReservationService;
use Modules\Inventory\Services\StockTransferService;
use Modules\Item\DTOs\CreateItemData;
use Modules\Item\Enums\CostingMethod;
use Modules\Item\Enums\ItemType;
use Modules\Item\Enums\TrackingType;
use Modules\Item\Models\Item;
use Modules\Item\Models\ItemCategory;
use Modules\Item\Services\ItemCreationService;
use Modules\Warehouse\Models\WarehouseModel;
use Tests\TestCase;

final class InventoryEngineTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_opening_balance_adjustment_posts_stock(): void
    {
        [$tenantId, $warehouseId, $item] = $this->stockContext();
        $adjustment = app(StockAdjustmentService::class)->create(new StockAdjustmentData(
            tenantId: $tenantId,
            adjustmentDate: '2026-06-06',
            adjustmentType: AdjustmentType::OpeningBalance,
            warehouseId: $warehouseId,
            lines: [
                new StockAdjustmentLineData((int) $item->getKey(), '0.000000', '15.000000', '15.000000', '8.000000'),
            ],
        ));

        app(StockAdjustmentService::class)->post($adjustment);

        $this->assertSame('15.000000', app(StockAvailabilityService::class)->availability(new StockBalanceData($tenantId, (int) $item->getKey(), $warehouseId))->quantityOnHand);
        $this->assertSame(AdjustmentStatus::Posted, $adjustment->refresh()->status);

        app(StockAdjustmentService::class)->reverse($adjustment);
        $this->assertSame('0.000000', app(StockAvailabilityService::class)->availability(new StockBalanceData($tenantId, (int) $item->getKey(), $warehouseId))->quantityOnHand);
        $this->assertSame(AdjustmentStatus::Reversed, $adjustment->refresh()->status);
    }

    public function test_transfer_dispatch_receive_and_reverse_tracks_in_transit_stock(): void
    {
        [$tenantId, $fromWarehouseId, $item] = $this->stockContext();
        $toWarehouseId = $this->createWarehouse($tenantId, 'WH-TO');
        $this->receipt($tenantId, $fromWarehouseId, $item, '10.000000', '4.000000');

        $transfer = app(StockTransferService::class)->create(new StockTransferData(
            tenantId: $tenantId,
            transferDate: '2026-06-06',
            fromWarehouseId: $fromWarehouseId,
            toWarehouseId: $toWarehouseId,
            lines: [
                new StockTransferLineData((int) $item->getKey(), '3.000000', '4.000000'),
            ],
        ));

        app(StockTransferService::class)->post($transfer);

        $this->assertSame(TransferStatus::Dispatched, $transfer->refresh()->status);
        $this->assertSame('7.000000', app(StockAvailabilityService::class)->availability(new StockBalanceData($tenantId, (int) $item->getKey(), $fromWarehouseId))->quantityOnHand);
        $toAvailability = app(StockAvailabilityService::class)->availability(new StockBalanceData($tenantId, (int) $item->getKey(), $toWarehouseId));
        $this->assertSame('0.000000', $toAvailability->quantityOnHand);
        $this->assertSame('3.000000', $toAvailability->quantityInTransit);
        $this->assertSame('3.000000', (string) InventoryStockBalance::query()
            ->where('item_id', $item->getKey())
            ->where('warehouse_id', $toWarehouseId)
            ->value('quantity_in_transit'));
        $this->assertDatabaseHas('inventory_stock_state_changes', [
            'item_id' => $item->getKey(),
            'from_state' => InventoryStockState::Available->value,
            'to_state' => InventoryStockState::InTransit->value,
            'source_type' => 'inventory_transfer',
        ]);

        app(StockTransferService::class)->receive($transfer);

        $this->assertSame(TransferStatus::Received, $transfer->refresh()->status);
        $toAvailability = app(StockAvailabilityService::class)->availability(new StockBalanceData($tenantId, (int) $item->getKey(), $toWarehouseId));
        $this->assertSame('3.000000', $toAvailability->quantityOnHand);
        $this->assertSame('0.000000', $toAvailability->quantityInTransit);
        $this->assertSame('0.000000', (string) InventoryStockBalance::query()
            ->where('item_id', $item->getKey())
            ->where('warehouse_id', $toWarehouseId)
            ->value('quantity_in_transit'));
        $this->assertDatabaseHas('inventory_stock_state_changes', [
            'item_id' => $item->getKey(),
            'from_state' => InventoryStockState::InTransit->value,
            'to_state' => InventoryStockState::Available->value,
            'source_type' => 'inventory_transfer',
        ]);

        app(StockTransferService::class)->reverse($transfer);
        $this->assertSame(TransferStatus::Reversed, $transfer->refresh()->status);
        $this->assertSame('10.000000', app(StockAvailabilityService::class)->availability(new StockBalanceData($tenantId, (int) $item->getKey(), $fromWarehouseId))->quantityOnHand);
        $this->assertSame('0.000000', app(StockAvailabilityService::class)->availability(new StockBalanceData($tenantId, (int) $item->getKey(), $toWarehouseId))->quantityOnHand);
    }

    public function test_cost_adjustment_updates_open_valuation_layer_without_receiving_again(): void
    {
        [$tenantId, $warehouseId, $item] = $this->stockContext();
        $this->receipt($tenantId, $warehouseId, $item, '10.000000', '5.000000');
        $layer = InventoryValuationLayer::query()->where('item_id', $item->getKey())->firstOrFail();

        $adjustment = app(InventoryCostAdjustmentService::class)->create(new CostAdjustmentData(
            tenantId: $tenantId,
            adjustmentDate: '2026-06-06',
            lines: [
                new CostAdjustmentLineData((int) $layer->getKey(), '20.000000', 'Freight'),
            ],
        ));

        app(InventoryCostAdjustmentService::class)->post($adjustment);

        $this->assertSame(CostAdjustmentStatus::Posted, $adjustment->refresh()->status);
        $layer->refresh();
        $this->assertSame('70.000000', (string) $layer->remaining_value);
        $this->assertSame('7.000000', (string) $layer->unit_cost);
        $balance = InventoryStockBalance::query()->where('item_id', $item->getKey())->firstOrFail();
        $this->assertSame('70.000000', (string) $balance->total_value);
        $this->assertSame('7.000000', (string) $balance->average_cost);
    }

    public function test_stock_count_detects_variance_and_posts_recount_adjustment(): void
    {
        [$tenantId, $warehouseId, $item] = $this->stockContext();
        $this->receipt($tenantId, $warehouseId, $item, '10.000000', '5.000000');

        $count = app(InventoryStockCountService::class)->create(new StockCountData(
            tenantId: $tenantId,
            countDate: '2026-06-06',
            warehouseId: $warehouseId,
            lines: [
                new StockCountLineData((int) $item->getKey(), '8.000000'),
            ],
        ));

        $this->assertSame('10.000000', (string) $count->lines[0]->system_quantity);
        $this->assertSame('-2.000000', (string) $count->lines[0]->variance_quantity);

        app(InventoryStockCountService::class)->approve($count);
        $this->assertSame(StockCountStatus::Approved, $count->refresh()->status);

        app(InventoryStockCountService::class)->post($count);

        $this->assertSame(StockCountStatus::Posted, $count->refresh()->status);
        $this->assertNotNull($count->inventory_adjustment_id);
        $availability = app(StockAvailabilityService::class)->availability(new StockBalanceData($tenantId, (int) $item->getKey(), $warehouseId));
        $this->assertSame('8.000000', $availability->quantityOnHand);
        $this->assertSame('8.000000', $availability->quantityAvailable);
    }

    public function test_fifo_layers_are_consumed_and_weighted_average_recalculates(): void
    {
        [$tenantId, $warehouseId, $fifoItem] = $this->stockContext();
        $this->receipt($tenantId, $warehouseId, $fifoItem, '10.000000', '5.000000');
        $this->receipt($tenantId, $warehouseId, $fifoItem, '5.000000', '8.000000');

        app(StockMovementService::class)->record(new StockMovementData($tenantId, '2026-06-06', InventoryMovementType::Issue, InventoryDirection::Out, (int) $fifoItem->getKey(), $warehouseId, '12.000000'));

        $layers = InventoryValuationLayer::query()->where('item_id', $fifoItem->getKey())->orderBy('id')->get();
        $this->assertSame('0.000000', (string) $layers[0]->remaining_quantity);
        $this->assertSame('3.000000', (string) $layers[1]->remaining_quantity);

        $weightedItem = $this->createItem($tenantId, 'WA-ITEM', TrackingType::None, CostingMethod::WeightedAverage);
        $this->receipt($tenantId, $warehouseId, $weightedItem, '10.000000', '5.000000');
        $this->receipt($tenantId, $warehouseId, $weightedItem, '10.000000', '15.000000');

        $balance = InventoryStockBalance::query()->where('item_id', $weightedItem->getKey())->firstOrFail();
        $this->assertSame('10.000000', (string) $balance->average_cost);
    }

    public function test_fifo_issue_audits_each_consumed_layer_and_reversal_restores_exact_cost(): void
    {
        [$tenantId, $warehouseId, $item] = $this->stockContext();
        $this->receipt($tenantId, $warehouseId, $item, '10.000000', '5.000000');
        $this->receipt($tenantId, $warehouseId, $item, '5.000000', '8.000000');

        $issue = app(StockMovementService::class)->record(new StockMovementData(
            $tenantId,
            '2026-06-06',
            InventoryMovementType::Issue,
            InventoryDirection::Out,
            (int) $item->getKey(),
            $warehouseId,
            '12.000000',
        ));

        $this->assertSame('5.500000', (string) $issue->unit_cost);
        $this->assertSame('66.000000', (string) $issue->total_cost);
        $consumptions = InventoryValuationConsumption::query()
            ->where('issue_movement_id', $issue->getKey())
            ->orderBy('id')
            ->get();
        $this->assertCount(2, $consumptions);
        $this->assertSame('10.000000', (string) $consumptions[0]->quantity_consumed);
        $this->assertSame('50.000000', (string) $consumptions[0]->total_cost);
        $this->assertSame('2.000000', (string) $consumptions[1]->quantity_consumed);
        $this->assertSame('16.000000', (string) $consumptions[1]->total_cost);

        $reversal = app(StockMovementService::class)->reverse($issue);
        $balance = InventoryStockBalance::query()->where('item_id', $item->getKey())->firstOrFail();
        $this->assertSame('15.000000', (string) $balance->quantity_on_hand);
        $this->assertSame('90.000000', (string) $balance->total_value);
        $this->assertSame('66.000000', (string) $reversal->total_cost);
        $this->assertSame(2, InventoryValuationConsumption::query()
            ->where('issue_movement_id', $issue->getKey())
            ->where('reversed_by_movement_id', $reversal->getKey())
            ->count());
    }

    public function test_weighted_average_standard_and_manual_cost_methods_reconcile(): void
    {
        $tenantId = $this->createTenant();
        $warehouseId = $this->createWarehouse($tenantId, 'WH-COST');

        $weighted = $this->createItem($tenantId, 'WA-COST', costing: CostingMethod::WeightedAverage);
        $this->receipt($tenantId, $warehouseId, $weighted, '10.000000', '5.000000');
        $this->receipt($tenantId, $warehouseId, $weighted, '10.000000', '15.000000');
        $weightedIssue = app(StockMovementService::class)->record(new StockMovementData(
            $tenantId,
            '2026-06-06',
            InventoryMovementType::Issue,
            InventoryDirection::Out,
            (int) $weighted->getKey(),
            $warehouseId,
            '4.000000',
        ));
        $this->assertSame('10.000000', (string) $weightedIssue->unit_cost);
        $this->assertSame('40.000000', (string) $weightedIssue->total_cost);
        $weightedLayer = InventoryValuationLayer::query()->where('item_id', $weighted->getKey())->firstOrFail();
        $this->assertSame('16.000000', (string) $weightedLayer->remaining_quantity);
        $this->assertSame('160.000000', (string) $weightedLayer->remaining_value);

        $standard = $this->createItem($tenantId, 'STD-COST', costing: CostingMethod::Standard);
        $standard->metadata = ['inventory' => ['standard_cost' => '7.250000']];
        $standard->save();
        $standardReceipt = $this->receipt($tenantId, $warehouseId, $standard, '3.000000', '99.000000');
        $this->assertSame('7.250000', (string) $standardReceipt->unit_cost);
        $this->assertSame('21.750000', (string) $standardReceipt->total_cost);

        $manual = $this->createItem($tenantId, 'MAN-COST', costing: CostingMethod::Manual);
        $this->receipt($tenantId, $warehouseId, $manual, '3.000000', '2.125000');
        $manualIssue = app(StockMovementService::class)->record(new StockMovementData(
            $tenantId,
            '2026-06-06',
            InventoryMovementType::Issue,
            InventoryDirection::Out,
            (int) $manual->getKey(),
            $warehouseId,
            '2.000000',
        ));
        $this->assertSame('4.250000', (string) $manualIssue->total_cost);
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
        $serviceItem = $this->createItem($tenantId, 'SERVICE-ITEM', TrackingType::None, CostingMethod::Fifo, ItemType::Service, false);

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

    private function stockContext(): array
    {
        $tenantId = $this->createTenant();
        $warehouseId = $this->createWarehouse($tenantId, 'WH-'.Str::upper(Str::random(4)));
        $item = $this->createItem($tenantId, 'ITEM-'.Str::upper(Str::random(4)));

        return [$tenantId, $warehouseId, $item];
    }

    private function receipt(
        int $tenantId,
        int $warehouseId,
        Item $item,
        string $quantity,
        string $unitCost,
        ?int $batchId = null,
        ?int $serialId = null,
    ): InventoryMovement {
        return app(StockMovementService::class)->record(new StockMovementData(
            tenantId: $tenantId,
            movementDate: '2026-06-06',
            movementType: InventoryMovementType::Receipt,
            direction: InventoryDirection::In,
            itemId: (int) $item->getKey(),
            warehouseId: $warehouseId,
            quantity: $quantity,
            batchId: $batchId,
            serialNumberId: $serialId,
            unitCost: $unitCost,
        ));
    }

    private function createItem(
        int $tenantId,
        string $code,
        TrackingType $tracking = TrackingType::None,
        CostingMethod $costing = CostingMethod::Fifo,
        ItemType $type = ItemType::Stock,
        bool $stockable = true,
    ): Item {
        return app(ItemCreationService::class)->create(new CreateItemData(
            tenantId: $tenantId,
            code: $code,
            name: 'Inventory '.$code,
            itemType: $type,
            trackingType: $tracking,
            costingMethod: $costing,
            isStockable: $stockable,
        ));
    }

    private function createTenant(string $suffix = ''): int
    {
        $suffix = $suffix !== '' ? $suffix : Str::upper(Str::random(4));

        return (int) DB::table('tenants')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'code' => 'TEN-INV-'.$suffix,
            'name' => 'Inventory Tenant '.$suffix,
            'slug' => 'inventory-tenant-'.Str::lower($suffix),
            'status' => 'active',
            'is_active' => true,
            'is_isolated' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createWarehouse(int $tenantId, string $code): int
    {
        return (int) DB::table('warehouses')->insertGetId([
            'tenant_id' => $tenantId,
            'row_version' => 1,
            'name' => 'Warehouse '.$code,
            'code' => $code,
            'type' => 'standard',
            'is_active' => true,
            'is_default' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
