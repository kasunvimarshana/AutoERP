<?php

declare(strict_types=1);

namespace Modules\Sales\Tests\Unit\Listeners;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Mockery;
use Modules\Inventory\Repositories\Contracts\StockMovementRepositoryInterface;
use Modules\Sales\Events\SalesOrderConfirmed;
use Modules\Sales\Listeners\ReserveStockListener;
use Tests\TestCase;

class ReserveStockListenerTest extends TestCase
{
    private $stockMovementRepository;

    private $listener;

    protected function setUp(): void
    {
        parent::setUp();

        $this->stockMovementRepository = Mockery::mock(StockMovementRepositoryInterface::class);

        $this->listener = new ReserveStockListener(
            $this->stockMovementRepository
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_it_creates_stock_movement_when_sales_order_confirmed_event_fires(): void
    {
        // Arrange
        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('commit')->once();
        Log::shouldReceive('info')->once();

        $orderLine = Mockery::mock();
        $orderLine->product_id = 'product-uuid-123';
        $orderLine->quantity = 10;

        $salesOrder = Mockery::mock('Modules\Sales\Entities\SalesOrder')->shouldIgnoreMissing();
        $salesOrder->id = 'order-uuid-123';
        $salesOrder->tenant_id = 'tenant-uuid-123';
        $salesOrder->order_number = 'SO-20240209-001';
        $salesOrder->warehouse_id = 'warehouse-uuid-123';
        $salesOrder->shouldReceive('getAttribute')->with('lines')->andReturn(collect([$orderLine]));

        $stockMovement = Mockery::mock();
        $stockMovement->id = 'movement-uuid-123';

        $this->stockMovementRepository
            ->shouldReceive('create')
            ->once()
            ->withArgs(function ($data) use ($salesOrder, $orderLine) {
                return $data['tenant_id'] === $salesOrder->tenant_id
                    && $data['product_id'] === $orderLine->product_id
                    && $data['warehouse_id'] === $salesOrder->warehouse_id
                    && $data['quantity'] === -$orderLine->quantity
                    && $data['movement_type'] === 'RESERVE'
                    && $data['reference_type'] === 'sales_order'
                    && $data['reference_id'] === $salesOrder->id
                    && $data['reference_number'] === $salesOrder->order_number
                    && $data['status'] === 'completed';
            })
            ->andReturn($stockMovement);

        $event = new SalesOrderConfirmed($salesOrder);

        // Act
        $this->listener->handle($event);

        // Assert
        $this->assertTrue(true);
    }

    public function test_it_handles_multiple_order_lines_correctly(): void
    {
        // Arrange
        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('commit')->once();
        Log::shouldReceive('info')->once();

        $orderLine1 = Mockery::mock();
        $orderLine1->product_id = 'product-uuid-1';
        $orderLine1->quantity = 5;

        $orderLine2 = Mockery::mock();
        $orderLine2->product_id = 'product-uuid-2';
        $orderLine2->quantity = 3;

        $orderLine3 = Mockery::mock();
        $orderLine3->product_id = 'product-uuid-3';
        $orderLine3->quantity = 7;

        $salesOrder = Mockery::mock('Modules\Sales\Entities\SalesOrder')->shouldIgnoreMissing();
        $salesOrder->id = 'order-uuid-123';
        $salesOrder->tenant_id = 'tenant-uuid-123';
        $salesOrder->order_number = 'SO-20240209-001';
        $salesOrder->warehouse_id = 'warehouse-uuid-123';
        $salesOrder->shouldReceive('getAttribute')->with('lines')->andReturn(collect([
            $orderLine1,
            $orderLine2,
            $orderLine3,
        ]));

        // Expect three stock movements to be created
        $this->stockMovementRepository
            ->shouldReceive('create')
            ->times(3)
            ->andReturn(Mockery::mock());

        $event = new SalesOrderConfirmed($salesOrder);

        // Act
        $this->listener->handle($event);

        // Assert
        $this->assertTrue(true);
    }

    public function test_it_skips_order_lines_without_product_id(): void
    {
        // Arrange
        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('commit')->once();
        Log::shouldReceive('info')->once();

        $orderLine1 = Mockery::mock();
        $orderLine1->product_id = 'product-uuid-1';
        $orderLine1->quantity = 5;

        $orderLine2 = Mockery::mock();
        $orderLine2->product_id = null; // No product ID
        $orderLine2->quantity = 3;

        $orderLine3 = Mockery::mock();
        $orderLine3->product_id = 'product-uuid-3';
        $orderLine3->quantity = 7;

        $salesOrder = Mockery::mock('Modules\Sales\Entities\SalesOrder')->shouldIgnoreMissing();
        $salesOrder->id = 'order-uuid-123';
        $salesOrder->tenant_id = 'tenant-uuid-123';
        $salesOrder->order_number = 'SO-20240209-001';
        $salesOrder->warehouse_id = 'warehouse-uuid-123';
        $salesOrder->shouldReceive('getAttribute')->with('lines')->andReturn(collect([
            $orderLine1,
            $orderLine2,
            $orderLine3,
        ]));

        // Expect only two stock movements (line2 skipped)
        $this->stockMovementRepository
            ->shouldReceive('create')
            ->times(2)
            ->withArgs(function ($data) {
                return $data['product_id'] !== null;
            })
            ->andReturn(Mockery::mock());

        $event = new SalesOrderConfirmed($salesOrder);

        // Act
        $this->listener->handle($event);

        // Assert
        $this->assertTrue(true);
    }

    public function test_it_reserves_stock_with_negative_quantity(): void
    {
        // Arrange
        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('commit')->once();
        Log::shouldReceive('info')->once();

        $orderLine = Mockery::mock();
        $orderLine->product_id = 'product-uuid-123';
        $orderLine->quantity = 10;

        $salesOrder = Mockery::mock('Modules\Sales\Entities\SalesOrder')->shouldIgnoreMissing();
        $salesOrder->id = 'order-uuid-123';
        $salesOrder->tenant_id = 'tenant-uuid-123';
        $salesOrder->order_number = 'SO-20240209-001';
        $salesOrder->warehouse_id = 'warehouse-uuid-123';
        $salesOrder->shouldReceive('getAttribute')->with('lines')->andReturn(collect([$orderLine]));

        $this->stockMovementRepository
            ->shouldReceive('create')
            ->once()
            ->withArgs(function ($data) use ($orderLine) {
                // Verify quantity is negative for reservation
                return $data['quantity'] === -$orderLine->quantity;
            })
            ->andReturn(Mockery::mock());

        $event = new SalesOrderConfirmed($salesOrder);

        // Act
        $this->listener->handle($event);

        // Assert
        $this->assertTrue(true);
    }

    public function test_it_rolls_back_transaction_on_failure(): void
    {
        // Arrange
        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('rollBack')->once();
        DB::shouldReceive('commit')->never();
        Log::shouldReceive('error')->once();

        $orderLine = Mockery::mock();
        $orderLine->product_id = 'product-uuid-123';
        $orderLine->quantity = 10;

        $salesOrder = Mockery::mock('Modules\Sales\Entities\SalesOrder')->shouldIgnoreMissing();
        $salesOrder->id = 'order-uuid-123';
        $salesOrder->tenant_id = 'tenant-uuid-123';
        $salesOrder->order_number = 'SO-20240209-001';
        $salesOrder->warehouse_id = 'warehouse-uuid-123';
        $salesOrder->shouldReceive('getAttribute')->with('lines')->andReturn(collect([$orderLine]));

        // Simulate repository failure
        $this->stockMovementRepository
            ->shouldReceive('create')
            ->once()
            ->andThrow(new \Exception('Database error'));

        $event = new SalesOrderConfirmed($salesOrder);

        // Act & Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Database error');

        $this->listener->handle($event);
    }

    public function test_it_logs_stock_reservation_success(): void
    {
        // Arrange
        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('commit')->once();

        $orderLine1 = Mockery::mock();
        $orderLine1->product_id = 'product-uuid-1';
        $orderLine1->quantity = 5;

        $orderLine2 = Mockery::mock();
        $orderLine2->product_id = 'product-uuid-2';
        $orderLine2->quantity = 3;

        $salesOrder = Mockery::mock('Modules\Sales\Entities\SalesOrder')->shouldIgnoreMissing();
        $salesOrder->id = 'order-uuid-123';
        $salesOrder->tenant_id = 'tenant-uuid-123';
        $salesOrder->order_number = 'SO-20240209-001';
        $salesOrder->warehouse_id = 'warehouse-uuid-123';

        $lines = collect([$orderLine1, $orderLine2]);
        $salesOrder->shouldReceive('getAttribute')->with('lines')->andReturn($lines);

        $this->stockMovementRepository
            ->shouldReceive('create')
            ->times(2)
            ->andReturn(Mockery::mock());

        Log::shouldReceive('info')
            ->once()
            ->withArgs(function ($message, $context) use ($salesOrder, $lines) {
                return $message === 'Stock reserved for sales order'
                    && $context['order_id'] === $salesOrder->id
                    && $context['order_number'] === $salesOrder->order_number
                    && $context['lines_count'] === $lines->count();
            });

        $event = new SalesOrderConfirmed($salesOrder);

        // Act
        $this->listener->handle($event);

        // Assert
        $this->assertTrue(true);
    }

    public function test_failed_method_logs_permanent_failure(): void
    {
        // Arrange
        $salesOrder = Mockery::mock('Modules\Sales\Entities\SalesOrder')->shouldIgnoreMissing();
        $salesOrder->id = 'order-uuid-123';

        $exception = new \Exception('Permanent failure');
        $event = new SalesOrderConfirmed($salesOrder);

        Log::shouldReceive('error')
            ->once()
            ->withArgs(function ($message, $context) use ($salesOrder, $exception) {
                return $message === 'Stock reservation failed permanently'
                    && $context['order_id'] === $salesOrder->id
                    && $context['error'] === $exception->getMessage();
            });

        // Act
        $this->listener->failed($event, $exception);

        // Assert
        $this->assertTrue(true);
    }

    public function test_it_handles_null_warehouse_id(): void
    {
        // Arrange
        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('commit')->once();
        Log::shouldReceive('info')->once();

        $orderLine = Mockery::mock();
        $orderLine->product_id = 'product-uuid-123';
        $orderLine->quantity = 10;

        $salesOrder = Mockery::mock('Modules\Sales\Entities\SalesOrder')->shouldIgnoreMissing();
        $salesOrder->id = 'order-uuid-123';
        $salesOrder->tenant_id = 'tenant-uuid-123';
        $salesOrder->order_number = 'SO-20240209-001';
        $salesOrder->warehouse_id = null; // No warehouse specified
        $salesOrder->shouldReceive('getAttribute')->with('lines')->andReturn(collect([$orderLine]));

        $this->stockMovementRepository
            ->shouldReceive('create')
            ->once()
            ->withArgs(function ($data) {
                return $data['warehouse_id'] === null;
            })
            ->andReturn(Mockery::mock());

        $event = new SalesOrderConfirmed($salesOrder);

        // Act
        $this->listener->handle($event);

        // Assert
        $this->assertTrue(true);
    }

    public function test_it_includes_reference_information_in_stock_movement(): void
    {
        // Arrange
        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('commit')->once();
        Log::shouldReceive('info')->once();

        $orderLine = Mockery::mock();
        $orderLine->product_id = 'product-uuid-123';
        $orderLine->quantity = 10;

        $salesOrder = Mockery::mock('Modules\Sales\Entities\SalesOrder')->shouldIgnoreMissing();
        $salesOrder->id = 'order-uuid-123';
        $salesOrder->tenant_id = 'tenant-uuid-123';
        $salesOrder->order_number = 'SO-20240209-001';
        $salesOrder->warehouse_id = 'warehouse-uuid-123';
        $salesOrder->shouldReceive('getAttribute')->with('lines')->andReturn(collect([$orderLine]));

        $this->stockMovementRepository
            ->shouldReceive('create')
            ->once()
            ->withArgs(function ($data) use ($salesOrder) {
                return $data['reference_type'] === 'sales_order'
                    && $data['reference_id'] === $salesOrder->id
                    && $data['reference_number'] === $salesOrder->order_number
                    && str_contains($data['notes'], $salesOrder->order_number);
            })
            ->andReturn(Mockery::mock());

        $event = new SalesOrderConfirmed($salesOrder);

        // Act
        $this->listener->handle($event);

        // Assert
        $this->assertTrue(true);
    }
}
