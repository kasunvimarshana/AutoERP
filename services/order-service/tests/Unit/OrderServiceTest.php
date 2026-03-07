<?php

namespace Tests\Unit;

use App\Modules\Order\DTOs\CreateOrderDTO;
use App\Modules\Order\Events\OrderCreated;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Models\OrderItem;
use App\Modules\Order\Repositories\Interfaces\OrderRepositoryInterface;
use App\Modules\Order\Services\OrderService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Mockery;
use Tests\TestCase;

class OrderServiceTest extends TestCase
{
    private OrderRepositoryInterface $repository;
    private OrderService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = Mockery::mock(OrderRepositoryInterface::class);
        $this->service = new OrderService($this->repository);
    }

    public function test_get_order_throws_exception_when_not_found(): void
    {
        $this->repository->shouldReceive('findById')->with(999)->andReturn(null);

        $this->expectException(ModelNotFoundException::class);

        $this->service->getOrder(999);
    }

    public function test_get_order_returns_order_when_found(): void
    {
        $order = new Order(['id' => 1, 'order_number' => 'ORD-TEST-001', 'status' => 'pending']);
        $this->repository->shouldReceive('findById')->with(1)->andReturn($order);

        $result = $this->service->getOrder(1);

        $this->assertSame($order, $result);
    }

    public function test_cancel_order_throws_exception_when_not_cancellable(): void
    {
        $order = new Order([
            'id' => 1,
            'order_number' => 'ORD-TEST-001',
            'status' => Order::STATUS_SHIPPED,
        ]);

        $this->repository->shouldReceive('findById')->with(1)->andReturn($order);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Order cannot be cancelled');

        $this->service->cancelOrder(1);
    }

    public function test_update_status_throws_exception_for_invalid_status(): void
    {
        $order = new Order(['id' => 1, 'status' => Order::STATUS_PENDING]);
        $this->repository->shouldReceive('findById')->with(1)->andReturn($order);

        DB::shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid status');

        $this->service->updateStatus(1, 'invalid_status');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
