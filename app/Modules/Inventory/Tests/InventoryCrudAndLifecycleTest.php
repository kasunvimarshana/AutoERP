<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Inventory\Tests;

use Illuminate\Support\Str;
use LogicException;
use Modules\Inventory\DTOs\CostAdjustmentData;
use Modules\Inventory\DTOs\CostAdjustmentLineData;
use Modules\Inventory\DTOs\ReservationData;
use Modules\Inventory\DTOs\StockAdjustmentData;
use Modules\Inventory\DTOs\StockAdjustmentLineData;
use Modules\Inventory\DTOs\StockCountData;
use Modules\Inventory\DTOs\StockCountLineData;
use Modules\Inventory\DTOs\StockTransferData;
use Modules\Inventory\DTOs\StockTransferLineData;
use Modules\Inventory\Enums\AdjustmentStatus;
use Modules\Inventory\Enums\AdjustmentType;
use Modules\Inventory\Enums\CostAdjustmentStatus;
use Modules\Inventory\Enums\SerialStatus;
use Modules\Inventory\Enums\StockCountStatus;
use Modules\Inventory\Enums\StockCountType;
use Modules\Inventory\Enums\TransferStatus;
use Modules\Inventory\Models\InventoryMovement;
use Modules\Inventory\Models\InventoryStockBalance;
use Modules\Inventory\Models\InventoryValuationLayer;
use Modules\Inventory\Services\BatchTrackingService;
use Modules\Inventory\Services\InventoryCostAdjustmentService;
use Modules\Inventory\Services\InventoryStockCountService;
use Modules\Inventory\Services\SerialTrackingService;
use Modules\Inventory\Services\StockAdjustmentService;
use Modules\Inventory\Services\StockReservationService;
use Modules\Inventory\Services\StockTransferService;
use Modules\Item\Enums\TrackingType;

final class InventoryCrudAndLifecycleTest extends InventoryTestCase
{
    public function test_adjustment_lifecycle_immutability_and_reversal(): void
    {
        [$tenantId, $warehouseId, $item] = $this->stockContext();

        $this->withTenantExecutionContext($tenantId, function () use ($tenantId, $warehouseId, $item): void {
            $adjustment = app(StockAdjustmentService::class)->create(new StockAdjustmentData(
                tenantId: $tenantId,
                adjustmentDate: '2026-06-06',
                adjustmentType: AdjustmentType::OpeningBalance,
                warehouseId: $warehouseId,
                lines: [
                    new StockAdjustmentLineData((int) $item->getKey(), '0.000000', '10.000000', '10.000000', '5.000000'),
                ],
            ));

            $this->assertSame(AdjustmentStatus::Draft, $adjustment->status);

            // Draft adjustment can be deleted
            $draftAdj = app(StockAdjustmentService::class)->create(new StockAdjustmentData(
                tenantId: $tenantId,
                adjustmentDate: '2026-06-06',
                adjustmentType: AdjustmentType::OpeningBalance,
                warehouseId: $warehouseId,
                lines: [
                    new StockAdjustmentLineData((int) $item->getKey(), '0.000000', '5.000000', '5.000000', '5.000000'),
                ],
            ));
            $draftAdj->delete();
            $this->assertSoftDeleted($draftAdj);

            // Post adjustment
            app(StockAdjustmentService::class)->post($adjustment);
            $adjustment->refresh();
            $this->assertSame(AdjustmentStatus::Posted, $adjustment->status);

            // Posted adjustment cannot be deleted directly
            $this->expectException(LogicException::class);
            $adjustment->delete();
        });
    }

    public function test_posted_adjustment_reversal_workflow(): void
    {
        [$tenantId, $warehouseId, $item] = $this->stockContext();

        $this->withTenantExecutionContext($tenantId, function () use ($tenantId, $warehouseId, $item): void {
            $adjustment = app(StockAdjustmentService::class)->create(new StockAdjustmentData(
                tenantId: $tenantId,
                adjustmentDate: '2026-06-06',
                adjustmentType: AdjustmentType::OpeningBalance,
                warehouseId: $warehouseId,
                lines: [
                    new StockAdjustmentLineData((int) $item->getKey(), '0.000000', '20.000000', '20.000000', '5.000000'),
                ],
            ));

            app(StockAdjustmentService::class)->post($adjustment);
            $this->assertSame(AdjustmentStatus::Posted, $adjustment->refresh()->status);

            $reversed = app(StockAdjustmentService::class)->reverse(
                $adjustment,
                reversedBy: null,
                reason: 'Damaged recount reversal',
                reversalDate: '2026-06-07',
            );
            $this->assertSame(AdjustmentStatus::Reversed, $reversed->status);

            $originalMovement = InventoryMovement::query()
                ->where('source_type', 'inventory_adjustment')
                ->where('source_id', $adjustment->getKey())
                ->firstOrFail();

            $reversalMovement = InventoryMovement::query()
                ->where('reversal_of_id', $originalMovement->getKey())
                ->firstOrFail();
            $this->assertSame('2026-06-07', $reversalMovement->movement_date->toDateString());
            $this->assertStringContainsString('Damaged recount reversal', (string) $reversalMovement->description);
        });
    }

    public function test_intra_warehouse_transfer_allows_different_locations(): void
    {
        [$tenantId, $warehouseId, $item] = $this->stockContext();
        $loc1 = $this->createWarehouseLocation($tenantId, $warehouseId, 'BIN-A1');
        $loc2 = $this->createWarehouseLocation($tenantId, $warehouseId, 'BIN-A2');

        $this->withTenantExecutionContext($tenantId, function () use ($tenantId, $warehouseId, $loc1, $loc2, $item): void {
            $this->receipt($tenantId, $warehouseId, $item, '30.000000', '10.000000', warehouseLocationId: $loc1);

            $transfer = app(StockTransferService::class)->create(new StockTransferData(
                tenantId: $tenantId,
                transferDate: '2026-06-06',
                fromWarehouseId: $warehouseId,
                toWarehouseId: $warehouseId,
                fromWarehouseLocationId: $loc1,
                toWarehouseLocationId: $loc2,
                lines: [
                    new StockTransferLineData((int) $item->getKey(), '10.000000'),
                ],
            ));

            $this->assertSame(TransferStatus::Pending, $transfer->status);
            $this->assertSame($warehouseId, $transfer->from_warehouse_id);
            $this->assertSame($warehouseId, $transfer->to_warehouse_id);
            $this->assertSame($loc1, $transfer->from_warehouse_location_id);
            $this->assertSame($loc2, $transfer->to_warehouse_location_id);

            // Draft can be deleted
            $transfer->delete();
            $this->assertSoftDeleted($transfer);
        });
    }

    public function test_transfer_lifecycle_immutability(): void
    {
        [$tenantId, $warehouseId, $item] = $this->stockContext();
        $warehouse2Id = $this->createWarehouse($tenantId, 'WH-DEST');

        $this->withTenantExecutionContext($tenantId, function () use ($tenantId, $warehouseId, $warehouse2Id, $item): void {
            $this->receipt($tenantId, $warehouseId, $item, '25.000000', '10.000000');

            $transfer = app(StockTransferService::class)->create(new StockTransferData(
                tenantId: $tenantId,
                transferDate: '2026-06-06',
                fromWarehouseId: $warehouseId,
                toWarehouseId: $warehouse2Id,
                lines: [
                    new StockTransferLineData((int) $item->getKey(), '10.000000'),
                ],
            ));

            app(StockTransferService::class)->post($transfer);
            $transfer->refresh();
            $this->assertSame(TransferStatus::Dispatched, $transfer->status);

            // Dispatched transfer cannot be deleted
            $this->expectException(LogicException::class);
            $transfer->delete();
        });
    }

    public function test_stock_count_lifecycle_and_draft_deletion(): void
    {
        [$tenantId, $warehouseId, $item] = $this->stockContext();

        $this->withTenantExecutionContext($tenantId, function () use ($tenantId, $warehouseId, $item): void {
            $this->receipt($tenantId, $warehouseId, $item, '10.000000', '5.000000');

            $count = app(InventoryStockCountService::class)->create(new StockCountData(
                tenantId: $tenantId,
                countDate: '2026-06-06',
                warehouseId: $warehouseId,
                countType: StockCountType::StockCount->value,
                lines: [
                    new StockCountLineData((int) $item->getKey(), '12.000000'),
                ],
            ));

            $this->assertSame(StockCountStatus::Draft, $count->status);

            // Draft count can be deleted
            $count->delete();
            $this->assertSoftDeleted($count);
        });
    }

    public function test_cost_adjustment_lifecycle_and_draft_deletion(): void
    {
        [$tenantId, $warehouseId, $item] = $this->stockContext();

        $this->withTenantExecutionContext($tenantId, function () use ($tenantId, $warehouseId, $item): void {
            $movement = $this->receipt($tenantId, $warehouseId, $item, '10.000000', '20.000000');
            $layer = InventoryValuationLayer::query()->where('movement_id', $movement->getKey())->firstOrFail();

            $costAdj = app(InventoryCostAdjustmentService::class)->create(new CostAdjustmentData(
                tenantId: $tenantId,
                adjustmentDate: '2026-06-06',
                reason: 'Freight cost adjustment',
                lines: [
                    new CostAdjustmentLineData($layer->getKey(), '50.000000', 'Additional freight'),
                ],
            ));

            $this->assertSame(CostAdjustmentStatus::Draft, $costAdj->status);

            // Draft can be deleted
            $costAdj->delete();
            $this->assertSoftDeleted($costAdj);
        });
    }

    public function test_batch_lifecycle_and_deletion_restrictions(): void
    {
        [$tenantId, $warehouseId] = $this->stockContext();
        $item = $this->createItem($tenantId, 'BATCH-ITEM-'.Str::upper(Str::random(4)), TrackingType::Batch);

        $this->withTenantExecutionContext($tenantId, function () use ($tenantId, $item): void {
            $batchService = app(BatchTrackingService::class);
            $batch = $batchService->create(
                tenantId: $tenantId,
                itemId: (int) $item->getKey(),
                batchNumber: 'LOT-TEST-001',
            );

            // Unused batch without stock can be deleted
            $batch->delete();
            $this->assertSoftDeleted($batch);
        });
    }

    public function test_ledger_immutability_hooks(): void
    {
        [$tenantId, $warehouseId, $item] = $this->stockContext();

        $this->withTenantExecutionContext($tenantId, function () use ($tenantId, $warehouseId, $item): void {
            $movement = $this->receipt($tenantId, $warehouseId, $item, '10.000000', '15.000000');

            $balance = InventoryStockBalance::query()
                ->where('tenant_id', $tenantId)
                ->where('item_id', $item->getKey())
                ->firstOrFail();

            // Direct balance deletion is prohibited
            $balanceException = null;
            try {
                $balance->delete();
            } catch (LogicException $e) {
                $balanceException = $e;
            }
            $this->assertNotNull($balanceException);
            $this->assertSame('InventoryStockBalance cannot be deleted directly. It is managed by inventory movements.', $balanceException->getMessage());

            // Valuation layer deletion is prohibited
            $layer = InventoryValuationLayer::query()->where('movement_id', $movement->getKey())->firstOrFail();
            $layerException = null;
            try {
                $layer->delete();
            } catch (LogicException $e) {
                $layerException = $e;
            }
            $this->assertNotNull($layerException);
            $this->assertSame('InventoryValuationLayer is an immutable accounting ledger and cannot be deleted.', $layerException->getMessage());
        });
    }

    public function test_serial_number_lifecycle_and_immutability(): void
    {
        [$tenantId, $warehouseId, $item] = $this->stockContext();

        $this->withTenantExecutionContext($tenantId, function () use ($tenantId, $warehouseId, $item): void {
            $serialService = app(SerialTrackingService::class);
            $serial = $serialService->create(
                tenantId: $tenantId,
                itemId: (int) $item->getKey(),
                serialNumber: 'SN-TEST-001',
                warehouseId: $warehouseId,
            );

            // Structural mutation is prohibited
            $structuralException = null;
            try {
                $serial->serial_number = 'SN-MUTATED-001';
                $serial->save();
            } catch (LogicException $e) {
                $structuralException = $e;
            }
            $this->assertNotNull($structuralException);
            $this->assertSame('Structural tenant, item, and serial number attributes are immutable.', $structuralException->getMessage());

            // Unused available serial can be deleted
            $unusedSerial = $serialService->create(
                tenantId: $tenantId,
                itemId: (int) $item->getKey(),
                serialNumber: 'SN-TEST-002',
                warehouseId: $warehouseId,
            );
            $unusedSerial->delete();
            $this->assertSoftDeleted($unusedSerial);

            // Serial with status Reserved or historical movement cannot be deleted
            $serial = $serial->fresh();
            $serial->status = SerialStatus::Reserved;
            $serial->save();
            $reservedException = null;
            try {
                $serial->delete();
            } catch (LogicException $e) {
                $reservedException = $e;
            }
            $this->assertNotNull($reservedException);
            $this->assertSame('Inventory serial numbers in active or historical usage cannot be deleted.', $reservedException->getMessage());
        });
    }

    public function test_batch_deletion_blocked_by_active_reservations(): void
    {
        [$tenantId, $warehouseId] = $this->stockContext();
        $item = $this->createItem($tenantId, 'BATCH-RES-'.Str::upper(Str::random(4)), TrackingType::Batch);

        $this->withTenantExecutionContext($tenantId, function () use ($tenantId, $warehouseId, $item): void {
            $batch = app(BatchTrackingService::class)->create(
                tenantId: $tenantId,
                itemId: (int) $item->getKey(),
                batchNumber: 'LOT-RES-001',
            );

            $this->receipt($tenantId, $warehouseId, $item, '10.000000', '10.000000', batchId: (int) $batch->getKey());

            app(StockReservationService::class)->reserve(new ReservationData(
                tenantId: $tenantId,
                reservationDate: '2026-06-06',
                itemId: (int) $item->getKey(),
                warehouseId: $warehouseId,
                quantityReserved: '5.000000',
                batchId: (int) $batch->getKey(),
            ));

            $batchException = null;
            try {
                $batch->delete();
            } catch (LogicException $e) {
                $batchException = $e;
            }
            $this->assertNotNull($batchException);
            $this->assertStringContainsString('cannot be deleted', $batchException->getMessage());
        });
    }

    public function test_stock_count_and_cost_adjustment_tampering_blocked(): void
    {
        [$tenantId, $warehouseId, $item] = $this->stockContext();

        $this->withTenantExecutionContext($tenantId, function () use ($tenantId, $warehouseId, $item): void {
            $this->receipt($tenantId, $warehouseId, $item, '10.000000', '5.000000');

            $countService = app(InventoryStockCountService::class);
            $count = $countService->create(new StockCountData(
                tenantId: $tenantId,
                countDate: '2026-06-06',
                warehouseId: $warehouseId,
                countType: StockCountType::StockCount->value,
                lines: [
                    new StockCountLineData((int) $item->getKey(), '12.000000'),
                ],
            ));

            $count = $countService->approve($count);
            $line = $count->lines()->firstOrFail();

            // Tampering with counted quantity on approved line throws LogicException
            $tamperException = null;
            try {
                $line->counted_quantity = '15.000000';
                $line->save();
            } catch (LogicException $e) {
                $tamperException = $e;
            }
            $this->assertNotNull($tamperException);
            $this->assertSame('Lines of approved inventory stock counts cannot be updated.', $tamperException->getMessage());

            // Post count
            $postedCount = $countService->post($count);
            $postedCountException = null;
            try {
                $postedCount->warehouse_id = $warehouseId + 1;
                $postedCount->save();
            } catch (LogicException $e) {
                $postedCountException = $e;
            }
            $this->assertNotNull($postedCountException);
            $this->assertSame('Posted inventory stock counts are strictly immutable in place.', $postedCountException->getMessage());
        });
    }

    public function test_transfer_reversal_propagates_civil_date(): void
    {
        [$tenantId, $warehouseId, $item] = $this->stockContext();
        $warehouse2Id = $this->createWarehouse($tenantId, 'WH-DEST-REV');

        $this->withTenantExecutionContext($tenantId, function () use ($tenantId, $warehouseId, $warehouse2Id, $item): void {
            $this->receipt($tenantId, $warehouseId, $item, '30.000000', '10.000000');

            $transferService = app(StockTransferService::class);
            $transfer = $transferService->create(new StockTransferData(
                tenantId: $tenantId,
                transferDate: '2026-06-06',
                fromWarehouseId: $warehouseId,
                toWarehouseId: $warehouse2Id,
                lines: [
                    new StockTransferLineData((int) $item->getKey(), '10.000000'),
                ],
            ));

            $transferService->post($transfer);
            $transferService->receive($transfer);
            $this->assertSame(TransferStatus::Received, $transfer->refresh()->status);

            // Reversing propagates civil date and reason
            $reversed = $transferService->reverse(
                $transfer,
                reversedBy: null,
                reason: 'Transfer cancellation return',
                reversalDate: '2026-06-07',
            );
            $this->assertSame(TransferStatus::Reversed, $reversed->status);

            $transferMovement = InventoryMovement::query()
                ->where('source_type', 'inventory_transfer')
                ->where('source_id', $transfer->getKey())
                ->firstOrFail();

            $reversalMovement = InventoryMovement::query()
                ->where('reversal_of_id', $transferMovement->getKey())
                ->firstOrFail();
            $this->assertSame('2026-06-07', $reversalMovement->movement_date->toDateString());
        });
    }
}
