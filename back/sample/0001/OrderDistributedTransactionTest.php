<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Services\InventoryServiceClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\RequestException;
use Mockery;
use Tests\TestCase;

/**
 * OrderDistributedTransactionTest
 *
 * Tests the Saga compensating-transaction pattern across the Order and
 * Inventory services without a real HTTP call — the InventoryServiceClient
 * is replaced with a mock so we can simulate remote failures precisely.
 */
class OrderDistributedTransactionTest extends TestCase
{
    use RefreshDatabase;

    private $inventoryMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->inventoryMock = Mockery::mock(InventoryServiceClient::class);
        $this->app->instance(InventoryServiceClient::class, $this->inventoryMock);
    }

    // ================================================================== //
    //  CREATE — happy path
    // ================================================================== //

    public function test_create_order_succeeds_when_inventory_reserves_stock(): void
    {
        $this->inventoryMock
            ->shouldReceive('reserve')
            ->once()
            ->andReturn(['reservation_id' => 'res-uuid-001', 'items' => []]);

        $response = $this->postJson('/api/orders', [
            'customer_id' => 42,
            'items' => [
                ['product_id' => 1, 'quantity' => 2],
            ],
        ]);

        $response->assertStatus(201)
                 ->assertJson(['success' => true, 'reservation_id' => 'res-uuid-001']);

        $this->assertDatabaseHas('orders', [
            'customer_id'    => 42,
            'status'         => 'confirmed',
            'reservation_id' => 'res-uuid-001',
        ]);
    }

    // ================================================================== //
    //  CREATE — inventory failure triggers local rollback
    // ================================================================== //

    public function test_create_order_rolls_back_when_inventory_fails(): void
    {
        $this->inventoryMock
            ->shouldReceive('reserve')
            ->once()
            ->andThrow(new \RuntimeException('Inventory: insufficient stock'));

        // No compensation needed (reservation was never created)
        $this->inventoryMock->shouldNotReceive('cancelReservation');

        $response = $this->postJson('/api/orders', [
            'customer_id' => 42,
            'items' => [['product_id' => 1, 'quantity' => 9999]],
        ]);

        $response->assertStatus(422)
                 ->assertJson(['error' => 'distributed_transaction_failed']);

        // Order must NOT exist in the DB
        $this->assertDatabaseMissing('orders', ['customer_id' => 42]);
    }

    // ================================================================== //
    //  CREATE — inventory reserve succeeds but confirm step fails
    //           → order rollback + compensating cancel sent
    // ================================================================== //

    public function test_create_order_compensates_inventory_when_local_confirm_fails(): void
    {
        $this->inventoryMock
            ->shouldReceive('reserve')
            ->once()
            ->andReturn(['reservation_id' => 'res-uuid-002']);

        // Expect the compensation call
        $this->inventoryMock
            ->shouldReceive('cancelReservation')
            ->once()
            ->with('res-uuid-002');

        // Simulate a DB failure during confirm by forcing Order::confirm to throw.
        // We achieve this by observing the model lifecycle.
        Order::saving(function (Order $order) {
            if ($order->status === 'confirmed') {
                throw new \RuntimeException('Simulated DB failure on confirm');
            }
        });

        $response = $this->postJson('/api/orders', [
            'customer_id' => 1,
            'items' => [['product_id' => 1, 'quantity' => 1]],
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseMissing('orders', ['reservation_id' => 'res-uuid-002']);
    }

    // ================================================================== //
    //  UPDATE — happy path
    // ================================================================== //

    public function test_update_order_succeeds(): void
    {
        $order = Order::create([
            'customer_id'    => 10,
            'status'         => 'confirmed',
            'reservation_id' => 'res-original',
        ]);

        $this->inventoryMock
            ->shouldReceive('adjustReservation')
            ->once()
            ->with('res-original', Mockery::any())
            ->andReturn(['reservation_id' => 'res-original', 'items' => []]);

        $response = $this->putJson("/api/orders/{$order->id}", [
            'items' => [['product_id' => 2, 'quantity' => 5]],
        ]);

        $response->assertStatus(200)->assertJson(['success' => true]);

        $this->assertDatabaseHas('order_items', [
            'order_id'   => $order->id,
            'product_id' => 2,
            'quantity'   => 5,
        ]);
    }

    // ================================================================== //
    //  UPDATE — inventory failure triggers rollback + compensation
    // ================================================================== //

    public function test_update_order_restores_original_items_on_inventory_failure(): void
    {
        $order = Order::create([
            'customer_id'    => 10,
            'status'         => 'confirmed',
            'reservation_id' => 'res-original',
        ]);
        $order->items()->create(['product_id' => 1, 'quantity' => 2]);

        $this->inventoryMock
            ->shouldReceive('adjustReservation')
            ->once()
            ->andThrow(new \RuntimeException('Inventory adjustment failed'));

        // Compensation: restore the original items
        $this->inventoryMock
            ->shouldReceive('adjustReservation')
            ->once(); // compensating call

        $response = $this->putJson("/api/orders/{$order->id}", [
            'items' => [['product_id' => 3, 'quantity' => 99]],
        ]);

        $response->assertStatus(422);

        // Original items still in DB
        $this->assertDatabaseHas('order_items', [
            'order_id'   => $order->id,
            'product_id' => 1,
            'quantity'   => 2,
        ]);
    }

    // ================================================================== //
    //  DELETE — happy path
    // ================================================================== //

    public function test_delete_order_cancels_and_releases_inventory(): void
    {
        $order = Order::create([
            'customer_id'    => 7,
            'status'         => 'confirmed',
            'reservation_id' => 'res-to-release',
        ]);

        $this->inventoryMock
            ->shouldReceive('cancelReservation')
            ->once()
            ->with('res-to-release');

        $response = $this->deleteJson("/api/orders/{$order->id}");

        $response->assertStatus(200)->assertJson(['success' => true]);

        $this->assertSoftDeleted('orders', ['id' => $order->id]);
    }

    // ================================================================== //
    //  DELETE — inventory failure triggers rollback
    // ================================================================== //

    public function test_delete_order_rolls_back_when_inventory_release_fails(): void
    {
        $order = Order::create([
            'customer_id'    => 7,
            'status'         => 'confirmed',
            'reservation_id' => 'res-stuck',
        ]);
        $order->items()->create(['product_id' => 1, 'quantity' => 1]);

        $this->inventoryMock
            ->shouldReceive('cancelReservation')
            ->once()
            ->andThrow(new \RuntimeException('Inventory service unavailable'));

        // Compensation: re-create the reservation
        $this->inventoryMock
            ->shouldReceive('reserve')
            ->once();

        $response = $this->deleteJson("/api/orders/{$order->id}");

        $response->assertStatus(422);

        // Order must still be active in DB
        $this->assertDatabaseHas('orders', [
            'id'     => $order->id,
            'status' => 'confirmed',
        ]);
    }

    // ================================================================== //
    //  READ
    // ================================================================== //

    public function test_list_orders_returns_paginated_results(): void
    {
        Order::factory()->count(5)->create();

        $response = $this->getJson('/api/orders');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data' => ['data', 'total', 'per_page'],
                 ]);
    }

    public function test_show_order_returns_correct_order(): void
    {
        $order = Order::create(['customer_id' => 99, 'status' => 'confirmed', 'reservation_id' => 'x']);

        $response = $this->getJson("/api/orders/{$order->id}");

        $response->assertStatus(200)
                 ->assertJson(['data' => ['id' => $order->id]]);
    }

    public function test_show_order_returns_404_for_missing_order(): void
    {
        $this->getJson('/api/orders/99999')->assertStatus(404);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
