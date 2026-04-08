<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Reservation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ReservationTest
 *
 * Tests the Inventory Service in isolation — no Order Service dependency.
 * Focuses on: stock decrement/increment, idempotency, and over-reservation prevention.
 */
class ReservationTest extends TestCase
{
    use RefreshDatabase;

    private function createProduct(array $overrides = []): Product
    {
        return Product::create(array_merge([
            'name'            => 'Test Product',
            'sku'             => uniqid('SKU-'),
            'total_stock'     => 100,
            'available_stock' => 100,
        ], $overrides));
    }

    // ================================================================== //
    //  CREATE reservation
    // ================================================================== //

    public function test_reserve_decrements_available_stock(): void
    {
        $product = $this->createProduct(['available_stock' => 50]);

        $this->postJson('/api/inventory/reservations', [
            'order_id' => 1,
            'items'    => [['product_id' => $product->id, 'quantity' => 10]],
        ])->assertStatus(201);

        $this->assertEquals(40, $product->fresh()->available_stock);
    }

    public function test_reserve_fails_when_stock_is_insufficient(): void
    {
        $product = $this->createProduct(['available_stock' => 5]);

        $this->postJson('/api/inventory/reservations', [
            'order_id' => 1,
            'items'    => [['product_id' => $product->id, 'quantity' => 10]],
        ])->assertStatus(422)->assertJson(['error' => 'insufficient_stock']);

        // Stock must be unchanged
        $this->assertEquals(5, $product->fresh()->available_stock);
    }

    public function test_reserve_is_idempotent_with_same_key(): void
    {
        $product = $this->createProduct(['available_stock' => 50]);

        $payload = [
            'order_id' => 1,
            'items'    => [['product_id' => $product->id, 'quantity' => 5]],
        ];

        $headers = ['Idempotency-Key' => 'reserve-order-1'];

        $this->postJson('/api/inventory/reservations', $payload, $headers)->assertStatus(201);
        $this->postJson('/api/inventory/reservations', $payload, $headers)->assertStatus(200);

        // Stock should only be decremented once
        $this->assertEquals(45, $product->fresh()->available_stock);
        $this->assertCount(1, Reservation::all());
    }

    // ================================================================== //
    //  UPDATE reservation
    // ================================================================== //

    public function test_adjust_reservation_updates_stock_correctly(): void
    {
        $product = $this->createProduct(['available_stock' => 50]);
        $reservation = Reservation::create([
            'id'       => 'res-test-001',
            'order_id' => 1,
            'status'   => 'active',
        ]);
        $reservation->items()->create(['product_id' => $product->id, 'quantity' => 5]);
        $product->decrement('available_stock', 5); // simulate initial reserve

        // Adjust: increase from 5 → 12
        $this->patchJson("/api/inventory/reservations/res-test-001", [
            'items' => [['product_id' => $product->id, 'quantity' => 12]],
        ])->assertStatus(200);

        // 50 - 5 (released) + 5 (available after release) - 12 (re-reserved) = 33
        $this->assertEquals(33, $product->fresh()->available_stock);
    }

    public function test_adjust_reservation_fails_if_insufficient_stock(): void
    {
        $product = $this->createProduct(['available_stock' => 10]);
        $reservation = Reservation::create([
            'id'       => 'res-test-002',
            'order_id' => 2,
            'status'   => 'active',
        ]);
        $reservation->items()->create(['product_id' => $product->id, 'quantity' => 3]);
        $product->decrement('available_stock', 3);

        // Try to adjust to 99 — exceeds available
        $this->patchJson("/api/inventory/reservations/res-test-002", [
            'items' => [['product_id' => $product->id, 'quantity' => 99]],
        ])->assertStatus(422);

        // Original reservation items must be intact
        $this->assertDatabaseHas('reservation_items', [
            'reservation_id' => 'res-test-002',
            'quantity'       => 3,
        ]);
    }

    // ================================================================== //
    //  DELETE reservation (cancel)
    // ================================================================== //

    public function test_cancel_reservation_releases_stock(): void
    {
        $product = $this->createProduct(['available_stock' => 30]);
        $reservation = Reservation::create([
            'id'       => 'res-test-003',
            'order_id' => 3,
            'status'   => 'active',
        ]);
        $reservation->items()->create(['product_id' => $product->id, 'quantity' => 8]);
        $product->decrement('available_stock', 8);

        $this->deleteJson('/api/inventory/reservations/res-test-003')
             ->assertStatus(200);

        // Stock should be restored to 30
        $this->assertEquals(30, $product->fresh()->available_stock);
        $this->assertDatabaseHas('reservations', [
            'id'     => 'res-test-003',
            'status' => 'cancelled',
        ]);
    }

    public function test_cancel_already_cancelled_reservation_returns_404(): void
    {
        $this->deleteJson('/api/inventory/reservations/nonexistent-uuid')
             ->assertStatus(404);
    }

    public function test_cancel_is_idempotent_with_same_key(): void
    {
        $product = $this->createProduct(['available_stock' => 20]);
        $reservation = Reservation::create([
            'id'       => 'res-test-004',
            'order_id' => 4,
            'status'   => 'active',
        ]);
        $reservation->items()->create(['product_id' => $product->id, 'quantity' => 4]);
        $product->decrement('available_stock', 4);

        $headers = ['Idempotency-Key' => 'cancel-res-test-004'];

        $this->deleteJson('/api/inventory/reservations/res-test-004', [], $headers)->assertStatus(200);
        $this->deleteJson('/api/inventory/reservations/res-test-004', [], $headers)->assertStatus(200);

        // Stock should only be released once
        $this->assertEquals(20, $product->fresh()->available_stock);
    }
}
