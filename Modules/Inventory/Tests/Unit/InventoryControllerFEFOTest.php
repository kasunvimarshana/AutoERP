<?php

declare(strict_types=1);

namespace Modules\Inventory\Tests\Unit;

use Modules\Inventory\Application\Services\InventoryService;
use Modules\Inventory\Interfaces\Http\Controllers\InventoryController;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * Unit tests for InventoryController::getStockByFEFO() endpoint.
 *
 * Validates controller and service method signatures for the FEFO
 * (First-Expired, First-Out) pharmaceutical compliance endpoint.
 * No database or Laravel bootstrap required.
 */
class InventoryControllerFEFOTest extends TestCase
{
    public function test_controller_has_get_stock_by_fefo_method(): void
    {
        $this->assertTrue(
            method_exists(InventoryController::class, 'getStockByFEFO'),
            'InventoryController must expose a public getStockByFEFO() method.'
        );
    }

    public function test_controller_get_stock_by_fefo_is_public(): void
    {
        $ref = new ReflectionMethod(InventoryController::class, 'getStockByFEFO');
        $this->assertTrue($ref->isPublic());
    }

    public function test_controller_get_stock_by_fefo_accepts_product_and_warehouse_ids(): void
    {
        $ref    = new ReflectionMethod(InventoryController::class, 'getStockByFEFO');
        $params = $ref->getParameters();

        $this->assertCount(2, $params);
        $this->assertSame('productId', $params[0]->getName());
        $this->assertSame('int', $params[0]->getType()?->getName());
        $this->assertSame('warehouseId', $params[1]->getName());
        $this->assertSame('int', $params[1]->getType()?->getName());
    }

    public function test_controller_get_stock_by_fefo_returns_json_response(): void
    {
        $ref = new ReflectionMethod(InventoryController::class, 'getStockByFEFO');
        $this->assertSame(
            \Illuminate\Http\JsonResponse::class,
            $ref->getReturnType()?->getName()
        );
    }

    public function test_service_has_get_stock_by_fefo_method(): void
    {
        $this->assertTrue(
            method_exists(InventoryService::class, 'getStockByFEFO'),
            'InventoryService must expose a public getStockByFEFO() method.'
        );
    }
}
