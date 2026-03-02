<?php

declare(strict_types=1);

namespace Modules\Inventory\Tests\Unit;

use Modules\Inventory\Application\Services\InventoryService;
use Modules\Inventory\Domain\Contracts\InventoryRepositoryContract;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * Unit tests for InventoryService::getStockByFEFO().
 *
 * FEFO (First-Expired, First-Out) is mandatory when pharmaceutical compliance
 * mode is enabled for a tenant. These structural tests verify the method
 * signature and return type without requiring a real database connection.
 */
class InventoryServiceFEFOTest extends TestCase
{
    private InventoryService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $repo          = $this->createStub(InventoryRepositoryContract::class);
        $this->service = new InventoryService($repo);
    }

    public function test_get_stock_by_fefo_method_exists(): void
    {
        $this->assertTrue(
            method_exists(InventoryService::class, 'getStockByFEFO'),
            'InventoryService must have a getStockByFEFO() method for pharmaceutical FEFO compliance.'
        );
    }

    public function test_get_stock_by_fefo_is_public(): void
    {
        $ref = new ReflectionMethod(InventoryService::class, 'getStockByFEFO');
        $this->assertTrue($ref->isPublic(), 'getStockByFEFO() must be public.');
    }

    public function test_get_stock_by_fefo_accepts_two_integer_parameters(): void
    {
        $ref    = new ReflectionMethod(InventoryService::class, 'getStockByFEFO');
        $params = $ref->getParameters();

        $this->assertCount(2, $params, 'getStockByFEFO() must accept exactly 2 parameters.');
    }

    public function test_get_stock_by_fefo_first_param_is_product_id(): void
    {
        $ref    = new ReflectionMethod(InventoryService::class, 'getStockByFEFO');
        $params = $ref->getParameters();

        $this->assertSame('productId', $params[0]->getName());
        $this->assertSame('int', $params[0]->getType()?->getName());
    }

    public function test_get_stock_by_fefo_second_param_is_warehouse_id(): void
    {
        $ref    = new ReflectionMethod(InventoryService::class, 'getStockByFEFO');
        $params = $ref->getParameters();

        $this->assertSame('warehouseId', $params[1]->getName());
        $this->assertSame('int', $params[1]->getType()?->getName());
    }

    public function test_get_stock_by_fefo_return_type_is_collection(): void
    {
        $ref        = new ReflectionMethod(InventoryService::class, 'getStockByFEFO');
        $returnType = $ref->getReturnType()?->getName();

        $this->assertSame(
            \Illuminate\Database\Eloquent\Collection::class,
            $returnType,
            'getStockByFEFO() must return an Eloquent Collection of StockItem records ordered by expiry_date.'
        );
    }

    public function test_get_stock_by_fefo_delegates_to_repository_find_by_fefo(): void
    {
        $expected = new \Illuminate\Database\Eloquent\Collection();

        $repo = $this->createMock(InventoryRepositoryContract::class);
        $repo->expects($this->once())
            ->method('findByFEFO')
            ->with(5, 3)
            ->willReturn($expected);

        $service = new InventoryService($repo);
        $result  = $service->getStockByFEFO(5, 3);

        $this->assertSame($expected, $result);
    }
}
