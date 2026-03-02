<?php

declare(strict_types=1);

namespace Modules\Warehouse\Tests\Unit;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Modules\Warehouse\Application\Services\WarehouseService;
use Modules\Warehouse\Domain\Contracts\WarehouseRepositoryContract;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for WarehouseService CRUD methods.
 *
 * Verifies method existence, visibility, parameter signatures, and delegation
 * for showPickingOrder, listPickingOrders, and completePickingOrder.
 * No database or Laravel bootstrap required.
 */
class WarehouseServiceCrudTest extends TestCase
{
    // -------------------------------------------------------------------------
    // showPickingOrder
    // -------------------------------------------------------------------------

    public function test_warehouse_service_has_show_picking_order_method(): void
    {
        $this->assertTrue(
            method_exists(WarehouseService::class, 'showPickingOrder'),
            'WarehouseService must expose a public showPickingOrder() method.'
        );
    }

    public function test_show_picking_order_is_public(): void
    {
        $reflection = new \ReflectionMethod(WarehouseService::class, 'showPickingOrder');

        $this->assertTrue($reflection->isPublic());
    }

    public function test_show_picking_order_accepts_id_parameter(): void
    {
        $reflection = new \ReflectionMethod(WarehouseService::class, 'showPickingOrder');
        $params     = $reflection->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('id', $params[0]->getName());
    }

    public function test_show_picking_order_returns_model(): void
    {
        $reflection = new \ReflectionMethod(WarehouseService::class, 'showPickingOrder');
        $returnType = (string) $reflection->getReturnType();

        $this->assertSame(\Illuminate\Database\Eloquent\Model::class, $returnType);
    }

    public function test_show_picking_order_delegates_to_repository_find_or_fail(): void
    {
        $expected = $this->createMock(Model::class);
        $repo     = $this->createMock(WarehouseRepositoryContract::class);
        $repo->expects($this->once())
            ->method('findOrFail')
            ->with(12)
            ->willReturn($expected);

        $service = new WarehouseService($repo);
        $result  = $service->showPickingOrder(12);

        $this->assertSame($expected, $result);
    }

    // -------------------------------------------------------------------------
    // listPickingOrders
    // -------------------------------------------------------------------------

    public function test_warehouse_service_has_list_picking_orders_method(): void
    {
        $this->assertTrue(
            method_exists(WarehouseService::class, 'listPickingOrders'),
            'WarehouseService must expose a public listPickingOrders() method.'
        );
    }

    public function test_list_picking_orders_is_public(): void
    {
        $reflection = new \ReflectionMethod(WarehouseService::class, 'listPickingOrders');

        $this->assertTrue($reflection->isPublic());
    }

    public function test_list_picking_orders_accepts_no_parameters(): void
    {
        $reflection = new \ReflectionMethod(WarehouseService::class, 'listPickingOrders');

        $this->assertCount(0, $reflection->getParameters());
    }

    public function test_list_picking_orders_delegates_to_repository_all(): void
    {
        $expected = new Collection();
        $repo     = $this->createMock(WarehouseRepositoryContract::class);
        $repo->expects($this->once())
            ->method('all')
            ->willReturn($expected);

        $service = new WarehouseService($repo);
        $result  = $service->listPickingOrders();

        $this->assertSame($expected, $result);
    }

    // -------------------------------------------------------------------------
    // completePickingOrder
    // -------------------------------------------------------------------------

    public function test_warehouse_service_has_complete_picking_order_method(): void
    {
        $this->assertTrue(
            method_exists(WarehouseService::class, 'completePickingOrder'),
            'WarehouseService must expose a public completePickingOrder() method.'
        );
    }

    public function test_complete_picking_order_is_public(): void
    {
        $reflection = new \ReflectionMethod(WarehouseService::class, 'completePickingOrder');

        $this->assertTrue($reflection->isPublic());
    }

    public function test_complete_picking_order_accepts_id_parameter(): void
    {
        $reflection = new \ReflectionMethod(WarehouseService::class, 'completePickingOrder');
        $params     = $reflection->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('id', $params[0]->getName());
    }

    public function test_complete_picking_order_returns_model(): void
    {
        $reflection = new \ReflectionMethod(WarehouseService::class, 'completePickingOrder');
        $returnType = (string) $reflection->getReturnType();

        $this->assertSame(\Illuminate\Database\Eloquent\Model::class, $returnType);
    }
}
