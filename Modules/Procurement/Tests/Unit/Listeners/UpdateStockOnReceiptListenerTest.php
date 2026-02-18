<?php

declare(strict_types=1);

namespace Modules\Procurement\Tests\Unit\Listeners;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Mockery;
use Modules\Inventory\Repositories\Contracts\StockLevelRepositoryInterface;
use Modules\Inventory\Repositories\Contracts\StockMovementRepositoryInterface;
use Modules\Procurement\Events\GoodsReceived;
use Modules\Procurement\Listeners\UpdateStockOnReceiptListener;
use Tests\TestCase;

class UpdateStockOnReceiptListenerTest extends TestCase
{
    private $stockMovementRepository;

    private $stockLevelRepository;

    private $listener;

    protected function setUp(): void
    {
        parent::setUp();

        $this->stockMovementRepository = Mockery::mock(StockMovementRepositoryInterface::class);
        $this->stockLevelRepository = Mockery::mock(StockLevelRepositoryInterface::class);

        $this->listener = new UpdateStockOnReceiptListener(
            $this->stockMovementRepository,
            $this->stockLevelRepository
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_it_creates_stock_movement_when_goods_received_event_fires(): void
    {
        // Arrange
        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('commit')->once();
        Log::shouldReceive('info')->twice();

        $supplier = Mockery::mock();
        $supplier->name = 'Test Supplier';

        $line = Mockery::mock();
        $line->product_id = 'product-uuid-123';
        $line->received_quantity = 100;
        $line->unit_price = 50.00;

        $goodsReceipt = Mockery::mock();
        $goodsReceipt->id = 'receipt-uuid-123';
        $goodsReceipt->tenant_id = 'tenant-uuid-123';
        $goodsReceipt->warehouse_id = 'warehouse-uuid-123';
        $goodsReceipt->supplier_id = 'supplier-uuid-123';
        $goodsReceipt->receipt_number = 'GR-20240209-001';
        $goodsReceipt->receipt_date = now();
        $goodsReceipt->shouldReceive('getAttribute')->with('supplier')->andReturn($supplier);
        $goodsReceipt->shouldReceive('getAttribute')->with('lines')->andReturn(collect([$line]));

        $stockMovement = Mockery::mock();
        $stockMovement->id = 'movement-uuid-123';

        $this->stockMovementRepository
            ->shouldReceive('create')
            ->once()
            ->withArgs(function ($data) use ($goodsReceipt, $line) {
                return $data['tenant_id'] === $goodsReceipt->tenant_id
                    && $data['product_id'] === $line->product_id
                    && $data['warehouse_id'] === $goodsReceipt->warehouse_id
                    && $data['quantity'] === $line->received_quantity
                    && $data['unit_cost'] === $line->unit_price
                    && $data['movement_type'] === 'RECEIPT'
                    && $data['reference_type'] === 'goods_receipt'
                    && $data['reference_id'] === $goodsReceipt->id
                    && $data['status'] === 'completed';
            })
            ->andReturn($stockMovement);

        // No existing stock level
        $this->stockLevelRepository
            ->shouldReceive('findByProductAndWarehouse')
            ->once()
            ->andReturn(null);

        // Create new stock level
        $this->stockLevelRepository
            ->shouldReceive('create')
            ->once()
            ->withArgs(function ($data) use ($goodsReceipt, $line) {
                return $data['tenant_id'] === $goodsReceipt->tenant_id
                    && $data['product_id'] === $line->product_id
                    && $data['warehouse_id'] === $goodsReceipt->warehouse_id
                    && $data['quantity'] === $line->received_quantity
                    && $data['available_quantity'] === $line->received_quantity
                    && $data['reserved_quantity'] === 0
                    && $data['average_cost'] === $line->unit_price;
            })
            ->andReturn(Mockery::mock());

        $event = new GoodsReceived($goodsReceipt);

        // Act
        $this->listener->handle($event);

        // Assert
        $this->assertTrue(true);
    }

    public function test_it_updates_existing_stock_level_with_weighted_average_cost(): void
    {
        // Arrange
        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('commit')->once();
        Log::shouldReceive('info')->twice();

        $supplier = Mockery::mock();
        $supplier->name = 'Test Supplier';

        $line = Mockery::mock();
        $line->product_id = 'product-uuid-123';
        $line->received_quantity = 50;
        $line->unit_price = 120.00; // New cost

        $goodsReceipt = Mockery::mock();
        $goodsReceipt->id = 'receipt-uuid-123';
        $goodsReceipt->tenant_id = 'tenant-uuid-123';
        $goodsReceipt->warehouse_id = 'warehouse-uuid-123';
        $goodsReceipt->supplier_id = 'supplier-uuid-123';
        $goodsReceipt->receipt_number = 'GR-20240209-001';
        $goodsReceipt->receipt_date = now();
        $goodsReceipt->shouldReceive('getAttribute')->with('supplier')->andReturn($supplier);
        $goodsReceipt->shouldReceive('getAttribute')->with('lines')->andReturn(collect([$line]));

        $stockMovement = Mockery::mock();
        $stockMovement->id = 'movement-uuid-123';

        $this->stockMovementRepository
            ->shouldReceive('create')
            ->once()
            ->andReturn($stockMovement);

        // Existing stock level
        $existingStockLevel = Mockery::mock();
        $existingStockLevel->quantity = 100; // Existing quantity
        $existingStockLevel->available_quantity = 100;
        $existingStockLevel->average_cost = 100.00; // Old average cost

        $this->stockLevelRepository
            ->shouldReceive('findByProductAndWarehouse')
            ->once()
            ->andReturn($existingStockLevel);

        // Calculate weighted average cost
        // Current value: 100 * 100 = 10,000
        // New value: 50 * 120 = 6,000
        // Total quantity: 100 + 50 = 150
        // New average: (10,000 + 6,000) / 150 = 106.67
        $expectedAverageCost = 106.67;

        $this->stockLevelRepository
            ->shouldReceive('update')
            ->once()
            ->withArgs(function ($stockLevel, $data) use ($existingStockLevel, $expectedAverageCost) {
                return $stockLevel === $existingStockLevel
                    && $data['quantity'] === 150
                    && $data['available_quantity'] === 150
                    && abs($data['average_cost'] - $expectedAverageCost) < 0.01;
            })
            ->andReturn($existingStockLevel);

        $event = new GoodsReceived($goodsReceipt);

        // Act
        $this->listener->handle($event);

        // Assert
        $this->assertTrue(true);
    }

    public function test_it_handles_multiple_receipt_lines(): void
    {
        // Arrange
        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('commit')->once();
        Log::shouldReceive('info')->times(4); // 3 lines + 1 final summary

        $supplier = Mockery::mock();
        $supplier->name = 'Test Supplier';

        $line1 = Mockery::mock();
        $line1->product_id = 'product-uuid-1';
        $line1->received_quantity = 100;
        $line1->unit_price = 50.00;

        $line2 = Mockery::mock();
        $line2->product_id = 'product-uuid-2';
        $line2->received_quantity = 200;
        $line2->unit_price = 75.00;

        $line3 = Mockery::mock();
        $line3->product_id = 'product-uuid-3';
        $line3->received_quantity = 150;
        $line3->unit_price = 60.00;

        $goodsReceipt = Mockery::mock();
        $goodsReceipt->id = 'receipt-uuid-123';
        $goodsReceipt->tenant_id = 'tenant-uuid-123';
        $goodsReceipt->warehouse_id = 'warehouse-uuid-123';
        $goodsReceipt->supplier_id = 'supplier-uuid-123';
        $goodsReceipt->receipt_number = 'GR-20240209-001';
        $goodsReceipt->receipt_date = now();
        $goodsReceipt->shouldReceive('getAttribute')->with('supplier')->andReturn($supplier);

        $lines = collect([$line1, $line2, $line3]);
        $goodsReceipt->shouldReceive('getAttribute')->with('lines')->andReturn($lines);

        // Expect three stock movements
        $this->stockMovementRepository
            ->shouldReceive('create')
            ->times(3)
            ->andReturn(Mockery::mock());

        // Expect three stock level operations
        $this->stockLevelRepository
            ->shouldReceive('findByProductAndWarehouse')
            ->times(3)
            ->andReturn(null);

        $this->stockLevelRepository
            ->shouldReceive('create')
            ->times(3)
            ->andReturn(Mockery::mock());

        $event = new GoodsReceived($goodsReceipt);

        // Act
        $this->listener->handle($event);

        // Assert
        $this->assertTrue(true);
    }

    public function test_it_skips_lines_without_product_id(): void
    {
        // Arrange
        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('commit')->once();
        Log::shouldReceive('info')->times(3); // 2 valid lines + 1 summary

        $supplier = Mockery::mock();
        $supplier->name = 'Test Supplier';

        $line1 = Mockery::mock();
        $line1->product_id = 'product-uuid-1';
        $line1->received_quantity = 100;
        $line1->unit_price = 50.00;

        $line2 = Mockery::mock();
        $line2->product_id = null; // No product ID
        $line2->received_quantity = 200;
        $line2->unit_price = 75.00;

        $line3 = Mockery::mock();
        $line3->product_id = 'product-uuid-3';
        $line3->received_quantity = 150;
        $line3->unit_price = 60.00;

        $goodsReceipt = Mockery::mock();
        $goodsReceipt->id = 'receipt-uuid-123';
        $goodsReceipt->tenant_id = 'tenant-uuid-123';
        $goodsReceipt->warehouse_id = 'warehouse-uuid-123';
        $goodsReceipt->supplier_id = 'supplier-uuid-123';
        $goodsReceipt->receipt_number = 'GR-20240209-001';
        $goodsReceipt->receipt_date = now();
        $goodsReceipt->shouldReceive('getAttribute')->with('supplier')->andReturn($supplier);
        $goodsReceipt->shouldReceive('getAttribute')->with('lines')->andReturn(collect([
            $line1,
            $line2,
            $line3,
        ]));

        // Expect only two stock movements (line2 skipped)
        $this->stockMovementRepository
            ->shouldReceive('create')
            ->times(2)
            ->andReturn(Mockery::mock());

        $this->stockLevelRepository
            ->shouldReceive('findByProductAndWarehouse')
            ->times(2)
            ->andReturn(null);

        $this->stockLevelRepository
            ->shouldReceive('create')
            ->times(2)
            ->andReturn(Mockery::mock());

        $event = new GoodsReceived($goodsReceipt);

        // Act
        $this->listener->handle($event);

        // Assert
        $this->assertTrue(true);
    }

    public function test_it_handles_zero_existing_quantity_in_weighted_average(): void
    {
        // Arrange
        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('commit')->once();
        Log::shouldReceive('info')->twice();

        $supplier = Mockery::mock();
        $supplier->name = 'Test Supplier';

        $line = Mockery::mock();
        $line->product_id = 'product-uuid-123';
        $line->received_quantity = 50;
        $line->unit_price = 120.00;

        $goodsReceipt = Mockery::mock();
        $goodsReceipt->id = 'receipt-uuid-123';
        $goodsReceipt->tenant_id = 'tenant-uuid-123';
        $goodsReceipt->warehouse_id = 'warehouse-uuid-123';
        $goodsReceipt->supplier_id = 'supplier-uuid-123';
        $goodsReceipt->receipt_number = 'GR-20240209-001';
        $goodsReceipt->receipt_date = now();
        $goodsReceipt->shouldReceive('getAttribute')->with('supplier')->andReturn($supplier);
        $goodsReceipt->shouldReceive('getAttribute')->with('lines')->andReturn(collect([$line]));

        $this->stockMovementRepository
            ->shouldReceive('create')
            ->once()
            ->andReturn(Mockery::mock());

        // Existing stock level with zero quantity
        $existingStockLevel = Mockery::mock();
        $existingStockLevel->quantity = 0;
        $existingStockLevel->available_quantity = 0;
        $existingStockLevel->average_cost = 0;

        $this->stockLevelRepository
            ->shouldReceive('findByProductAndWarehouse')
            ->once()
            ->andReturn($existingStockLevel);

        // When existing quantity is 0, should use new unit cost
        $this->stockLevelRepository
            ->shouldReceive('update')
            ->once()
            ->withArgs(function ($stockLevel, $data) use ($line) {
                return $data['quantity'] === $line->received_quantity
                    && $data['average_cost'] === $line->unit_price;
            })
            ->andReturn($existingStockLevel);

        $event = new GoodsReceived($goodsReceipt);

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

        $supplier = Mockery::mock();
        $supplier->name = 'Test Supplier';

        $line = Mockery::mock();
        $line->product_id = 'product-uuid-123';
        $line->received_quantity = 100;
        $line->unit_price = 50.00;

        $goodsReceipt = Mockery::mock();
        $goodsReceipt->id = 'receipt-uuid-123';
        $goodsReceipt->tenant_id = 'tenant-uuid-123';
        $goodsReceipt->warehouse_id = 'warehouse-uuid-123';
        $goodsReceipt->shouldReceive('getAttribute')->with('supplier')->andReturn($supplier);
        $goodsReceipt->shouldReceive('getAttribute')->with('lines')->andReturn(collect([$line]));

        // Simulate repository failure
        $this->stockMovementRepository
            ->shouldReceive('create')
            ->once()
            ->andThrow(new \Exception('Database error'));

        $event = new GoodsReceived($goodsReceipt);

        // Act & Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Database error');

        $this->listener->handle($event);
    }

    public function test_it_logs_stock_update_success(): void
    {
        // Arrange
        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('commit')->once();

        $supplier = Mockery::mock();
        $supplier->name = 'Test Supplier';

        $line1 = Mockery::mock();
        $line1->product_id = 'product-uuid-1';
        $line1->received_quantity = 100;
        $line1->unit_price = 50.00;

        $line2 = Mockery::mock();
        $line2->product_id = 'product-uuid-2';
        $line2->received_quantity = 200;
        $line2->unit_price = 75.00;

        $goodsReceipt = Mockery::mock();
        $goodsReceipt->id = 'receipt-uuid-123';
        $goodsReceipt->tenant_id = 'tenant-uuid-123';
        $goodsReceipt->warehouse_id = 'warehouse-uuid-123';
        $goodsReceipt->supplier_id = 'supplier-uuid-123';
        $goodsReceipt->receipt_number = 'GR-20240209-001';
        $goodsReceipt->receipt_date = now();
        $goodsReceipt->shouldReceive('getAttribute')->with('supplier')->andReturn($supplier);

        $lines = collect([$line1, $line2]);
        $goodsReceipt->shouldReceive('getAttribute')->with('lines')->andReturn($lines);

        $this->stockMovementRepository
            ->shouldReceive('create')
            ->times(2)
            ->andReturn(Mockery::mock());

        $this->stockLevelRepository
            ->shouldReceive('findByProductAndWarehouse')
            ->times(2)
            ->andReturn(null);

        $this->stockLevelRepository
            ->shouldReceive('create')
            ->times(2)
            ->andReturn(Mockery::mock());

        Log::shouldReceive('info')
            ->times(2)
            ->with('Stock movement created for goods receipt', Mockery::any());

        Log::shouldReceive('info')
            ->once()
            ->withArgs(function ($message, $context) use ($goodsReceipt, $lines) {
                return $message === 'Stock updated for goods receipt'
                    && $context['goods_receipt_id'] === $goodsReceipt->id
                    && $context['receipt_number'] === $goodsReceipt->receipt_number
                    && $context['supplier_id'] === $goodsReceipt->supplier_id
                    && $context['lines_count'] === $lines->count();
            });

        $event = new GoodsReceived($goodsReceipt);

        // Act
        $this->listener->handle($event);

        // Assert
        $this->assertTrue(true);
    }

    public function test_failed_method_logs_permanent_failure(): void
    {
        // Arrange
        $goodsReceipt = Mockery::mock();
        $goodsReceipt->id = 'receipt-uuid-123';

        $exception = new \Exception('Permanent failure');
        $event = new GoodsReceived($goodsReceipt);

        Log::shouldReceive('error')
            ->once()
            ->withArgs(function ($message, $context) use ($goodsReceipt, $exception) {
                return $message === 'Stock update for goods receipt failed permanently'
                    && $context['goods_receipt_id'] === $goodsReceipt->id
                    && $context['error'] === $exception->getMessage();
            });

        // Act
        $this->listener->failed($event, $exception);

        // Assert
        $this->assertTrue(true);
    }

    public function test_it_includes_supplier_name_in_notes(): void
    {
        // Arrange
        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('commit')->once();
        Log::shouldReceive('info')->twice();

        $supplier = Mockery::mock();
        $supplier->name = 'ACME Corporation';

        $line = Mockery::mock();
        $line->product_id = 'product-uuid-123';
        $line->received_quantity = 100;
        $line->unit_price = 50.00;

        $goodsReceipt = Mockery::mock();
        $goodsReceipt->id = 'receipt-uuid-123';
        $goodsReceipt->tenant_id = 'tenant-uuid-123';
        $goodsReceipt->warehouse_id = 'warehouse-uuid-123';
        $goodsReceipt->supplier_id = 'supplier-uuid-123';
        $goodsReceipt->receipt_number = 'GR-20240209-001';
        $goodsReceipt->receipt_date = now();
        $goodsReceipt->shouldReceive('getAttribute')->with('supplier')->andReturn($supplier);
        $goodsReceipt->shouldReceive('getAttribute')->with('lines')->andReturn(collect([$line]));

        $this->stockMovementRepository
            ->shouldReceive('create')
            ->once()
            ->withArgs(function ($data) use ($supplier) {
                return str_contains($data['notes'], $supplier->name);
            })
            ->andReturn(Mockery::mock());

        $this->stockLevelRepository
            ->shouldReceive('findByProductAndWarehouse')
            ->once()
            ->andReturn(null);

        $this->stockLevelRepository
            ->shouldReceive('create')
            ->once()
            ->andReturn(Mockery::mock());

        $event = new GoodsReceived($goodsReceipt);

        // Act
        $this->listener->handle($event);

        // Assert
        $this->assertTrue(true);
    }
}
