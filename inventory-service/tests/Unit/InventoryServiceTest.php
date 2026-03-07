<?php

namespace Tests\Unit;

use App\Models\InventoryReservation;
use App\Models\Product;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Ramsey\Uuid\Uuid;
use Tests\TestCase;

class InventoryServiceTest extends TestCase
{
    use RefreshDatabase;

    private InventoryService $inventoryService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->inventoryService = new InventoryService();
    }

    private function createProduct(array $overrides = []): Product
    {
        return Product::create(array_merge([
            'sku'               => 'TEST-' . uniqid(),
            'name'              => 'Test Product',
            'description'       => 'Test Description',
            'price'             => 49.99,
            'stock_quantity'    => 100,
            'reserved_quantity' => 0,
        ], $overrides));
    }

    public function testReserveStockDecreasesAvailableStock(): void
    {
        $product = $this->createProduct(['stock_quantity' => 100, 'reserved_quantity' => 0]);

        $sagaId  = Uuid::uuid4()->toString();
        $orderId = Uuid::uuid4()->toString();

        $result = $this->inventoryService->reserveStock($sagaId, $orderId, [
            ['product_id' => $product->id, 'quantity' => 10, 'price' => 49.99],
        ]);

        $this->assertTrue($result);

        $product->refresh();
        $this->assertEquals(10, $product->reserved_quantity);
        $this->assertEquals(90, $product->getAvailableStock());

        $this->assertDatabaseHas('inventory_reservations', [
            'order_id'   => $orderId,
            'product_id' => $product->id,
            'quantity'   => 10,
            'status'     => InventoryReservation::STATUS_PENDING,
            'saga_id'    => $sagaId,
        ]);
    }

    public function testReserveStockFailsWhenInsufficientStock(): void
    {
        $product = $this->createProduct(['stock_quantity' => 5, 'reserved_quantity' => 0]);

        $sagaId  = Uuid::uuid4()->toString();
        $orderId = Uuid::uuid4()->toString();

        $result = $this->inventoryService->reserveStock($sagaId, $orderId, [
            ['product_id' => $product->id, 'quantity' => 10, 'price' => 49.99],
        ]);

        $this->assertFalse($result);

        $product->refresh();
        $this->assertEquals(0, $product->reserved_quantity);
        $this->assertEquals(5, $product->getAvailableStock());

        $this->assertDatabaseMissing('inventory_reservations', [
            'order_id' => $orderId,
        ]);
    }

    public function testReleaseReservationRestoresStock(): void
    {
        $product = $this->createProduct(['stock_quantity' => 50, 'reserved_quantity' => 0]);

        $sagaId  = Uuid::uuid4()->toString();
        $orderId = Uuid::uuid4()->toString();

        // First, reserve some stock
        $this->inventoryService->reserveStock($sagaId, $orderId, [
            ['product_id' => $product->id, 'quantity' => 20, 'price' => 49.99],
        ]);

        $product->refresh();
        $this->assertEquals(20, $product->reserved_quantity);

        // Now release it
        $result = $this->inventoryService->releaseReservation($sagaId, $orderId);

        $this->assertTrue($result);

        $product->refresh();
        $this->assertEquals(0, $product->reserved_quantity);
        $this->assertEquals(50, $product->getAvailableStock());

        $this->assertDatabaseHas('inventory_reservations', [
            'order_id' => $orderId,
            'saga_id'  => $sagaId,
            'status'   => InventoryReservation::STATUS_RELEASED,
        ]);
    }

    public function testReserveStockRollsBackOnPartialFailure(): void
    {
        $product1 = $this->createProduct(['stock_quantity' => 50]);
        $product2 = $this->createProduct(['stock_quantity' => 2]);

        $sagaId  = Uuid::uuid4()->toString();
        $orderId = Uuid::uuid4()->toString();

        $result = $this->inventoryService->reserveStock($sagaId, $orderId, [
            ['product_id' => $product1->id, 'quantity' => 10, 'price' => 10.00],
            ['product_id' => $product2->id, 'quantity' => 5, 'price' => 5.00], // will fail
        ]);

        $this->assertFalse($result);

        // Everything should be rolled back
        $product1->refresh();
        $product2->refresh();
        $this->assertEquals(0, $product1->reserved_quantity);
        $this->assertEquals(0, $product2->reserved_quantity);
    }

    public function testConfirmReservationUpdatesStatus(): void
    {
        $product = $this->createProduct(['stock_quantity' => 50]);
        $sagaId  = Uuid::uuid4()->toString();
        $orderId = Uuid::uuid4()->toString();

        $this->inventoryService->reserveStock($sagaId, $orderId, [
            ['product_id' => $product->id, 'quantity' => 5, 'price' => 10.00],
        ]);

        $result = $this->inventoryService->confirmReservation($sagaId);

        $this->assertTrue($result);
        $this->assertDatabaseHas('inventory_reservations', [
            'saga_id' => $sagaId,
            'status'  => InventoryReservation::STATUS_CONFIRMED,
        ]);
    }
}
