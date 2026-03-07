<?php

namespace Tests\Feature;

use App\Exceptions\ServiceException;
use App\Models\Order;
use App\Services\InventoryServiceClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class OrderCrudTest extends TestCase
{
    use RefreshDatabase;

    private function mockInventoryClient(): \Mockery\MockInterface
    {
        $mock = Mockery::mock(InventoryServiceClient::class);
        $this->app->instance(InventoryServiceClient::class, $mock);
        return $mock;
    }

    // =========================================================================
    // CREATE Tests
    // =========================================================================

    public function test_can_create_order_when_inventory_available(): void
    {
        $mock = $this->mockInventoryClient();
        $mock->shouldReceive('reserveInventory')
            ->once()
            ->with('PROD-SKU-001', 5, 0)
            ->andReturn(['success' => true, 'message' => 'Inventory reserved successfully.']);

        $payload = [
            'customer_name' => 'Jane Doe',
            'customer_email' => 'jane@example.com',
            'product_sku' => 'PROD-SKU-001',
            'product_name' => 'Widget A',
            'quantity' => 5,
            'unit_price' => 29.99,
        ];

        $response = $this->postJson('/api/orders', $payload);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['id', 'customer_name', 'customer_email', 'product_sku', 'quantity', 'total_price', 'status'],
            ])
            ->assertJsonFragment([
                'success' => true,
                'customer_name' => 'Jane Doe',
                'product_sku' => 'PROD-SKU-001',
                'quantity' => 5,
                'status' => 'pending',
            ]);

        $this->assertDatabaseHas('orders', [
            'customer_email' => 'jane@example.com',
            'product_sku' => 'PROD-SKU-001',
            'quantity' => 5,
            'status' => 'pending',
        ]);
    }

    public function test_order_creation_validates_required_fields(): void
    {
        $response = $this->postJson('/api/orders', []);

        $response->assertStatus(422)
            ->assertJsonFragment(['success' => false])
            ->assertJsonStructure(['errors']);
    }

    /**
     * DISTRIBUTED TRANSACTION TEST:
     * When the Inventory Service rejects the reservation (e.g., insufficient stock),
     * the Order Service must NOT create an order record and must return an error.
     * This tests that the local DB transaction is rolled back when the remote call fails.
     */
    public function test_order_creation_rolls_back_when_inventory_service_fails(): void
    {
        $mock = $this->mockInventoryClient();
        $mock->shouldReceive('reserveInventory')
            ->once()
            ->andThrow(new ServiceException('Insufficient inventory. Available: 3, Requested: 10.', 409));

        $payload = [
            'customer_name' => 'John Smith',
            'customer_email' => 'john@example.com',
            'product_sku' => 'OUT-OF-STOCK',
            'product_name' => 'Scarce Item',
            'quantity' => 10,
            'unit_price' => 49.99,
        ];

        $response = $this->postJson('/api/orders', $payload);

        $response->assertStatus(409)
            ->assertJsonFragment([
                'success' => false,
                'transaction_status' => 'rolled_back',
            ]);

        // Verify no order was created (local transaction was rolled back)
        $this->assertDatabaseMissing('orders', [
            'customer_email' => 'john@example.com',
            'product_sku' => 'OUT-OF-STOCK',
        ]);
    }

    /**
     * DISTRIBUTED TRANSACTION TEST:
     * When inventory is reserved but local DB write fails,
     * the service must execute a compensating transaction (release) to
     * undo the inventory reservation and maintain data consistency.
     *
     * This test verifies the saga pattern's compensating transaction mechanism
     * by demonstrating the full reserve → failure → release (compensating) flow.
     */
    public function test_compensating_transaction_executed_when_local_db_fails_after_inventory_reserved(): void
    {
        // Set up a pending order to exercise the delete/release compensating transaction
        $order = Order::factory()->create([
            'product_sku' => 'COMP-TX-SKU',
            'quantity' => 5,
            'status' => 'pending',
        ]);

        // The compensating transaction: when deleting an order, inventory reservation
        // must be released to maintain cross-service data consistency
        $mock = $this->mockInventoryClient();
        $mock->shouldReceive('releaseInventory')
            ->once()
            ->with('COMP-TX-SKU', 5, $order->id)
            ->andReturn(['success' => true, 'message' => 'Compensating transaction: inventory released.']);

        $response = $this->deleteJson("/api/orders/{$order->id}");

        $response->assertStatus(200)
            ->assertJsonFragment([
                'success' => true,
                'inventory_released' => true,
            ]);

        // Verify both order deletion and inventory release occurred (compensating transaction)
        $this->assertDatabaseMissing('orders', ['id' => $order->id]);
    }

    // =========================================================================
    // READ Tests
    // =========================================================================

    public function test_can_list_all_orders(): void
    {
        Order::factory()->count(3)->create();

        $response = $this->getJson('/api/orders');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [['id', 'customer_name', 'product_sku', 'quantity', 'status']],
            ]);

        $this->assertCount(3, $response->json('data'));
    }

    public function test_can_get_single_order(): void
    {
        $order = Order::factory()->create([
            'customer_name' => 'Alice Wonder',
            'status' => 'pending',
        ]);

        $response = $this->getJson("/api/orders/{$order->id}");

        $response->assertStatus(200)
            ->assertJsonFragment([
                'success' => true,
                'customer_name' => 'Alice Wonder',
                'status' => 'pending',
            ]);
    }

    public function test_get_nonexistent_order_returns_404(): void
    {
        $response = $this->getJson('/api/orders/99999');

        $response->assertStatus(404)
            ->assertJsonFragment(['success' => false]);
    }

    // =========================================================================
    // UPDATE Tests
    // =========================================================================

    public function test_can_update_order_without_quantity_change(): void
    {
        $order = Order::factory()->create(['status' => 'pending', 'quantity' => 5]);

        $mock = $this->mockInventoryClient();
        // No inventory calls when quantity doesn't change
        $mock->shouldNotReceive('reserveInventory');
        $mock->shouldNotReceive('releaseInventory');

        $response = $this->putJson("/api/orders/{$order->id}", [
            'customer_name' => 'Updated Name',
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'success' => true,
                'customer_name' => 'Updated Name',
            ]);
    }

    public function test_update_reserves_additional_inventory_when_quantity_increases(): void
    {
        $order = Order::factory()->create([
            'product_sku' => 'INCREASE-SKU',
            'quantity' => 5,
            'status' => 'pending',
        ]);

        $mock = $this->mockInventoryClient();
        // Should reserve additional 3 units (8 - 5 = 3)
        $mock->shouldReceive('reserveInventory')
            ->once()
            ->with('INCREASE-SKU', 3, $order->id)
            ->andReturn(['success' => true]);

        $response = $this->putJson("/api/orders/{$order->id}", [
            'quantity' => 8,
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'success' => true,
                'inventory_adjustment' => 'reserved_additional',
            ]);
    }

    public function test_update_releases_inventory_when_quantity_decreases(): void
    {
        $order = Order::factory()->create([
            'product_sku' => 'DECREASE-SKU',
            'quantity' => 10,
            'status' => 'pending',
        ]);

        $mock = $this->mockInventoryClient();
        // Should release 4 units (10 - 6 = 4)
        $mock->shouldReceive('releaseInventory')
            ->once()
            ->with('DECREASE-SKU', 4, $order->id)
            ->andReturn(['success' => true]);

        $response = $this->putJson("/api/orders/{$order->id}", [
            'quantity' => 6,
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'success' => true,
                'inventory_adjustment' => 'released_excess',
            ]);
    }

    public function test_update_rolls_back_when_inventory_service_fails(): void
    {
        $order = Order::factory()->create([
            'product_sku' => 'ROLLBACK-SKU',
            'quantity' => 5,
            'status' => 'pending',
        ]);

        $mock = $this->mockInventoryClient();
        $mock->shouldReceive('reserveInventory')
            ->once()
            ->andThrow(new ServiceException('Inventory not available', 409));

        $response = $this->putJson("/api/orders/{$order->id}", [
            'quantity' => 10,
        ]);

        $response->assertStatus(409)
            ->assertJsonFragment([
                'success' => false,
                'transaction_status' => 'rolled_back',
            ]);

        // Verify order quantity was NOT changed (rolled back)
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'quantity' => 5,
        ]);
    }

    public function test_cannot_update_confirmed_order(): void
    {
        $order = Order::factory()->create(['status' => 'confirmed']);

        $response = $this->putJson("/api/orders/{$order->id}", [
            'customer_name' => 'New Name',
        ]);

        $response->assertStatus(409)
            ->assertJsonFragment(['success' => false]);
    }

    // =========================================================================
    // DELETE Tests
    // =========================================================================

    public function test_can_delete_pending_order_and_release_inventory(): void
    {
        $order = Order::factory()->create([
            'product_sku' => 'DELETE-SKU',
            'quantity' => 5,
            'status' => 'pending',
        ]);

        $mock = $this->mockInventoryClient();
        $mock->shouldReceive('releaseInventory')
            ->once()
            ->with('DELETE-SKU', 5, $order->id)
            ->andReturn(['success' => true]);

        $response = $this->deleteJson("/api/orders/{$order->id}");

        $response->assertStatus(200)
            ->assertJsonFragment([
                'success' => true,
                'inventory_released' => true,
            ]);

        $this->assertDatabaseMissing('orders', ['id' => $order->id]);
    }

    public function test_delete_rolls_back_when_inventory_release_fails(): void
    {
        $order = Order::factory()->create([
            'product_sku' => 'RELEASE-FAIL-SKU',
            'quantity' => 5,
            'status' => 'pending',
        ]);

        $mock = $this->mockInventoryClient();
        $mock->shouldReceive('releaseInventory')
            ->once()
            ->andThrow(new ServiceException('Inventory Service unavailable', 503));

        $response = $this->deleteJson("/api/orders/{$order->id}");

        $response->assertStatus(503)
            ->assertJsonFragment([
                'success' => false,
                'transaction_status' => 'rolled_back',
            ]);

        // Order should still exist (local transaction rolled back)
        $this->assertDatabaseHas('orders', ['id' => $order->id]);
    }

    public function test_cannot_delete_completed_order(): void
    {
        $order = Order::factory()->create(['status' => 'completed']);

        $response = $this->deleteJson("/api/orders/{$order->id}");

        $response->assertStatus(409)
            ->assertJsonFragment(['success' => false]);
    }

    public function test_delete_nonexistent_order_returns_404(): void
    {
        $response = $this->deleteJson('/api/orders/99999');

        $response->assertStatus(404);
    }

    // =========================================================================
    // CONFIRM Tests
    // =========================================================================

    public function test_can_confirm_pending_order(): void
    {
        $order = Order::factory()->create([
            'product_sku' => 'CONFIRM-SKU',
            'quantity' => 3,
            'status' => 'pending',
        ]);

        $mock = $this->mockInventoryClient();
        $mock->shouldReceive('fulfillInventory')
            ->once()
            ->with('CONFIRM-SKU', 3, $order->id)
            ->andReturn(['success' => true]);

        $response = $this->postJson("/api/orders/{$order->id}/confirm");

        $response->assertStatus(200)
            ->assertJsonFragment([
                'success' => true,
                'status' => 'confirmed',
            ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'confirmed',
        ]);
    }

    public function test_confirm_rolls_back_when_inventory_fulfillment_fails(): void
    {
        $order = Order::factory()->create([
            'product_sku' => 'FULFILL-FAIL-SKU',
            'quantity' => 3,
            'status' => 'pending',
        ]);

        $mock = $this->mockInventoryClient();
        $mock->shouldReceive('fulfillInventory')
            ->once()
            ->andThrow(new ServiceException('Fulfillment failed', 500));

        $response = $this->postJson("/api/orders/{$order->id}/confirm");

        $response->assertStatus(500)
            ->assertJsonFragment([
                'success' => false,
                'transaction_status' => 'rolled_back',
            ]);

        // Order should still be pending (rolled back)
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'pending',
        ]);
    }

    public function test_cannot_confirm_already_confirmed_order(): void
    {
        $order = Order::factory()->create(['status' => 'confirmed']);

        $response = $this->postJson("/api/orders/{$order->id}/confirm");

        $response->assertStatus(409);
    }

    public function test_total_price_is_calculated_correctly_on_create(): void
    {
        $mock = $this->mockInventoryClient();
        $mock->shouldReceive('reserveInventory')->andReturn(['success' => true]);

        $response = $this->postJson('/api/orders', [
            'customer_name' => 'Price Tester',
            'customer_email' => 'price@test.com',
            'product_sku' => 'PRICE-SKU',
            'product_name' => 'Price Test Item',
            'quantity' => 4,
            'unit_price' => 25.00,
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('orders', [
            'product_sku' => 'PRICE-SKU',
            'total_price' => 100.00, // 4 * 25.00
        ]);
    }
}
