<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Events\InventoryDepleted;
use App\Events\InventoryLow;
use App\Events\InventoryUpdated;
use App\Models\Inventory;
use App\Models\InventoryTransaction;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Event;
use RuntimeException;
use Tests\TestCase;

class InventoryTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    private InventoryService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = $this->app->make(InventoryService::class);

        // Prevent listeners from attempting a real RabbitMQ connection during tests
        Event::fake([
            InventoryUpdated::class,
            InventoryLow::class,
            InventoryDepleted::class,
        ]);
    }

    // -------------------------------------------------------------------------
    // Helper
    // -------------------------------------------------------------------------

    private function makeInventory(array $overrides = []): Inventory
    {
        return Inventory::factory()->create(array_merge([
            'product_id'        => $this->faker->numberBetween(1, 1000),
            'quantity'          => 100,
            'reserved_quantity' => 0,
            'reorder_level'     => 10,
            'reorder_quantity'  => 50,
            'status'            => 'active',
        ], $overrides));
    }

    // -------------------------------------------------------------------------
    // INDEX
    // -------------------------------------------------------------------------

    public function test_index_returns_paginated_inventory(): void
    {
        Inventory::factory()->count(20)->create();

        $result = $this->service->getAllInventory(['per_page' => 10]);

        $this->assertSame(10, $result->perPage());
        $this->assertSame(20, $result->total());
    }

    public function test_index_filters_by_status(): void
    {
        Inventory::factory()->count(3)->create(['status' => 'active']);
        Inventory::factory()->count(2)->create(['status' => 'inactive']);

        $result = $this->service->getAllInventory(['status' => 'active']);

        $this->assertSame(3, $result->total());
        $result->getCollection()->each(
            fn (Inventory $i) => $this->assertSame('active', $i->status)
        );
    }

    public function test_index_filters_low_stock(): void
    {
        // quantity <= reorder_level and > 0
        Inventory::factory()->create(['quantity' => 5,  'reorder_level' => 10]);
        Inventory::factory()->create(['quantity' => 8,  'reorder_level' => 10]);
        Inventory::factory()->create(['quantity' => 50, 'reorder_level' => 10]);

        $result = $this->service->getAllInventory(['low_stock' => 'true']);

        $this->assertSame(2, $result->total());
    }

    public function test_index_filters_out_of_stock(): void
    {
        Inventory::factory()->create(['quantity' => 0]);
        Inventory::factory()->create(['quantity' => 0]);
        Inventory::factory()->create(['quantity' => 50]);

        $result = $this->service->getAllInventory(['out_of_stock' => 'true']);

        $this->assertSame(2, $result->total());
        $result->getCollection()->each(
            fn (Inventory $i) => $this->assertSame(0, $i->quantity)
        );
    }

    public function test_index_sorts_by_quantity_ascending(): void
    {
        Inventory::factory()->create(['quantity' => 90]);
        Inventory::factory()->create(['quantity' => 10]);
        Inventory::factory()->create(['quantity' => 50]);

        $result = $this->service->getAllInventory([
            'sort_by'        => 'quantity',
            'sort_direction' => 'asc',
        ]);

        $quantities = $result->getCollection()->pluck('quantity')->toArray();

        $this->assertSame([10, 50, 90], $quantities);
    }

    // -------------------------------------------------------------------------
    // SHOW
    // -------------------------------------------------------------------------

    public function test_get_inventory_by_id_returns_record(): void
    {
        $inventory = $this->makeInventory();

        $found = $this->service->getInventoryById($inventory->id);

        $this->assertNotNull($found);
        $this->assertSame($inventory->id, $found->id);
    }

    public function test_get_inventory_by_id_returns_null_for_missing_id(): void
    {
        $result = $this->service->getInventoryById(99999);

        $this->assertNull($result);
    }

    public function test_get_inventory_by_product_id(): void
    {
        $productId = 42;
        Inventory::factory()->count(2)->create(['product_id' => $productId]);
        Inventory::factory()->create(['product_id' => 99]);

        $results = $this->service->getInventoryByProductId($productId);

        $this->assertCount(2, $results);
        $results->each(fn (Inventory $i) => $this->assertSame($productId, $i->product_id));
    }

    // -------------------------------------------------------------------------
    // STORE
    // -------------------------------------------------------------------------

    public function test_create_inventory_persists_record(): void
    {
        $data = [
            'product_id'         => 1,
            'quantity'           => 100,
            'reserved_quantity'  => 0,
            'warehouse_location' => 'A-01-01',
            'reorder_level'      => 10,
            'reorder_quantity'   => 50,
            'unit_cost'          => 25.99,
            'status'             => 'active',
        ];

        $inventory = $this->service->createInventory($data);

        $this->assertDatabaseHas('inventories', ['product_id' => 1, 'warehouse_location' => 'A-01-01']);
        $this->assertSame(100, $inventory->quantity);
    }

    public function test_create_inventory_with_initial_stock_creates_receipt_transaction(): void
    {
        $inventory = $this->service->createInventory([
            'product_id' => 5,
            'quantity'   => 50,
            'status'     => 'active',
        ]);

        $this->assertDatabaseHas('inventory_transactions', [
            'inventory_id' => $inventory->id,
            'type'         => InventoryTransaction::TYPE_RECEIPT,
            'new_quantity' => 50,
        ]);
    }

    public function test_create_inventory_with_zero_stock_creates_no_transaction(): void
    {
        $inventory = $this->service->createInventory([
            'product_id' => 6,
            'quantity'   => 0,
            'status'     => 'active',
        ]);

        $this->assertDatabaseMissing('inventory_transactions', [
            'inventory_id' => $inventory->id,
        ]);
    }

    // -------------------------------------------------------------------------
    // UPDATE
    // -------------------------------------------------------------------------

    public function test_update_inventory_persists_changes(): void
    {
        $inventory = $this->makeInventory(['warehouse_location' => 'A-01-01']);

        $updated = $this->service->updateInventory($inventory->id, [
            'warehouse_location' => 'B-02-05',
            'reorder_level'      => 20,
        ]);

        $this->assertNotNull($updated);
        $this->assertSame('B-02-05', $updated->warehouse_location);
        $this->assertSame(20, $updated->reorder_level);
    }

    public function test_update_inventory_returns_null_for_missing_id(): void
    {
        $result = $this->service->updateInventory(99999, ['status' => 'inactive']);

        $this->assertNull($result);
    }

    // -------------------------------------------------------------------------
    // DESTROY
    // -------------------------------------------------------------------------

    public function test_delete_inventory_soft_deletes(): void
    {
        $inventory = $this->makeInventory();

        $result = $this->service->deleteInventory($inventory->id);

        $this->assertTrue($result);
        $this->assertSoftDeleted('inventories', ['id' => $inventory->id]);
    }

    public function test_delete_inventory_returns_false_for_missing_id(): void
    {
        $result = $this->service->deleteInventory(99999);

        $this->assertFalse($result);
    }

    // -------------------------------------------------------------------------
    // ADJUST STOCK
    // -------------------------------------------------------------------------

    public function test_adjust_stock_receipt_increases_quantity(): void
    {
        $inventory = $this->makeInventory(['quantity' => 50]);

        $updated = $this->service->adjustStock($inventory->id, InventoryTransaction::TYPE_RECEIPT, 30, 'Restock');

        $this->assertSame(80, $updated->quantity);

        $this->assertDatabaseHas('inventory_transactions', [
            'inventory_id'      => $inventory->id,
            'type'              => InventoryTransaction::TYPE_RECEIPT,
            'quantity'          => 30,
            'previous_quantity' => 50,
            'new_quantity'      => 80,
        ]);

        Event::assertDispatched(InventoryUpdated::class, fn (InventoryUpdated $e) => $e->inventory->id === $inventory->id);
    }

    public function test_adjust_stock_sale_decreases_quantity(): void
    {
        $inventory = $this->makeInventory(['quantity' => 50]);

        $updated = $this->service->adjustStock($inventory->id, InventoryTransaction::TYPE_SALE, -10, 'Order #42');

        $this->assertSame(40, $updated->quantity);

        $this->assertDatabaseHas('inventory_transactions', [
            'inventory_id'      => $inventory->id,
            'type'              => InventoryTransaction::TYPE_SALE,
            'previous_quantity' => 50,
            'new_quantity'      => 40,
        ]);
    }

    public function test_adjust_stock_throws_when_insufficient_stock(): void
    {
        $inventory = $this->makeInventory(['quantity' => 5]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/Insufficient stock/');

        $this->service->adjustStock($inventory->id, InventoryTransaction::TYPE_SALE, -10);
    }

    public function test_adjust_stock_fires_low_stock_event_when_below_reorder_level(): void
    {
        // Start at 15, reorder level at 10 – bring it down to 8 (low stock)
        $inventory = $this->makeInventory(['quantity' => 15, 'reorder_level' => 10]);

        $this->service->adjustStock($inventory->id, InventoryTransaction::TYPE_SALE, -7);

        Event::assertDispatched(InventoryLow::class, fn (InventoryLow $e) => $e->inventory->id === $inventory->id);
        Event::assertNotDispatched(InventoryDepleted::class);
    }

    public function test_adjust_stock_fires_depleted_event_when_quantity_reaches_zero(): void
    {
        $inventory = $this->makeInventory(['quantity' => 5]);

        $this->service->adjustStock($inventory->id, InventoryTransaction::TYPE_SALE, -5);

        Event::assertDispatched(InventoryDepleted::class, fn (InventoryDepleted $e) => $e->inventory->id === $inventory->id);
    }

    public function test_adjust_stock_throws_for_invalid_type(): void
    {
        $inventory = $this->makeInventory();

        $this->expectException(\InvalidArgumentException::class);

        $this->service->adjustStock($inventory->id, 'invalid_type', 10);
    }

    // -------------------------------------------------------------------------
    // RESERVE & RELEASE STOCK
    // -------------------------------------------------------------------------

    public function test_reserve_stock_increases_reserved_quantity(): void
    {
        $inventory = $this->makeInventory(['quantity' => 100, 'reserved_quantity' => 0]);

        $updated = $this->service->reserveStock($inventory->id, 20, 'order', '99');

        $this->assertSame(20, $updated->reserved_quantity);
        $this->assertSame(80, $updated->available_quantity);

        $this->assertDatabaseHas('inventory_transactions', [
            'inventory_id' => $inventory->id,
            'type'         => InventoryTransaction::TYPE_RESERVATION,
            'quantity'     => 20,
        ]);

        Event::assertDispatched(InventoryUpdated::class);
    }

    public function test_reserve_stock_throws_when_insufficient_available(): void
    {
        $inventory = $this->makeInventory(['quantity' => 10, 'reserved_quantity' => 8]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/Insufficient available stock/');

        $this->service->reserveStock($inventory->id, 5);
    }

    public function test_release_stock_decreases_reserved_quantity(): void
    {
        $inventory = $this->makeInventory(['quantity' => 100, 'reserved_quantity' => 30]);

        $updated = $this->service->releaseStock($inventory->id, 30, 'order', '100');

        $this->assertSame(0, $updated->reserved_quantity);
        $this->assertSame(100, $updated->available_quantity);

        $this->assertDatabaseHas('inventory_transactions', [
            'inventory_id' => $inventory->id,
            'type'         => InventoryTransaction::TYPE_RELEASE,
            'quantity'     => 30,
        ]);
    }

    public function test_release_stock_throws_when_releasing_more_than_reserved(): void
    {
        $inventory = $this->makeInventory(['quantity' => 100, 'reserved_quantity' => 5]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/Cannot release more than reserved/');

        $this->service->releaseStock($inventory->id, 10);
    }

    // -------------------------------------------------------------------------
    // HTTP Layer
    // -------------------------------------------------------------------------

    public function test_http_index_returns_inventory_list(): void
    {
        Inventory::factory()->count(3)->create();

        $response = $this->withoutMiddleware()->getJson('/api/v1/inventory');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [['id', 'product_id', 'quantity', 'reserved_quantity', 'status']],
        ]);
    }

    public function test_http_store_validates_required_fields(): void
    {
        $response = $this->withoutMiddleware()->postJson('/api/v1/inventory', []);

        $response->assertStatus(422);
        $response->assertJsonStructure(['message', 'errors']);
    }

    public function test_http_store_creates_inventory(): void
    {
        $payload = [
            'product_id' => 99,
            'quantity'   => 100,
            'status'     => 'active',
        ];

        $response = $this->withoutMiddleware()->postJson('/api/v1/inventory', $payload);

        $response->assertStatus(201);
        $response->assertJsonFragment(['product_id' => 99]);
        $this->assertDatabaseHas('inventories', ['product_id' => 99]);
    }

    public function test_http_show_returns_404_for_unknown_inventory(): void
    {
        $response = $this->withoutMiddleware()->getJson('/api/v1/inventory/99999');

        $response->assertNotFound();
    }

    public function test_http_update_inventory(): void
    {
        $inventory = $this->makeInventory();

        $response = $this->withoutMiddleware()->putJson(
            "/api/v1/inventory/{$inventory->id}",
            ['warehouse_location' => 'Z-99-99', 'status' => 'inactive']
        );

        $response->assertOk();
        $response->assertJsonFragment(['warehouse_location' => 'Z-99-99']);
    }

    public function test_http_destroy_inventory(): void
    {
        $inventory = $this->makeInventory();

        $response = $this->withoutMiddleware()->deleteJson("/api/v1/inventory/{$inventory->id}");

        $response->assertOk();
        $response->assertJsonFragment(['message' => 'Inventory record deleted successfully.']);
        $this->assertSoftDeleted('inventories', ['id' => $inventory->id]);
    }

    public function test_http_adjust_inventory_increases_stock(): void
    {
        $inventory = $this->makeInventory(['quantity' => 50]);

        $response = $this->withoutMiddleware()->postJson(
            "/api/v1/inventory/{$inventory->id}/adjust",
            ['type' => 'receipt', 'quantity' => 25, 'notes' => 'Restock delivery']
        );

        $response->assertOk();
        $response->assertJsonFragment(['quantity' => 75]);
    }

    public function test_http_adjust_inventory_returns_422_on_insufficient_stock(): void
    {
        $inventory = $this->makeInventory(['quantity' => 5]);

        $response = $this->withoutMiddleware()->postJson(
            "/api/v1/inventory/{$inventory->id}/adjust",
            ['type' => 'sale', 'quantity' => -50]
        );

        $response->assertStatus(422);
    }

    public function test_http_get_inventory_by_product_id(): void
    {
        $productId = 777;
        Inventory::factory()->count(2)->create(['product_id' => $productId]);

        $response = $this->withoutMiddleware()->getJson("/api/v1/inventory/product/{$productId}");

        $response->assertOk();
        $response->assertJsonFragment(['product_id' => $productId]);
        $this->assertCount(2, $response->json('data'));
    }

    public function test_http_transactions_index(): void
    {
        $inventory = $this->makeInventory(['quantity' => 50]);
        $this->service->adjustStock($inventory->id, InventoryTransaction::TYPE_RECEIPT, 10);

        $response = $this->withoutMiddleware()->getJson('/api/v1/inventory-transactions');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [['id', 'inventory_id', 'type', 'quantity']],
        ]);
    }

    public function test_http_transactions_show_returns_404_for_unknown(): void
    {
        $response = $this->withoutMiddleware()->getJson('/api/v1/inventory-transactions/99999');

        $response->assertNotFound();
    }
}
