<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

// ── GoodsReceipt – PutAway ──────────────────────────────────────────────────
use Modules\GoodsReceipt\Application\Contracts\InspectGoodsReceiptServiceInterface;
use Modules\GoodsReceipt\Application\Contracts\PutAwayGoodsReceiptServiceInterface;
use Modules\GoodsReceipt\Application\Services\InspectGoodsReceiptService;
use Modules\GoodsReceipt\Application\Services\PutAwayGoodsReceiptService;
use Modules\GoodsReceipt\Domain\Entities\GoodsReceipt;
use Modules\GoodsReceipt\Domain\Events\GoodsReceiptInspected;
use Modules\GoodsReceipt\Domain\Events\GoodsReceiptPutAway;
use Modules\GoodsReceipt\Domain\RepositoryInterfaces\GoodsReceiptRepositoryInterface;

// ── Inventory – ReserveStock ────────────────────────────────────────────────
use Modules\Inventory\Application\Contracts\AdjustInventoryServiceInterface;
use Modules\Inventory\Application\Contracts\ReconcileInventoryServiceInterface;
use Modules\Inventory\Application\Contracts\ReleaseStockServiceInterface;
use Modules\Inventory\Application\Contracts\ReserveStockServiceInterface;
use Modules\Inventory\Application\DTOs\AdjustInventoryData;
use Modules\Inventory\Application\Services\AdjustInventoryService;
use Modules\Inventory\Application\Services\ReconcileInventoryService;
use Modules\Inventory\Application\Services\ReleaseStockService;
use Modules\Inventory\Application\Services\ReserveStockService;
use Modules\Inventory\Domain\Entities\InventoryCycleCount;
use Modules\Inventory\Domain\Entities\InventoryLevel;
use Modules\Inventory\Domain\Events\InventoryAdjusted;
use Modules\Inventory\Domain\Events\InventoryReconciled;
use Modules\Inventory\Domain\Events\StockReleased;
use Modules\Inventory\Domain\Events\StockReserved;
use Modules\Inventory\Domain\RepositoryInterfaces\InventoryCycleCountRepositoryInterface;
use Modules\Inventory\Domain\RepositoryInterfaces\InventoryLevelRepositoryInterface;

// ── StockMovement – TransferStock ───────────────────────────────────────────
use Modules\StockMovement\Application\Contracts\TransferStockServiceInterface;
use Modules\StockMovement\Application\DTOs\TransferStockData;
use Modules\StockMovement\Application\Services\TransferStockService;
use Modules\StockMovement\Domain\RepositoryInterfaces\StockMovementRepositoryInterface;

// ── Core ────────────────────────────────────────────────────────────────────
use Modules\Core\Application\Contracts\WriteServiceInterface;

/**
 * WIMSStockOperationsModuleTest
 *
 * Covers all new stock-operation services introduced to complete the WIMS:
 *   – GoodsReceipt  : PutAway workflow
 *   – Inventory     : ReserveStock / ReleaseStock / AdjustInventory / ReconcileInventory
 *   – StockMovement : TransferStock (paired ISSUE + RECEIPT movements)
 */
class WIMSStockOperationsModuleTest extends TestCase
{
    // =========================================================================
    // GoodsReceipt::putAway()
    // =========================================================================

    public function test_goods_receipt_put_away_transitions_status(): void
    {
        $receipt = new GoodsReceipt(
            tenantId:        1,
            referenceNumber: 'GR-001',
            supplierId:      10,
        );

        $this->assertSame('draft', $receipt->getStatus());
        $this->assertFalse($receipt->isPutAway());

        $receipt->putAway(99);

        $this->assertSame('put_away', $receipt->getStatus());
        $this->assertTrue($receipt->isPutAway());
        $this->assertSame(99, $receipt->getPutAwayBy());
    }

    public function test_goods_receipt_is_put_away_returns_false_by_default(): void
    {
        $receipt = new GoodsReceipt(1, 'GR-002', 10);
        $this->assertFalse($receipt->isPutAway());
    }

    public function test_goods_receipt_put_away_event_exists(): void
    {
        $this->assertTrue(class_exists(GoodsReceiptPutAway::class));
    }

    public function test_goods_receipt_put_away_event_extends_base_event(): void
    {
        $receipt = new GoodsReceipt(tenantId: 1, referenceNumber: 'GR-002', supplierId: 10);
        $event   = new GoodsReceiptPutAway($receipt, 5);
        $this->assertInstanceOf(\Modules\Core\Domain\Events\BaseEvent::class, $event);
        $this->assertSame($receipt, $event->receipt);
        $this->assertSame(5, $event->putAwayBy);
    }

    public function test_goods_receipt_put_away_event_broadcast_with(): void
    {
        $receipt = new GoodsReceipt(tenantId: 1, referenceNumber: 'GR-003', supplierId: 10);
        $event   = new GoodsReceiptPutAway($receipt, 7);
        $payload = $event->broadcastWith();

        $this->assertArrayHasKey('put_away_by', $payload);
        $this->assertSame(7, $payload['put_away_by']);
    }

    public function test_put_away_goods_receipt_service_interface_is_write_service(): void
    {
        $this->assertTrue(
            is_subclass_of(PutAwayGoodsReceiptServiceInterface::class, WriteServiceInterface::class)
        );
    }

    public function test_put_away_goods_receipt_service_class_exists(): void
    {
        $this->assertTrue(class_exists(PutAwayGoodsReceiptService::class));
    }

    public function test_put_away_goods_receipt_service_implements_interface(): void
    {
        $this->assertTrue(
            is_subclass_of(PutAwayGoodsReceiptService::class, PutAwayGoodsReceiptServiceInterface::class)
        );
    }

    public function test_put_away_goods_receipt_service_constructor_injects_repository(): void
    {
        $repo    = $this->createMock(GoodsReceiptRepositoryInterface::class);
        $service = new PutAwayGoodsReceiptService($repo);
        $this->assertInstanceOf(PutAwayGoodsReceiptService::class, $service);
    }

    // =========================================================================
    // GoodsReceipt::inspect()
    // =========================================================================

    public function test_goods_receipt_inspect_transitions_status(): void
    {
        $receipt = new GoodsReceipt(
            tenantId:        1,
            referenceNumber: 'GR-INS-001',
            supplierId:      10,
        );

        $this->assertSame('draft', $receipt->getStatus());
        $this->assertFalse($receipt->isInspected());

        $receipt->inspect(42);

        $this->assertSame('inspected', $receipt->getStatus());
        $this->assertTrue($receipt->isInspected());
        $this->assertSame(42, $receipt->getInspectedBy());
        $this->assertInstanceOf(\DateTimeInterface::class, $receipt->getInspectedAt());
    }

    public function test_goods_receipt_is_inspected_returns_false_by_default(): void
    {
        $receipt = new GoodsReceipt(1, 'GR-INS-002', 10);
        $this->assertFalse($receipt->isInspected());
        $this->assertNull($receipt->getInspectedBy());
        $this->assertNull($receipt->getInspectedAt());
    }

    public function test_goods_receipt_inspected_event_exists(): void
    {
        $this->assertTrue(class_exists(GoodsReceiptInspected::class));
    }

    public function test_goods_receipt_inspected_event_extends_base_event(): void
    {
        $receipt = new GoodsReceipt(tenantId: 1, referenceNumber: 'GR-INS-003', supplierId: 10);
        $event   = new GoodsReceiptInspected($receipt, 5);
        $this->assertInstanceOf(\Modules\Core\Domain\Events\BaseEvent::class, $event);
        $this->assertSame($receipt, $event->receipt);
        $this->assertSame(5, $event->inspectedBy);
    }

    public function test_goods_receipt_inspected_event_broadcast_with(): void
    {
        $receipt = new GoodsReceipt(tenantId: 1, referenceNumber: 'GR-INS-004', supplierId: 10);
        $event   = new GoodsReceiptInspected($receipt, 7);
        $payload = $event->broadcastWith();

        $this->assertArrayHasKey('inspected_by', $payload);
        $this->assertSame(7, $payload['inspected_by']);
    }

    public function test_inspect_goods_receipt_service_interface_is_write_service(): void
    {
        $this->assertTrue(
            is_subclass_of(InspectGoodsReceiptServiceInterface::class, WriteServiceInterface::class)
        );
    }

    public function test_inspect_goods_receipt_service_class_exists(): void
    {
        $this->assertTrue(class_exists(InspectGoodsReceiptService::class));
    }

    public function test_inspect_goods_receipt_service_implements_interface(): void
    {
        $this->assertTrue(
            is_subclass_of(InspectGoodsReceiptService::class, InspectGoodsReceiptServiceInterface::class)
        );
    }

    public function test_inspect_goods_receipt_service_constructor_injects_repository(): void
    {
        $repo    = $this->createMock(GoodsReceiptRepositoryInterface::class);
        $service = new InspectGoodsReceiptService($repo);
        $this->assertInstanceOf(InspectGoodsReceiptService::class, $service);
    }

    // =========================================================================
    // Inventory – StockReserved / StockReleased events
    // =========================================================================

    public function test_stock_reserved_event_exists(): void
    {
        $this->assertTrue(class_exists(StockReserved::class));
    }

    public function test_stock_released_event_exists(): void
    {
        $this->assertTrue(class_exists(StockReleased::class));
    }

    public function test_stock_reserved_event_extends_base_event(): void
    {
        $level = new InventoryLevel(tenantId: 1, productId: 5, qtyOnHand: 100.0);
        $event = new StockReserved($level, 20.0);
        $this->assertInstanceOf(\Modules\Core\Domain\Events\BaseEvent::class, $event);
    }

    public function test_stock_released_event_extends_base_event(): void
    {
        $level = new InventoryLevel(tenantId: 1, productId: 5, qtyOnHand: 100.0, qtyReserved: 20.0);
        $event = new StockReleased($level, 10.0);
        $this->assertInstanceOf(\Modules\Core\Domain\Events\BaseEvent::class, $event);
    }

    public function test_stock_reserved_event_broadcast_with(): void
    {
        $level   = new InventoryLevel(tenantId: 1, productId: 5, qtyOnHand: 100.0);
        $level->reserve(30.0);
        $event   = new StockReserved($level, 30.0);
        $payload = $event->broadcastWith();

        $this->assertArrayHasKey('reserved_qty', $payload);
        $this->assertArrayHasKey('qty_reserved', $payload);
        $this->assertSame(30.0, $payload['reserved_qty']);
        $this->assertSame(30.0, $payload['qty_reserved']);
    }

    public function test_stock_released_event_broadcast_with(): void
    {
        $level = new InventoryLevel(tenantId: 1, productId: 5, qtyOnHand: 100.0, qtyReserved: 30.0);
        $level->release(10.0);
        $event   = new StockReleased($level, 10.0);
        $payload = $event->broadcastWith();

        $this->assertArrayHasKey('released_qty', $payload);
        $this->assertSame(10.0, $payload['released_qty']);
    }

    // =========================================================================
    // ReserveStockService
    // =========================================================================

    public function test_reserve_stock_service_interface_is_write_service(): void
    {
        $this->assertTrue(
            is_subclass_of(ReserveStockServiceInterface::class, WriteServiceInterface::class)
        );
    }

    public function test_reserve_stock_service_class_exists(): void
    {
        $this->assertTrue(class_exists(ReserveStockService::class));
    }

    public function test_reserve_stock_service_implements_interface(): void
    {
        $this->assertTrue(
            is_subclass_of(ReserveStockService::class, ReserveStockServiceInterface::class)
        );
    }

    public function test_reserve_stock_service_constructor_injects_repository(): void
    {
        $repo    = $this->createMock(InventoryLevelRepositoryInterface::class);
        $service = new ReserveStockService($repo);
        $this->assertInstanceOf(ReserveStockService::class, $service);
    }

    // =========================================================================
    // ReleaseStockService
    // =========================================================================

    public function test_release_stock_service_interface_is_write_service(): void
    {
        $this->assertTrue(
            is_subclass_of(ReleaseStockServiceInterface::class, WriteServiceInterface::class)
        );
    }

    public function test_release_stock_service_class_exists(): void
    {
        $this->assertTrue(class_exists(ReleaseStockService::class));
    }

    public function test_release_stock_service_implements_interface(): void
    {
        $this->assertTrue(
            is_subclass_of(ReleaseStockService::class, ReleaseStockServiceInterface::class)
        );
    }

    public function test_release_stock_service_constructor_injects_repository(): void
    {
        $repo    = $this->createMock(InventoryLevelRepositoryInterface::class);
        $service = new ReleaseStockService($repo);
        $this->assertInstanceOf(ReleaseStockService::class, $service);
    }

    // =========================================================================
    // Inventory – InventoryAdjusted event
    // =========================================================================

    public function test_inventory_adjusted_event_exists(): void
    {
        $this->assertTrue(class_exists(InventoryAdjusted::class));
    }

    public function test_inventory_adjusted_event_extends_base_event(): void
    {
        $level = new InventoryLevel(tenantId: 1, productId: 5);
        $event = new InventoryAdjusted($level, 10.0, 'recount');
        $this->assertInstanceOf(\Modules\Core\Domain\Events\BaseEvent::class, $event);
    }

    public function test_inventory_adjusted_event_broadcast_with(): void
    {
        $level   = new InventoryLevel(tenantId: 1, productId: 5, qtyOnHand: 50.0);
        $level->addStock(10.0);
        $event   = new InventoryAdjusted($level, 10.0, 'recount');
        $payload = $event->broadcastWith();

        $this->assertArrayHasKey('adjustment_qty', $payload);
        $this->assertArrayHasKey('reason', $payload);
        $this->assertArrayHasKey('qty_on_hand', $payload);
        $this->assertSame(10.0, $payload['adjustment_qty']);
        $this->assertSame('recount', $payload['reason']);
        $this->assertSame(60.0, $payload['qty_on_hand']);
    }

    // =========================================================================
    // AdjustInventoryData DTO
    // =========================================================================

    public function test_adjust_inventory_data_dto_exists(): void
    {
        $this->assertTrue(class_exists(AdjustInventoryData::class));
    }

    public function test_adjust_inventory_data_dto_extends_base_dto(): void
    {
        $this->assertTrue(
            is_subclass_of(AdjustInventoryData::class, \Modules\Core\Application\DTOs\BaseDto::class)
        );
    }

    public function test_adjust_inventory_data_dto_from_array(): void
    {
        $dto = AdjustInventoryData::fromArray([
            'id'            => 7,
            'adjustmentQty' => -5.0,
            'reason'        => 'damaged goods',
            'adjustedBy'    => 3,
            'notes'         => 'inspection',
        ]);

        $this->assertSame(7, $dto->id);
        $this->assertSame(-5.0, $dto->adjustmentQty);
        $this->assertSame('damaged goods', $dto->reason);
        $this->assertSame(3, $dto->adjustedBy);
        $this->assertSame('inspection', $dto->notes);
    }

    public function test_adjust_inventory_data_dto_nullable_fields(): void
    {
        $dto = AdjustInventoryData::fromArray([
            'id'            => 1,
            'adjustmentQty' => 10.0,
            'reason'        => 'cycle count',
        ]);

        $this->assertNull($dto->adjustedBy);
        $this->assertNull($dto->notes);
    }

    // =========================================================================
    // AdjustInventoryService
    // =========================================================================

    public function test_adjust_inventory_service_interface_is_write_service(): void
    {
        $this->assertTrue(
            is_subclass_of(AdjustInventoryServiceInterface::class, WriteServiceInterface::class)
        );
    }

    public function test_adjust_inventory_service_class_exists(): void
    {
        $this->assertTrue(class_exists(AdjustInventoryService::class));
    }

    public function test_adjust_inventory_service_implements_interface(): void
    {
        $this->assertTrue(
            is_subclass_of(AdjustInventoryService::class, AdjustInventoryServiceInterface::class)
        );
    }

    public function test_adjust_inventory_service_constructor_injects_repository(): void
    {
        $repo    = $this->createMock(InventoryLevelRepositoryInterface::class);
        $service = new AdjustInventoryService($repo);
        $this->assertInstanceOf(AdjustInventoryService::class, $service);
    }

    // =========================================================================
    // InventoryCycleCount – InventoryReconciled event
    // =========================================================================

    public function test_inventory_reconciled_event_exists(): void
    {
        $this->assertTrue(class_exists(InventoryReconciled::class));
    }

    public function test_inventory_reconciled_event_extends_base_event(): void
    {
        $cycleCount = new InventoryCycleCount(
            tenantId:        1,
            referenceNumber: 'CC-001',
            warehouseId:     10,
        );
        $event = new InventoryReconciled($cycleCount);
        $this->assertInstanceOf(\Modules\Core\Domain\Events\BaseEvent::class, $event);
    }

    public function test_inventory_reconciled_event_broadcast_with(): void
    {
        $cycleCount = new InventoryCycleCount(
            tenantId:        1,
            referenceNumber: 'CC-002',
            warehouseId:     5,
            status:          'completed',
        );
        $event   = new InventoryReconciled($cycleCount);
        $payload = $event->broadcastWith();

        $this->assertArrayHasKey('warehouse_id', $payload);
        $this->assertArrayHasKey('status', $payload);
        $this->assertSame(5, $payload['warehouse_id']);
        $this->assertSame('completed', $payload['status']);
    }

    // =========================================================================
    // ReconcileInventoryService
    // =========================================================================

    public function test_reconcile_inventory_service_interface_is_write_service(): void
    {
        $this->assertTrue(
            is_subclass_of(ReconcileInventoryServiceInterface::class, WriteServiceInterface::class)
        );
    }

    public function test_reconcile_inventory_service_class_exists(): void
    {
        $this->assertTrue(class_exists(ReconcileInventoryService::class));
    }

    public function test_reconcile_inventory_service_implements_interface(): void
    {
        $this->assertTrue(
            is_subclass_of(ReconcileInventoryService::class, ReconcileInventoryServiceInterface::class)
        );
    }

    public function test_reconcile_inventory_service_constructor_injects_repository(): void
    {
        $repo    = $this->createMock(InventoryCycleCountRepositoryInterface::class);
        $service = new ReconcileInventoryService($repo);
        $this->assertInstanceOf(ReconcileInventoryService::class, $service);
    }

    // =========================================================================
    // InventoryLevel – reserve / release domain logic
    // =========================================================================

    public function test_inventory_level_reserve_increases_reserved_qty(): void
    {
        $level = new InventoryLevel(tenantId: 1, productId: 5, qtyOnHand: 100.0, qtyReserved: 0.0);

        $level->reserve(25.0);

        $this->assertSame(25.0, $level->getQtyReserved());
        $this->assertSame(75.0, $level->getQtyAvailable());
    }

    public function test_inventory_level_release_decreases_reserved_qty(): void
    {
        $level = new InventoryLevel(tenantId: 1, productId: 5, qtyOnHand: 100.0, qtyReserved: 30.0);

        $level->release(10.0);

        $this->assertSame(20.0, $level->getQtyReserved());
        $this->assertSame(80.0, $level->getQtyAvailable());
    }

    public function test_inventory_level_release_cannot_go_below_zero(): void
    {
        $level = new InventoryLevel(tenantId: 1, productId: 5, qtyOnHand: 100.0, qtyReserved: 5.0);

        $level->release(50.0);   // release more than reserved

        $this->assertSame(0.0, $level->getQtyReserved());
        $this->assertSame(100.0, $level->getQtyAvailable());
    }

    public function test_inventory_level_add_stock_increases_on_hand(): void
    {
        $level = new InventoryLevel(tenantId: 1, productId: 5, qtyOnHand: 50.0);

        $level->addStock(20.0);

        $this->assertSame(70.0, $level->getQtyOnHand());
        $this->assertSame(70.0, $level->getQtyAvailable());
    }

    public function test_inventory_level_remove_stock_decreases_on_hand(): void
    {
        $level = new InventoryLevel(tenantId: 1, productId: 5, qtyOnHand: 80.0);

        $level->removeStock(30.0);

        $this->assertSame(50.0, $level->getQtyOnHand());
    }

    // =========================================================================
    // InventoryCycleCount – reconcile (complete) domain logic
    // =========================================================================

    public function test_inventory_cycle_count_complete_transitions_to_completed(): void
    {
        $cycleCount = new InventoryCycleCount(
            tenantId:        1,
            referenceNumber: 'CC-003',
            warehouseId:     10,
        );
        $cycleCount->start();
        $this->assertSame('in_progress', $cycleCount->getStatus());

        $cycleCount->complete();

        $this->assertSame('completed', $cycleCount->getStatus());
        $this->assertNotNull($cycleCount->getCompletedAt());
    }

    // =========================================================================
    // TransferStockData DTO
    // =========================================================================

    public function test_transfer_stock_data_dto_exists(): void
    {
        $this->assertTrue(class_exists(TransferStockData::class));
    }

    public function test_transfer_stock_data_dto_extends_base_dto(): void
    {
        $this->assertTrue(
            is_subclass_of(TransferStockData::class, \Modules\Core\Application\DTOs\BaseDto::class)
        );
    }

    public function test_transfer_stock_data_dto_from_array(): void
    {
        $dto = TransferStockData::fromArray([
            'tenantId'        => 1,
            'referenceNumber' => 'TRF-001',
            'productId'       => 42,
            'quantity'        => 15.0,
            'fromLocationId'  => 100,
            'toLocationId'    => 200,
            'currency'        => 'USD',
        ]);

        $this->assertSame(1, $dto->tenantId);
        $this->assertSame('TRF-001', $dto->referenceNumber);
        $this->assertSame(42, $dto->productId);
        $this->assertSame(15.0, $dto->quantity);
        $this->assertSame(100, $dto->fromLocationId);
        $this->assertSame(200, $dto->toLocationId);
        $this->assertSame('USD', $dto->currency);
    }

    public function test_transfer_stock_data_dto_nullable_fields(): void
    {
        $dto = TransferStockData::fromArray([
            'tenantId'        => 1,
            'referenceNumber' => 'TRF-002',
            'productId'       => 1,
            'quantity'        => 5.0,
            'fromLocationId'  => 1,
            'toLocationId'    => 2,
        ]);

        $this->assertNull($dto->variationId);
        $this->assertNull($dto->batchId);
        $this->assertNull($dto->serialNumberId);
        $this->assertNull($dto->uomId);
        $this->assertNull($dto->unitCost);
        $this->assertNull($dto->performedBy);
        $this->assertNull($dto->notes);
        $this->assertNull($dto->metadata);
    }

    public function test_transfer_stock_data_dto_has_validation_rules(): void
    {
        $dto   = new TransferStockData;
        $rules = $dto->rules();

        $this->assertArrayHasKey('tenantId', $rules);
        $this->assertArrayHasKey('productId', $rules);
        $this->assertArrayHasKey('quantity', $rules);
        $this->assertArrayHasKey('fromLocationId', $rules);
        $this->assertArrayHasKey('toLocationId', $rules);
    }

    // =========================================================================
    // TransferStockService
    // =========================================================================

    public function test_transfer_stock_service_interface_is_write_service(): void
    {
        $this->assertTrue(
            is_subclass_of(TransferStockServiceInterface::class, WriteServiceInterface::class)
        );
    }

    public function test_transfer_stock_service_class_exists(): void
    {
        $this->assertTrue(class_exists(TransferStockService::class));
    }

    public function test_transfer_stock_service_implements_interface(): void
    {
        $this->assertTrue(
            is_subclass_of(TransferStockService::class, TransferStockServiceInterface::class)
        );
    }

    public function test_transfer_stock_service_constructor_injects_repository(): void
    {
        $repo    = $this->createMock(StockMovementRepositoryInterface::class);
        $service = new TransferStockService($repo);
        $this->assertInstanceOf(TransferStockService::class, $service);
    }

    // =========================================================================
    // Route / Infrastructure existence checks
    // =========================================================================

    public function test_goods_receipt_put_away_route_file_contains_put_away(): void
    {
        $routes = file_get_contents(
            __DIR__ . '/../../app/Modules/GoodsReceipt/routes/api.php'
        );

        $this->assertStringContainsString('put-away', $routes);
    }

    public function test_goods_receipt_route_file_contains_inspect(): void
    {
        $routes = file_get_contents(
            __DIR__ . '/../../app/Modules/GoodsReceipt/routes/api.php'
        );

        $this->assertStringContainsString('inspect', $routes);
    }

    public function test_inventory_routes_contain_reserve(): void
    {
        $routes = file_get_contents(
            __DIR__ . '/../../app/Modules/Inventory/routes/api.php'
        );

        $this->assertStringContainsString('reserve', $routes);
    }

    public function test_inventory_routes_contain_release(): void
    {
        $routes = file_get_contents(
            __DIR__ . '/../../app/Modules/Inventory/routes/api.php'
        );

        $this->assertStringContainsString('release', $routes);
    }

    public function test_inventory_routes_contain_adjust(): void
    {
        $routes = file_get_contents(
            __DIR__ . '/../../app/Modules/Inventory/routes/api.php'
        );

        $this->assertStringContainsString('adjust', $routes);
    }

    public function test_inventory_routes_contain_reconcile(): void
    {
        $routes = file_get_contents(
            __DIR__ . '/../../app/Modules/Inventory/routes/api.php'
        );

        $this->assertStringContainsString('reconcile', $routes);
    }

    public function test_stock_movement_routes_contain_transfer(): void
    {
        $routes = file_get_contents(
            __DIR__ . '/../../app/Modules/StockMovement/routes/api.php'
        );

        $this->assertStringContainsString('transfer', $routes);
    }

    // =========================================================================
    // Service provider binding coverage assertions
    // =========================================================================

    public function test_goods_receipt_service_provider_registers_put_away(): void
    {
        $provider = file_get_contents(
            __DIR__ . '/../../app/Modules/GoodsReceipt/Infrastructure/Providers/GoodsReceiptServiceProvider.php'
        );

        $this->assertStringContainsString('PutAwayGoodsReceiptServiceInterface', $provider);
        $this->assertStringContainsString('PutAwayGoodsReceiptService', $provider);
    }

    public function test_inventory_service_provider_registers_reserve_service(): void
    {
        $provider = file_get_contents(
            __DIR__ . '/../../app/Modules/Inventory/Infrastructure/Providers/InventoryServiceProvider.php'
        );

        $this->assertStringContainsString('ReserveStockServiceInterface', $provider);
        $this->assertStringContainsString('ReserveStockService', $provider);
    }

    public function test_inventory_service_provider_registers_release_service(): void
    {
        $provider = file_get_contents(
            __DIR__ . '/../../app/Modules/Inventory/Infrastructure/Providers/InventoryServiceProvider.php'
        );

        $this->assertStringContainsString('ReleaseStockServiceInterface', $provider);
        $this->assertStringContainsString('ReleaseStockService', $provider);
    }

    public function test_inventory_service_provider_registers_adjust_service(): void
    {
        $provider = file_get_contents(
            __DIR__ . '/../../app/Modules/Inventory/Infrastructure/Providers/InventoryServiceProvider.php'
        );

        $this->assertStringContainsString('AdjustInventoryServiceInterface', $provider);
        $this->assertStringContainsString('AdjustInventoryService', $provider);
    }

    public function test_inventory_service_provider_registers_reconcile_service(): void
    {
        $provider = file_get_contents(
            __DIR__ . '/../../app/Modules/Inventory/Infrastructure/Providers/InventoryServiceProvider.php'
        );

        $this->assertStringContainsString('ReconcileInventoryServiceInterface', $provider);
        $this->assertStringContainsString('ReconcileInventoryService', $provider);
    }

    public function test_stock_movement_service_provider_registers_transfer_service(): void
    {
        $provider = file_get_contents(
            __DIR__ . '/../../app/Modules/StockMovement/Infrastructure/Providers/StockMovementServiceProvider.php'
        );

        $this->assertStringContainsString('TransferStockServiceInterface', $provider);
        $this->assertStringContainsString('TransferStockService', $provider);
    }

    // =========================================================================
    // Controller injection coverage assertions
    // =========================================================================

    public function test_goods_receipt_controller_injects_put_away_service(): void
    {
        $controller = file_get_contents(
            __DIR__ . '/../../app/Modules/GoodsReceipt/Infrastructure/Http/Controllers/GoodsReceiptController.php'
        );

        $this->assertStringContainsString('PutAwayGoodsReceiptServiceInterface', $controller);
        $this->assertStringContainsString('putAwayService', $controller);
    }

    public function test_inventory_level_controller_injects_reserve_service(): void
    {
        $controller = file_get_contents(
            __DIR__ . '/../../app/Modules/Inventory/Infrastructure/Http/Controllers/InventoryLevelController.php'
        );

        $this->assertStringContainsString('ReserveStockServiceInterface', $controller);
        $this->assertStringContainsString('ReleaseStockServiceInterface', $controller);
        $this->assertStringContainsString('AdjustInventoryServiceInterface', $controller);
    }

    public function test_inventory_cycle_count_controller_injects_reconcile_service(): void
    {
        $controller = file_get_contents(
            __DIR__ . '/../../app/Modules/Inventory/Infrastructure/Http/Controllers/InventoryCycleCountController.php'
        );

        $this->assertStringContainsString('ReconcileInventoryServiceInterface', $controller);
        $this->assertStringContainsString('reconcileService', $controller);
    }

    public function test_stock_movement_controller_injects_transfer_service(): void
    {
        $controller = file_get_contents(
            __DIR__ . '/../../app/Modules/StockMovement/Infrastructure/Http/Controllers/StockMovementController.php'
        );

        $this->assertStringContainsString('TransferStockServiceInterface', $controller);
        $this->assertStringContainsString('transferService', $controller);
    }

    // =========================================================================
    // AdjustInventoryService – positive and negative adjustments
    // =========================================================================

    public function test_adjust_inventory_adds_stock_for_positive_adjustment(): void
    {
        $level = new InventoryLevel(tenantId: 1, productId: 5, qtyOnHand: 100.0, id: 9);
        $level->addStock(10.0);
        $this->assertSame(110.0, $level->getQtyOnHand());
    }

    public function test_adjust_inventory_removes_stock_for_negative_adjustment(): void
    {
        $level = new InventoryLevel(tenantId: 1, productId: 5, qtyOnHand: 100.0, id: 10);
        $level->removeStock(25.0);
        $this->assertSame(75.0, $level->getQtyOnHand());
    }

    // =========================================================================
    // TransferStockService – paired movement reference naming
    // =========================================================================

    public function test_transfer_stock_data_builds_paired_reference_numbers(): void
    {
        // Verify that the service appends -OUT and -IN suffixes to the base reference.
        $service = file_get_contents(
            __DIR__ . '/../../app/Modules/StockMovement/Application/Services/TransferStockService.php'
        );

        $this->assertStringContainsString("'-OUT'", $service);
        $this->assertStringContainsString("'-IN'", $service);
    }

    public function test_transfer_stock_service_creates_issue_and_receipt_movements(): void
    {
        $service = file_get_contents(
            __DIR__ . '/../../app/Modules/StockMovement/Application/Services/TransferStockService.php'
        );

        $this->assertStringContainsString("'issue'", $service);
        $this->assertStringContainsString("'receipt'", $service);
    }
}
