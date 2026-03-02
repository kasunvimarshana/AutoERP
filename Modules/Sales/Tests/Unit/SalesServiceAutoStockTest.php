<?php

declare(strict_types=1);

namespace Modules\Sales\Tests\Unit;

use Modules\Inventory\Domain\Contracts\InventoryServiceContract;
use Modules\Sales\Application\Services\SalesService;
use Modules\Sales\Domain\Contracts\SalesRepositoryContract;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * Unit tests for automatic stock management integration in SalesService.
 *
 * Validates:
 *  - SalesService accepts an optional InventoryServiceContract constructor arg
 *  - createDelivery() signature is unchanged (orderId, data)
 *  - createReturn() method exists with correct signature
 *  - The InventoryServiceContract is correctly type-hinted
 */
class SalesServiceAutoStockTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Constructor — optional InventoryServiceContract injection
    // -------------------------------------------------------------------------

    public function test_sales_service_can_be_instantiated_without_inventory_service(): void
    {
        $repo    = $this->createStub(SalesRepositoryContract::class);
        $service = new SalesService($repo);

        $this->assertInstanceOf(SalesService::class, $service);
    }

    public function test_sales_service_can_be_instantiated_with_inventory_service(): void
    {
        $repo      = $this->createStub(SalesRepositoryContract::class);
        $inventory = $this->createStub(InventoryServiceContract::class);
        $service   = new SalesService($repo, $inventory);

        $this->assertInstanceOf(SalesService::class, $service);
    }

    public function test_sales_service_constructor_second_param_is_nullable_inventory_contract(): void
    {
        $ref    = new ReflectionMethod(SalesService::class, '__construct');
        $params = $ref->getParameters();

        $this->assertGreaterThanOrEqual(2, count($params));

        $inventoryParam = $params[1];
        $this->assertSame('inventoryService', $inventoryParam->getName());
        $this->assertTrue($inventoryParam->allowsNull());
        $this->assertTrue($inventoryParam->isOptional());
        $this->assertStringContainsString(
            InventoryServiceContract::class,
            (string) $inventoryParam->getType()
        );
    }

    // -------------------------------------------------------------------------
    // createDelivery — signature unchanged
    // -------------------------------------------------------------------------

    public function test_create_delivery_still_accepts_order_id_and_data(): void
    {
        $ref    = new ReflectionMethod(SalesService::class, 'createDelivery');
        $params = $ref->getParameters();

        $this->assertCount(2, $params);
        $this->assertSame('orderId', $params[0]->getName());
        $this->assertSame('data', $params[1]->getName());
    }

    public function test_create_delivery_is_public(): void
    {
        $this->assertTrue((new ReflectionMethod(SalesService::class, 'createDelivery'))->isPublic());
    }

    // -------------------------------------------------------------------------
    // createReturn — new method for return stock restoration
    // -------------------------------------------------------------------------

    public function test_sales_service_has_create_return_method(): void
    {
        $this->assertTrue(
            method_exists(SalesService::class, 'createReturn'),
            'SalesService must expose a public createReturn() method for return stock restoration.'
        );
    }

    public function test_create_return_is_public(): void
    {
        $this->assertTrue((new ReflectionMethod(SalesService::class, 'createReturn'))->isPublic());
    }

    public function test_create_return_accepts_order_id_and_lines(): void
    {
        $ref    = new ReflectionMethod(SalesService::class, 'createReturn');
        $params = $ref->getParameters();

        $this->assertCount(2, $params);
        $this->assertSame('orderId', $params[0]->getName());
        $this->assertSame('lines', $params[1]->getName());
    }

    public function test_create_return_return_type_is_array(): void
    {
        $ref = new ReflectionMethod(SalesService::class, 'createReturn');
        $this->assertSame('array', (string) $ref->getReturnType());
    }

    public function test_create_return_returns_empty_array_without_inventory_service(): void
    {
        $repo    = $this->createStub(SalesRepositoryContract::class);
        $service = new SalesService($repo); // no inventory service

        $result = $service->createReturn(1, []);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_create_return_returns_empty_array_for_empty_lines_without_inventory_service(): void
    {
        $repo    = $this->createStub(SalesRepositoryContract::class);
        $service = new SalesService($repo);

        $result = $service->createReturn(99, []);

        $this->assertSame([], $result);
    }

    // -------------------------------------------------------------------------
    // Regression guard — existing public API still present
    // -------------------------------------------------------------------------

    public function test_create_delivery_method_still_present(): void
    {
        $this->assertTrue(method_exists(SalesService::class, 'createDelivery'));
    }

    public function test_cancel_order_method_still_present(): void
    {
        $this->assertTrue(method_exists(SalesService::class, 'cancelOrder'));
    }

    public function test_create_order_method_still_present(): void
    {
        $this->assertTrue(method_exists(SalesService::class, 'createOrder'));
    }

    public function test_confirm_order_method_still_present(): void
    {
        $this->assertTrue(method_exists(SalesService::class, 'confirmOrder'));
    }
}
