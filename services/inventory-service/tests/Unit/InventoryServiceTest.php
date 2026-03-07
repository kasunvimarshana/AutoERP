<?php

namespace Tests\Unit;

use App\DTOs\StockAdjustmentDTO;
use App\Events\InventoryUpdated;
use App\Events\LowStockDetected;
use App\Events\StockReserved;
use App\Exceptions\InsufficientStockException;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Repositories\Interfaces\InventoryRepositoryInterface;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Mockery;
use Tests\TestCase;

class InventoryServiceTest extends TestCase
{
    use RefreshDatabase;

    private InventoryService $service;

    protected function setUp(): void
    {
        parent::setUp();

        // Use the real repository backed by the in-memory DB
        $repo = $this->app->make(InventoryRepositoryInterface::class);
        $this->service = new InventoryService($repo);
    }

    /*
    |--------------------------------------------------------------------------
    | adjustStock
    |--------------------------------------------------------------------------
    */

    public function test_adjust_stock_add_increases_quantity(): void
    {
        $item = $this->makeItem(quantity: 100);
        $dto  = new StockAdjustmentDTO('add', 50, 'Test add');

        $updated = $this->service->adjustStock($item, $dto);

        $this->assertEquals(150, $updated->quantity);
    }

    public function test_adjust_stock_subtract_decreases_quantity(): void
    {
        $item = $this->makeItem(quantity: 80);
        $dto  = new StockAdjustmentDTO('subtract', 30, 'Test subtract');

        $updated = $this->service->adjustStock($item, $dto);

        $this->assertEquals(50, $updated->quantity);
    }

    public function test_adjust_stock_set_overrides_quantity(): void
    {
        $item = $this->makeItem(quantity: 50);
        $dto  = new StockAdjustmentDTO('set', 200, 'Physical count');

        $updated = $this->service->adjustStock($item, $dto);

        $this->assertEquals(200, $updated->quantity);
    }

    public function test_adjust_stock_subtract_below_zero_throws_exception(): void
    {
        $item = $this->makeItem(quantity: 10);
        $dto  = new StockAdjustmentDTO('subtract', 100, 'Should fail');

        $this->expectException(InsufficientStockException::class);

        $this->service->adjustStock($item, $dto);
    }

    public function test_adjust_stock_creates_audit_transaction(): void
    {
        $item = $this->makeItem(quantity: 50);
        $dto  = new StockAdjustmentDTO('add', 25, 'Audit test');

        $this->service->adjustStock($item, $dto);

        $this->assertDatabaseHas('inventory_transactions', [
            'inventory_item_id' => $item->id,
            'type'              => 'add',
            'quantity_before'   => 50,
            'quantity_change'   => 25,
            'quantity_after'    => 75,
        ]);
    }

    public function test_adjust_stock_fires_inventory_updated_event(): void
    {
        Event::fake([InventoryUpdated::class]);

        $item = $this->makeItem(quantity: 50);
        $dto  = new StockAdjustmentDTO('add', 10, 'Event test');

        $this->service->adjustStock($item, $dto);

        Event::assertDispatched(InventoryUpdated::class);
    }

    public function test_adjust_stock_fires_low_stock_event_when_threshold_breached(): void
    {
        Event::fake([LowStockDetected::class, InventoryUpdated::class]);

        $item = $this->makeItem(quantity: 15, reorderPoint: 10);
        $dto  = new StockAdjustmentDTO('subtract', 10, 'Low stock test');

        $this->service->adjustStock($item, $dto);

        // After subtract: quantity = 5 <= reorder_point = 10
        Event::assertDispatched(LowStockDetected::class);
    }

    public function test_no_low_stock_event_when_above_threshold(): void
    {
        Event::fake([LowStockDetected::class]);

        $item = $this->makeItem(quantity: 100, reorderPoint: 10);
        $dto  = new StockAdjustmentDTO('subtract', 5, 'Still plenty');

        $this->service->adjustStock($item, $dto);

        Event::assertNotDispatched(LowStockDetected::class);
    }

    /*
    |--------------------------------------------------------------------------
    | reserveStock
    |--------------------------------------------------------------------------
    */

    public function test_reserve_stock_increments_reserved_quantity(): void
    {
        $item = $this->makeItem(quantity: 100, reserved: 0);

        $updated = $this->service->reserveStock($item, 30, 'Order reservation');

        $this->assertEquals(30, $updated->reserved_quantity);
        $this->assertEquals(70, $updated->available_quantity);
    }

    public function test_reserve_stock_fires_stock_reserved_event(): void
    {
        Event::fake([StockReserved::class]);

        $item = $this->makeItem(quantity: 100);
        $this->service->reserveStock($item, 10, 'Event test');

        Event::assertDispatched(StockReserved::class);
    }

    public function test_reserve_stock_throws_when_insufficient_available(): void
    {
        $item = $this->makeItem(quantity: 50, reserved: 45);
        // available = 5

        $this->expectException(InsufficientStockException::class);

        $this->service->reserveStock($item, 10, 'Should fail');
    }

    /*
    |--------------------------------------------------------------------------
    | releaseStock
    |--------------------------------------------------------------------------
    */

    public function test_release_stock_decrements_reserved_quantity(): void
    {
        $item = $this->makeItem(quantity: 100, reserved: 40);

        $updated = $this->service->releaseStock($item, 20, 'Order cancelled');

        $this->assertEquals(20, $updated->reserved_quantity);
    }

    public function test_release_stock_caps_at_current_reserved(): void
    {
        $item = $this->makeItem(quantity: 100, reserved: 5);

        // Try to release more than reserved
        $updated = $this->service->releaseStock($item, 100, 'Capped release');

        $this->assertEquals(0, $updated->reserved_quantity);
    }

    /*
    |--------------------------------------------------------------------------
    | compensateCreation
    |--------------------------------------------------------------------------
    */

    public function test_compensate_creation_soft_deletes_item(): void
    {
        $item = $this->makeItem(quantity: 0);

        $result = $this->service->compensateCreation($item->id, 1, 'saga-test-id');

        $this->assertTrue($result);
        $this->assertSoftDeleted('inventory_items', ['id' => $item->id]);
    }

    public function test_compensate_creation_returns_false_when_not_found(): void
    {
        $result = $this->service->compensateCreation(9999, 1, 'non-existent-saga');

        $this->assertFalse($result);
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    private function makeItem(
        int $tenantId   = 1,
        int $productId  = 1,
        int $quantity   = 100,
        int $reserved   = 0,
        int $reorderPoint = 0,
    ): InventoryItem {
        $warehouse = \App\Models\Warehouse::create([
            'tenant_id' => $tenantId,
            'name'      => 'Unit Test WH',
            'code'      => 'UT-' . uniqid(),
            'is_active' => true,
        ]);

        return InventoryItem::create([
            'tenant_id'         => $tenantId,
            'product_id'        => $productId,
            'warehouse_id'      => $warehouse->id,
            'sku'               => 'TEST-' . uniqid(),
            'quantity'          => $quantity,
            'reserved_quantity' => $reserved,
            'reorder_point'     => $reorderPoint,
        ]);
    }
}
