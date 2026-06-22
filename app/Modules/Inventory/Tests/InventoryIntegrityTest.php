<?php

declare(strict_types=1);

namespace Modules\Inventory\Tests;

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Modules\Inventory\DTOs\AllocationData;
use Modules\Inventory\DTOs\ReservationData;
use Modules\Inventory\DTOs\StockAdjustmentData;
use Modules\Inventory\DTOs\StockAdjustmentLineData;
use Modules\Inventory\DTOs\StockBalanceData;
use Modules\Inventory\DTOs\StockCountData;
use Modules\Inventory\DTOs\StockCountLineData;
use Modules\Inventory\DTOs\StockMovementData;
use Modules\Inventory\DTOs\StockTransferData;
use Modules\Inventory\DTOs\StockTransferLineData;
use Modules\Inventory\Enums\AdjustmentType;
use Modules\Inventory\Enums\InventoryDirection;
use Modules\Inventory\Enums\InventoryMovementType;
use Modules\Inventory\Models\InventoryMovement;
use Modules\Inventory\Models\InventoryStockBalance;
use Modules\Inventory\Services\InventoryNumberService;
use Modules\Inventory\Services\InventoryStockCountService;
use Modules\Inventory\Services\StockAdjustmentService;
use Modules\Inventory\Services\StockAllocationService;
use Modules\Inventory\Services\StockBalanceService;
use Modules\Inventory\Services\StockMovementService;
use Modules\Inventory\Services\StockReservationService;
use Modules\Inventory\Services\StockTransferService;
use Modules\Item\DTOs\CreateItemData;
use Modules\Item\DTOs\ItemUnitData;
use Modules\Item\Enums\CostingMethod;
use Modules\Item\Enums\ItemType;
use Modules\Item\Enums\ItemUnitRole;
use Modules\Item\Enums\TrackingType;
use Modules\Item\Models\Item;
use Modules\Item\Services\ItemCreationService;
use Tests\TestCase;

final class InventoryIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_entered_uom_is_preserved_while_stock_uses_base_quantity(): void
    {
        [$tenantId, $warehouseId, $item, $baseUomId, $boxUomId] = $this->uomStockContext();

        $movement = app(StockMovementService::class)->record(new StockMovementData(
            tenantId: $tenantId,
            movementDate: '2026-06-14',
            movementType: InventoryMovementType::Receipt,
            direction: InventoryDirection::In,
            itemId: (int) $item->getKey(),
            warehouseId: $warehouseId,
            quantity: '2.000000',
            unitCost: '60.000000',
            uomId: $boxUomId,
        ));

        $this->assertSame($baseUomId, $movement->base_uom_id);
        $this->assertSame($boxUomId, $movement->entered_uom_id);
        $this->assertSame('2.000000', (string) $movement->entered_quantity);
        $this->assertSame('60.000000', (string) $movement->entered_unit_cost);
        $this->assertSame('12.000000', (string) $movement->conversion_factor);
        $this->assertSame('24.000000', (string) $movement->quantity);
        $this->assertSame('5.000000', (string) $movement->unit_cost);

        $reservation = app(StockReservationService::class)->reserve(new ReservationData(
            tenantId: $tenantId,
            reservationDate: '2026-06-14',
            itemId: (int) $item->getKey(),
            warehouseId: $warehouseId,
            quantityReserved: '1.000000',
            uomId: $boxUomId,
        ));
        $allocation = app(StockAllocationService::class)->allocate(new AllocationData(
            tenantId: $tenantId,
            allocationDate: '2026-06-14',
            itemId: (int) $item->getKey(),
            warehouseId: $warehouseId,
            quantityAllocated: '0.500000',
            reservationId: (int) $reservation->getKey(),
            uomId: $boxUomId,
        ));

        $this->assertSame('1.000000', (string) $reservation->entered_quantity);
        $this->assertSame('12.000000', (string) $reservation->quantity_reserved);
        $this->assertSame('0.500000', (string) $allocation->entered_quantity);
        $this->assertSame('6.000000', (string) $allocation->quantity_allocated);

        $this->assertDatabaseHas('inventory_stock_balances', [
            'tenant_id' => $tenantId,
            'item_id' => $item->getKey(),
            'warehouse_id' => $warehouseId,
            'base_uom_id' => $baseUomId,
            'quantity_on_hand' => '24',
            'quantity_reserved' => '6',
            'quantity_allocated' => '6',
            'quantity_available' => '12',
            'total_value' => '120',
        ]);
    }

    public function test_dimension_key_prevents_duplicate_null_dimension_balances(): void
    {
        [$tenantId, $warehouseId, $item] = $this->stockContext();
        $data = new StockBalanceData($tenantId, (int) $item->getKey(), $warehouseId);
        $balances = app(StockBalanceService::class);

        $first = $balances->getOrCreate($data);
        $second = $balances->getOrCreate($data);

        $this->assertSame($first->getKey(), $second->getKey());
        $this->assertSame(1, InventoryStockBalance::query()->count());
        $this->assertSame(64, strlen((string) $first->dimension_key));

        $this->expectException(QueryException::class);
        $first->replicate()->save();
    }

    public function test_inventory_numbers_are_tenant_scoped_and_monotonic(): void
    {
        $firstTenant = $this->createTenant('NUM-A');
        $secondTenant = $this->createTenant('NUM-B');
        $numbers = app(InventoryNumberService::class);
        $date = now()->format('Ymd');

        $this->assertSame("MOV-{$date}-000001", $numbers->next($firstTenant, 'MOV'));
        $this->assertSame("MOV-{$date}-000002", $numbers->next($firstTenant, 'MOV'));
        $this->assertSame("RES-{$date}-000001", $numbers->next($firstTenant, 'RES'));
        $this->assertSame("MOV-{$date}-000001", $numbers->next($secondTenant, 'MOV'));
    }

    public function test_stock_count_rejects_posting_after_stock_changes(): void
    {
        [$tenantId, $warehouseId, $item] = $this->stockContext();
        $this->receipt($tenantId, $warehouseId, $item, '10.000000');

        $counts = app(InventoryStockCountService::class);
        $count = $counts->create(new StockCountData(
            tenantId: $tenantId,
            countDate: '2026-06-14',
            warehouseId: $warehouseId,
            lines: [
                new StockCountLineData(
                    itemId: (int) $item->getKey(),
                    countedQuantity: '9.000000',
                ),
            ],
        ));
        $this->receipt($tenantId, $warehouseId, $item, '1.000000');

        try {
            $counts->post($count);
            $this->fail('Posting a stale inventory stock count should fail.');
        } catch (InvalidArgumentException $exception) {
            $this->assertSame(
                'Inventory stock changed after the count was created. Create a new count before posting.',
                $exception->getMessage(),
            );
        }

        $this->assertDatabaseHas('inventory_stock_counts', [
            'id' => $count->getKey(),
            'status' => 'draft',
            'inventory_adjustment_id' => null,
        ]);
    }

    public function test_duplicate_active_source_allocation_is_rejected_without_mutating_balance(): void
    {
        [$tenantId, $warehouseId, $item] = $this->stockContext();
        $this->receipt($tenantId, $warehouseId, $item, '10.000000');
        $allocations = app(StockAllocationService::class);
        $data = new AllocationData(
            tenantId: $tenantId,
            allocationDate: '2026-06-14',
            itemId: (int) $item->getKey(),
            warehouseId: $warehouseId,
            quantityAllocated: '2.000000',
            sourceType: 'sales_delivery',
            sourceId: 100,
            sourceLineType: 'sales_delivery_line',
            sourceLineId: 200,
        );

        $allocations->allocate($data);

        try {
            $allocations->allocate($data);
            $this->fail('A duplicate active source allocation should fail.');
        } catch (InvalidArgumentException $exception) {
            $this->assertSame(
                'An inventory allocation already exists for this source line.',
                $exception->getMessage(),
            );
        }

        $this->assertDatabaseCount('inventory_allocations', 1);
        $this->assertDatabaseHas('inventory_stock_balances', [
            'item_id' => $item->getKey(),
            'quantity_allocated' => '2',
            'quantity_available' => '8',
        ]);
    }

    public function test_adjustment_rejects_posting_after_stock_changes(): void
    {
        [$tenantId, $warehouseId, $item] = $this->stockContext();
        $this->receipt($tenantId, $warehouseId, $item, '10.000000');

        $adjustments = app(StockAdjustmentService::class);
        $adjustment = $adjustments->create(new StockAdjustmentData(
            tenantId: $tenantId,
            adjustmentDate: '2026-06-14',
            adjustmentType: AdjustmentType::Recount,
            warehouseId: $warehouseId,
            lines: [
                new StockAdjustmentLineData(
                    itemId: (int) $item->getKey(),
                    systemQuantity: '10.000000',
                    countedQuantity: '9.000000',
                    adjustmentQuantity: '-1.000000',
                ),
            ],
        ));
        $this->receipt($tenantId, $warehouseId, $item, '1.000000');

        $this->assertInvalidOperation(
            fn () => $adjustments->post($adjustment),
            'Inventory stock changed after the adjustment was created. Create a new adjustment before posting.',
        );
        $this->assertDatabaseHas('inventory_adjustments', [
            'id' => $adjustment->getKey(),
            'status' => 'draft',
        ]);
        $this->assertDatabaseHas('inventory_stock_balances', [
            'item_id' => $item->getKey(),
            'quantity_on_hand' => '11',
        ]);
    }

    public function test_balance_reconciliation_detects_state_mismatch(): void
    {
        [$tenantId, $warehouseId, $item] = $this->stockContext();
        $this->receipt($tenantId, $warehouseId, $item, '10.000000');
        $balance = InventoryStockBalance::query()->firstOrFail();
        $balance->quantity_available = '9.000000';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Inventory available quantity is not reconciled to its stock states.');

        app(StockBalanceService::class)->assertReconciled($balance);
    }

    public function test_duplicate_workflow_dimension_lines_are_rejected(): void
    {
        [$tenantId, $warehouseId, $item] = $this->stockContext();
        $secondWarehouseId = $this->createWarehouse($tenantId);
        $itemId = (int) $item->getKey();

        $this->assertInvalidOperation(
            fn () => app(InventoryStockCountService::class)->create(new StockCountData(
                tenantId: $tenantId,
                countDate: '2026-06-14',
                warehouseId: $warehouseId,
                lines: [
                    new StockCountLineData($itemId, '1.000000'),
                    new StockCountLineData($itemId, '1.000000'),
                ],
            )),
            'Inventory stock count contains duplicate stock dimension lines.',
        );
        $this->assertInvalidOperation(
            fn () => app(StockAdjustmentService::class)->create(new StockAdjustmentData(
                tenantId: $tenantId,
                adjustmentDate: '2026-06-14',
                adjustmentType: AdjustmentType::Increase,
                warehouseId: $warehouseId,
                lines: [
                    new StockAdjustmentLineData($itemId, '0.000000', '1.000000', '1.000000'),
                    new StockAdjustmentLineData($itemId, '0.000000', '1.000000', '1.000000'),
                ],
            )),
            'Inventory adjustment contains duplicate stock dimension lines.',
        );
        $this->assertInvalidOperation(
            fn () => app(StockTransferService::class)->create(new StockTransferData(
                tenantId: $tenantId,
                transferDate: '2026-06-14',
                fromWarehouseId: $warehouseId,
                toWarehouseId: $secondWarehouseId,
                lines: [
                    new StockTransferLineData($itemId, '1.000000'),
                    new StockTransferLineData($itemId, '1.000000'),
                ],
            )),
            'Inventory transfer contains duplicate stock dimension lines.',
        );
    }

    public function test_inventory_history_restricts_hard_deletion_of_referenced_item(): void
    {
        [$tenantId, $warehouseId, $item] = $this->stockContext();
        $movement = $this->receipt($tenantId, $warehouseId, $item, '1.000000');

        try {
            DB::table('items')->where('id', $item->getKey())->delete();
            $this->fail('A referenced inventory item should not be hard deleted.');
        } catch (QueryException) {
            $this->assertDatabaseHas('inventory_movements', ['id' => $movement->getKey()]);
            $this->assertDatabaseHas('items', ['id' => $item->getKey()]);
        }
    }

    /**
     * @return array{int, int, Item}
     */
    private function stockContext(): array
    {
        $tenantId = $this->createTenant();
        $warehouseId = $this->createWarehouse($tenantId);
        $item = $this->createItem($tenantId);

        return [$tenantId, $warehouseId, $item];
    }

    /**
     * @return array{int, int, Item, int, int}
     */
    private function uomStockContext(): array
    {
        $tenantId = $this->createTenant();
        $warehouseId = $this->createWarehouse($tenantId);
        $baseUomId = $this->createUom($tenantId, 'PCS', true);
        $boxUomId = $this->createUom($tenantId, 'BOX');
        $item = $this->createItem($tenantId, $baseUomId, [
            new ItemUnitData($baseUomId, ItemUnitRole::Base, '1.000000', true),
            new ItemUnitData($boxUomId, ItemUnitRole::Purchase, '12.000000', true),
        ]);

        return [$tenantId, $warehouseId, $item, $baseUomId, $boxUomId];
    }

    private function receipt(
        int $tenantId,
        int $warehouseId,
        Item $item,
        string $quantity,
    ): InventoryMovement {
        return app(StockMovementService::class)->record(new StockMovementData(
            tenantId: $tenantId,
            movementDate: '2026-06-14',
            movementType: InventoryMovementType::Receipt,
            direction: InventoryDirection::In,
            itemId: (int) $item->getKey(),
            warehouseId: $warehouseId,
            quantity: $quantity,
            unitCost: '1.000000',
        ));
    }

    /**
     * @param  list<ItemUnitData>  $units
     */
    private function createItem(
        int $tenantId,
        ?int $baseUomId = null,
        array $units = [],
    ): Item {
        $code = 'ITEM-'.Str::upper(Str::random(8));

        return app(ItemCreationService::class)->create(new CreateItemData(
            tenantId: $tenantId,
            code: $code,
            name: 'Inventory '.$code,
            itemType: ItemType::Stock,
            trackingType: TrackingType::None,
            costingMethod: CostingMethod::Fifo,
            baseUomId: $baseUomId,
            isStockable: true,
            units: $units,
        ));
    }

    private function createTenant(string $suffix = ''): int
    {
        $suffix = $suffix !== '' ? $suffix : Str::upper(Str::random(8));

        return (int) DB::table('tenants')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'code' => 'TEN-INV-'.$suffix,
            'name' => 'Inventory Tenant '.$suffix,
            'slug' => 'inventory-tenant-'.Str::lower($suffix),
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now()]);
    }

    private function createWarehouse(int $tenantId): int
    {
        $code = 'WH-'.Str::upper(Str::random(8));

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

    private function createUom(int $tenantId, string $code, bool $isBase = false): int
    {
        return (int) DB::table('unit_of_measures')->insertGetId([
            'tenant_id' => $tenantId,
            'row_version' => 1,
            'code' => $code,
            'name' => $code.' Unit',
            'symbol' => Str::lower($code),
            'type' => 'unit',
            'category' => 'quantity',
            'decimal_precision' => 6,
            'allow_fractional_quantity' => true,
            'is_base' => $isBase,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function assertInvalidOperation(callable $operation, string $message): void
    {
        try {
            $operation();
            $this->fail($message);
        } catch (InvalidArgumentException $exception) {
            $this->assertSame($message, $exception->getMessage());
        }
    }
}
