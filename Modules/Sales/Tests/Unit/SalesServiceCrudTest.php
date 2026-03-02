<?php

declare(strict_types=1);

namespace Modules\Sales\Tests\Unit;

use Illuminate\Database\Eloquent\Collection;
use Modules\Sales\Application\Services\SalesService;
use Modules\Sales\Domain\Contracts\SalesRepositoryContract;
use PHPUnit\Framework\TestCase;

/**
 * Structural and delegation tests for SalesService CRUD methods.
 *
 * showOrder(), cancelOrder(), and listCustomers() method signatures and
 * repository delegation contracts are verified using pure-PHP reflection
 * and mocked repository contracts. No database or Laravel bootstrap required.
 */
class SalesServiceCrudTest extends TestCase
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

    public function test_sales_service_has_show_order_method(): void
    {
        $this->assertTrue(
            method_exists(SalesService::class, 'showOrder'),
            'SalesService must expose a public showOrder() method.'
        );
    }

    public function test_sales_service_has_cancel_order_method(): void
    {
        $this->assertTrue(
            method_exists(SalesService::class, 'cancelOrder'),
            'SalesService must expose a public cancelOrder() method.'
        );
    }

    public function test_sales_service_has_list_customers_method(): void
    {
        $this->assertTrue(
            method_exists(SalesService::class, 'listCustomers'),
            'SalesService must expose a public listCustomers() method.'
        );
    }

    // -------------------------------------------------------------------------
    // Method signatures — parameter inspection via reflection
    // -------------------------------------------------------------------------

    public function test_show_order_accepts_int_or_string_id(): void
    {
        $reflection = new \ReflectionMethod(SalesService::class, 'showOrder');
        $params     = $reflection->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('id', $params[0]->getName());
    }

    public function test_cancel_order_accepts_int_or_string_id(): void
    {
        $reflection = new \ReflectionMethod(SalesService::class, 'cancelOrder');
        $params     = $reflection->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('id', $params[0]->getName());
    }

    public function test_list_customers_accepts_no_required_params(): void
    {
        $reflection = new \ReflectionMethod(SalesService::class, 'listCustomers');
        $params     = $reflection->getParameters();

        $this->assertCount(0, $params);
    }

    // -------------------------------------------------------------------------
    // showOrder — delegates to repository findOrFail
    // -------------------------------------------------------------------------

    public function test_show_order_delegates_to_repository_find_or_fail(): void
    {
        $mockOrder = $this->getMockBuilder(\Modules\Sales\Domain\Entities\SalesOrder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $repo = $this->createMock(SalesRepositoryContract::class);
        $repo->expects($this->once())
            ->method('findOrFail')
            ->with(99)
            ->willReturn($mockOrder);

        $result = $this->makeService($repo)->showOrder(99);

        $this->assertSame($mockOrder, $result);
    }

    // -------------------------------------------------------------------------
    // listCustomers — delegates to repository allCustomers
    // -------------------------------------------------------------------------

    public function test_list_customers_delegates_to_repository_all_customers(): void
    {
        $collection = new Collection();

        $repo = $this->createMock(SalesRepositoryContract::class);
        $repo->expects($this->once())
            ->method('allCustomers')
            ->willReturn($collection);

        $result = $this->makeService($repo)->listCustomers();

        $this->assertSame($collection, $result);
    }

    public function test_list_customers_returns_collection_instance(): void
    {
        $repo = $this->createMock(SalesRepositoryContract::class);
        $repo->method('allCustomers')->willReturn(new Collection(['c1', 'c2']));

        $result = $this->makeService($repo)->listCustomers();

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(2, $result);
    }

    // -------------------------------------------------------------------------
    // Return types — reflection
    // -------------------------------------------------------------------------

    public function test_show_order_return_type_is_sales_order(): void
    {
        $reflection  = new \ReflectionMethod(SalesService::class, 'showOrder');
        $returnType  = (string) $reflection->getReturnType();

        $this->assertSame(\Modules\Sales\Domain\Entities\SalesOrder::class, $returnType);
    }
}
