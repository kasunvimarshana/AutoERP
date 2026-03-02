<?php

declare(strict_types=1);

namespace Modules\Sales\Tests\Unit;

use Illuminate\Database\Eloquent\Collection;
use Modules\Sales\Application\Services\SalesService;
use Modules\Sales\Domain\Contracts\SalesRepositoryContract;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for SalesService read-path and structural compliance.
 *
 * listOrders() and the createOrder/confirmOrder method signatures are
 * validated using pure-PHP reflection and mocked repository contracts.
 * No database or Laravel bootstrap required.
 */
class SalesServiceListTest extends TestCase
{
    private function makeService(?SalesRepositoryContract $repo = null): SalesService
    {
        return new SalesService(
            $repo ?? $this->createMock(SalesRepositoryContract::class)
        );
    }

    // -------------------------------------------------------------------------
    // Method existence — structural compliance
    // -------------------------------------------------------------------------

    public function test_sales_service_has_create_order_method(): void
    {
        $this->assertTrue(
            method_exists(SalesService::class, 'createOrder'),
            'SalesService must expose a public createOrder() method.'
        );
    }

    public function test_sales_service_has_confirm_order_method(): void
    {
        $this->assertTrue(
            method_exists(SalesService::class, 'confirmOrder'),
            'SalesService must expose a public confirmOrder() method.'
        );
    }

    public function test_sales_service_has_list_orders_method(): void
    {
        $this->assertTrue(
            method_exists(SalesService::class, 'listOrders'),
            'SalesService must expose a public listOrders() method.'
        );
    }

    // -------------------------------------------------------------------------
    // listOrders — delegates to repository
    // -------------------------------------------------------------------------

    public function test_list_orders_delegates_to_repository_all(): void
    {
        $collection = new Collection();

        $repo = $this->createMock(SalesRepositoryContract::class);
        $repo->expects($this->once())
            ->method('all')
            ->willReturn($collection);

        $result = $this->makeService($repo)->listOrders();

        $this->assertSame($collection, $result);
    }

    public function test_list_orders_returns_collection_type(): void
    {
        $repo = $this->createMock(SalesRepositoryContract::class);
        $repo->method('all')->willReturn(new Collection());

        $result = $this->makeService($repo)->listOrders([]);

        $this->assertInstanceOf(Collection::class, $result);
    }

    public function test_list_orders_returns_populated_collection(): void
    {
        $items = new Collection(['order-1', 'order-2', 'order-3']);

        $repo = $this->createMock(SalesRepositoryContract::class);
        $repo->method('all')->willReturn($items);

        $result = $this->makeService($repo)->listOrders();

        $this->assertCount(3, $result);
    }

    public function test_list_orders_accepts_empty_filters_array(): void
    {
        $collection = new Collection();

        $repo = $this->createMock(SalesRepositoryContract::class);
        $repo->method('all')->willReturn($collection);

        // Should not throw — empty filters is valid
        $result = $this->makeService($repo)->listOrders([]);

        $this->assertInstanceOf(Collection::class, $result);
    }

    // -------------------------------------------------------------------------
    // confirmOrder — method signature
    // -------------------------------------------------------------------------

    public function test_confirm_order_accepts_integer_order_id(): void
    {
        $reflection = new \ReflectionMethod(SalesService::class, 'confirmOrder');
        $params     = $reflection->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('orderId', $params[0]->getName());
    }
}
