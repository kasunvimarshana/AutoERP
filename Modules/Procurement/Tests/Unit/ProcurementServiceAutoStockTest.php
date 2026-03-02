<?php

declare(strict_types=1);

namespace Modules\Procurement\Tests\Unit;

use Modules\Inventory\Domain\Contracts\InventoryServiceContract;
use Modules\Procurement\Application\Services\ProcurementService;
use Modules\Procurement\Domain\Contracts\ProcurementRepositoryContract;
use Modules\Procurement\Domain\Contracts\VendorBillRepositoryContract;
use Modules\Procurement\Domain\Contracts\VendorRepositoryContract;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * Unit tests for automatic stock management integration in ProcurementService.
 *
 * Validates:
 *  - ProcurementService accepts an optional InventoryServiceContract constructor arg
 *  - receiveGoods() signature is unchanged (purchaseOrderId, linesReceived)
 *  - InventoryServiceContract is correctly typed and optional
 */
class ProcurementServiceAutoStockTest extends TestCase
{
    private function makeService(
        ?ProcurementRepositoryContract $proc = null,
        ?VendorRepositoryContract $vendor = null,
        ?VendorBillRepositoryContract $bill = null,
        ?InventoryServiceContract $inventory = null,
    ): ProcurementService {
        return new ProcurementService(
            $proc     ?? $this->createStub(ProcurementRepositoryContract::class),
            $vendor   ?? $this->createStub(VendorRepositoryContract::class),
            $bill     ?? $this->createStub(VendorBillRepositoryContract::class),
            $inventory,
        );
    }

    // -------------------------------------------------------------------------
    // Constructor — optional InventoryServiceContract injection
    // -------------------------------------------------------------------------

    public function test_procurement_service_can_be_instantiated_without_inventory_service(): void
    {
        $service = $this->makeService();
        $this->assertInstanceOf(ProcurementService::class, $service);
    }

    public function test_procurement_service_can_be_instantiated_with_inventory_service(): void
    {
        $inventory = $this->createStub(InventoryServiceContract::class);
        $service   = $this->makeService(inventory: $inventory);

        $this->assertInstanceOf(ProcurementService::class, $service);
    }

    public function test_procurement_service_constructor_fourth_param_is_nullable_inventory_contract(): void
    {
        $ref    = new ReflectionMethod(ProcurementService::class, '__construct');
        $params = $ref->getParameters();

        $this->assertGreaterThanOrEqual(4, count($params));

        $inventoryParam = $params[3];
        $this->assertSame('inventoryService', $inventoryParam->getName());
        $this->assertTrue($inventoryParam->allowsNull());
        $this->assertTrue($inventoryParam->isOptional());
        $this->assertStringContainsString(
            InventoryServiceContract::class,
            (string) $inventoryParam->getType()
        );
    }

    // -------------------------------------------------------------------------
    // receiveGoods — signature unchanged (2 params)
    // -------------------------------------------------------------------------

    public function test_receive_goods_still_accepts_two_params(): void
    {
        $ref    = new ReflectionMethod(ProcurementService::class, 'receiveGoods');
        $params = $ref->getParameters();

        $this->assertCount(2, $params);
        $this->assertSame('purchaseOrderId', $params[0]->getName());
        $this->assertSame('linesReceived', $params[1]->getName());
    }

    public function test_receive_goods_is_public(): void
    {
        $this->assertTrue((new ReflectionMethod(ProcurementService::class, 'receiveGoods'))->isPublic());
    }

    // -------------------------------------------------------------------------
    // InventoryServiceContract — interface structure
    // -------------------------------------------------------------------------

    public function test_inventory_service_contract_exists(): void
    {
        $this->assertTrue(
            interface_exists(InventoryServiceContract::class),
            'InventoryServiceContract interface must be defined.'
        );
    }

    public function test_inventory_service_contract_declares_record_transaction(): void
    {
        $this->assertTrue(
            method_exists(InventoryServiceContract::class, 'recordTransaction'),
            'InventoryServiceContract must declare recordTransaction().'
        );
    }

    public function test_inventory_service_contract_declares_deduct_by_strategy(): void
    {
        $this->assertTrue(
            method_exists(InventoryServiceContract::class, 'deductByStrategy'),
            'InventoryServiceContract must declare deductByStrategy().'
        );
    }

    // -------------------------------------------------------------------------
    // InventoryService implements InventoryServiceContract
    // -------------------------------------------------------------------------

    public function test_inventory_service_implements_inventory_service_contract(): void
    {
        $this->assertTrue(
            is_subclass_of(
                \Modules\Inventory\Application\Services\InventoryService::class,
                InventoryServiceContract::class
            ),
            'InventoryService must implement InventoryServiceContract.'
        );
    }

    // -------------------------------------------------------------------------
    // Regression guard — existing public API still present
    // -------------------------------------------------------------------------

    public function test_receive_goods_method_still_present(): void
    {
        $this->assertTrue(method_exists(ProcurementService::class, 'receiveGoods'));
    }

    public function test_create_purchase_order_method_still_present(): void
    {
        $this->assertTrue(method_exists(ProcurementService::class, 'createPurchaseOrder'));
    }

    public function test_three_way_match_method_still_present(): void
    {
        $this->assertTrue(method_exists(ProcurementService::class, 'threeWayMatch'));
    }

    public function test_list_vendors_method_still_present(): void
    {
        $this->assertTrue(method_exists(ProcurementService::class, 'listVendors'));
    }
}
