<?php

declare(strict_types=1);

namespace Modules\Inventory\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Modules\Inventory\DTOs\AllocationData;
use Modules\Inventory\DTOs\ReservationData;
use Modules\Inventory\DTOs\StockAdjustmentData;
use Modules\Inventory\DTOs\StockAdjustmentLineData;
use Modules\Inventory\DTOs\StockBalanceData;
use Modules\Inventory\DTOs\StockMovementData;
use Modules\Inventory\DTOs\StockTransferData;
use Modules\Inventory\DTOs\StockTransferLineData;
use Modules\Inventory\Enums\AdjustmentStatus;
use Modules\Inventory\Enums\AdjustmentType;
use Modules\Inventory\Enums\AllocationStatus;
use Modules\Inventory\Enums\InventoryDirection;
use Modules\Inventory\Enums\InventoryMovementType;
use Modules\Inventory\Enums\ReservationStatus;
use Modules\Inventory\Enums\TransferStatus;
use Modules\Inventory\Models\InventoryStockBalance;
use Modules\Inventory\Models\InventoryValuationLayer;
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
use Modules\Item\Services\ItemCreationService;
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
    }

    public function test_transfer_moves_stock_between_warehouses(): void
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

        $this->assertSame(TransferStatus::Posted, $transfer->refresh()->status);
        $this->assertSame('7.000000', app(StockAvailabilityService::class)->availability(new StockBalanceData($tenantId, (int) $item->getKey(), $fromWarehouseId))->quantityOnHand);
        $this->assertSame('3.000000', app(StockAvailabilityService::class)->availability(new StockBalanceData($tenantId, (int) $item->getKey(), $toWarehouseId))->quantityOnHand);
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
    ): void {
        app(StockMovementService::class)->record(new StockMovementData(
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
