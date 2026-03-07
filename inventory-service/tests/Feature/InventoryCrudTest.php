<?php

namespace Tests\Feature;

use App\Models\Inventory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryCrudTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================================
    // CREATE Tests
    // =========================================================================

    public function test_can_create_inventory_item(): void
    {
        $payload = [
            'product_name' => 'Laptop Pro 15',
            'sku' => 'LAP-PRO-15',
            'quantity' => 100,
            'unit_price' => 1299.99,
        ];

        $response = $this->postJson('/api/inventories', $payload);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['id', 'product_name', 'sku', 'quantity', 'unit_price', 'status'],
            ])
            ->assertJsonFragment([
                'success' => true,
                'product_name' => 'Laptop Pro 15',
                'sku' => 'LAP-PRO-15',
                'quantity' => 100,
            ]);

        $this->assertDatabaseHas('inventories', [
            'sku' => 'LAP-PRO-15',
            'product_name' => 'Laptop Pro 15',
        ]);
    }

    public function test_create_inventory_fails_with_invalid_data(): void
    {
        $response = $this->postJson('/api/inventories', [
            'product_name' => '',  // required
            'sku' => '',           // required
            'quantity' => -1,      // min:0
            'unit_price' => -5,    // min:0
        ]);

        $response->assertStatus(422)
            ->assertJsonFragment(['success' => false])
            ->assertJsonStructure(['errors']);
    }

    public function test_create_inventory_fails_with_duplicate_sku(): void
    {
        Inventory::factory()->create(['sku' => 'EXISTING-SKU']);

        $response = $this->postJson('/api/inventories', [
            'product_name' => 'Another Product',
            'sku' => 'EXISTING-SKU',
            'quantity' => 10,
            'unit_price' => 50.00,
        ]);

        $response->assertStatus(422)
            ->assertJsonFragment(['success' => false]);
    }

    // =========================================================================
    // READ Tests
    // =========================================================================

    public function test_can_list_all_inventories(): void
    {
        Inventory::factory()->count(3)->create();

        $response = $this->getJson('/api/inventories');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [['id', 'product_name', 'sku', 'quantity']],
            ])
            ->assertJsonFragment(['success' => true]);

        $this->assertCount(3, $response->json('data'));
    }

    public function test_can_get_single_inventory_item(): void
    {
        $inventory = Inventory::factory()->create([
            'product_name' => 'Widget X',
            'sku' => 'WID-X-001',
        ]);

        $response = $this->getJson("/api/inventories/{$inventory->id}");

        $response->assertStatus(200)
            ->assertJsonFragment([
                'success' => true,
                'sku' => 'WID-X-001',
                'product_name' => 'Widget X',
            ]);
    }

    public function test_get_nonexistent_inventory_returns_404(): void
    {
        $response = $this->getJson('/api/inventories/99999');

        $response->assertStatus(404)
            ->assertJsonFragment(['success' => false]);
    }

    // =========================================================================
    // UPDATE Tests
    // =========================================================================

    public function test_can_update_inventory_item(): void
    {
        $inventory = Inventory::factory()->create([
            'product_name' => 'Old Name',
            'quantity' => 50,
        ]);

        $response = $this->putJson("/api/inventories/{$inventory->id}", [
            'product_name' => 'New Name',
            'quantity' => 75,
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'success' => true,
                'product_name' => 'New Name',
                'quantity' => 75,
            ]);

        $this->assertDatabaseHas('inventories', [
            'id' => $inventory->id,
            'product_name' => 'New Name',
            'quantity' => 75,
        ]);
    }

    public function test_update_nonexistent_inventory_returns_404(): void
    {
        $response = $this->putJson('/api/inventories/99999', [
            'product_name' => 'Updated Name',
        ]);

        $response->assertStatus(404);
    }

    // =========================================================================
    // DELETE Tests
    // =========================================================================

    public function test_can_delete_inventory_item(): void
    {
        $inventory = Inventory::factory()->create();

        $response = $this->deleteJson("/api/inventories/{$inventory->id}");

        $response->assertStatus(200)
            ->assertJsonFragment(['success' => true]);

        $this->assertDatabaseMissing('inventories', ['id' => $inventory->id]);
    }

    public function test_delete_nonexistent_inventory_returns_404(): void
    {
        $response = $this->deleteJson('/api/inventories/99999');

        $response->assertStatus(404);
    }

    // =========================================================================
    // RESERVE Tests (Distributed Transaction Endpoint)
    // =========================================================================

    public function test_can_reserve_inventory_for_order(): void
    {
        $inventory = Inventory::factory()->create([
            'sku' => 'PROD-001',
            'quantity' => 100,
            'reserved_quantity' => 0,
            'status' => 'active',
        ]);

        $response = $this->postJson('/api/inventories/reserve', [
            'sku' => 'PROD-001',
            'quantity' => 10,
            'order_id' => 42,
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'success' => true,
                'sku' => 'PROD-001',
                'reserved_quantity' => 10,
            ]);

        $this->assertDatabaseHas('inventories', [
            'sku' => 'PROD-001',
            'reserved_quantity' => 10,
        ]);
    }

    public function test_reserve_fails_when_insufficient_quantity(): void
    {
        Inventory::factory()->create([
            'sku' => 'LOW-STOCK',
            'quantity' => 5,
            'reserved_quantity' => 0,
            'status' => 'active',
        ]);

        $response = $this->postJson('/api/inventories/reserve', [
            'sku' => 'LOW-STOCK',
            'quantity' => 10, // More than available
            'order_id' => 1,
        ]);

        $response->assertStatus(409)
            ->assertJsonFragment(['success' => false]);

        // Verify no reservation was made
        $this->assertDatabaseHas('inventories', [
            'sku' => 'LOW-STOCK',
            'reserved_quantity' => 0,
        ]);
    }

    public function test_reserve_fails_for_inactive_inventory(): void
    {
        Inventory::factory()->create([
            'sku' => 'INACTIVE-PROD',
            'quantity' => 100,
            'status' => 'inactive',
        ]);

        $response = $this->postJson('/api/inventories/reserve', [
            'sku' => 'INACTIVE-PROD',
            'quantity' => 5,
            'order_id' => 1,
        ]);

        $response->assertStatus(404)
            ->assertJsonFragment(['success' => false]);
    }

    // =========================================================================
    // RELEASE Tests (Compensating Transaction Endpoint)
    // =========================================================================

    public function test_can_release_inventory_reservation(): void
    {
        $inventory = Inventory::factory()->create([
            'sku' => 'RESERVED-PROD',
            'quantity' => 100,
            'reserved_quantity' => 20,
        ]);

        $response = $this->postJson('/api/inventories/release', [
            'sku' => 'RESERVED-PROD',
            'quantity' => 20,
            'order_id' => 5,
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'success' => true,
                'released_quantity' => 20,
            ]);

        $this->assertDatabaseHas('inventories', [
            'sku' => 'RESERVED-PROD',
            'reserved_quantity' => 0,
        ]);
    }

    public function test_release_cannot_reduce_below_zero(): void
    {
        $inventory = Inventory::factory()->create([
            'sku' => 'PARTLY-RESERVED',
            'quantity' => 100,
            'reserved_quantity' => 5, // Only 5 reserved
        ]);

        $response = $this->postJson('/api/inventories/release', [
            'sku' => 'PARTLY-RESERVED',
            'quantity' => 10, // Trying to release more than reserved
            'order_id' => 5,
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['success' => true]);

        // Should only release the available reserved amount (5), not go negative
        $this->assertDatabaseHas('inventories', [
            'sku' => 'PARTLY-RESERVED',
            'reserved_quantity' => 0,
        ]);
    }

    // =========================================================================
    // FULFILL Tests
    // =========================================================================

    public function test_can_fulfill_inventory_for_completed_order(): void
    {
        $inventory = Inventory::factory()->create([
            'sku' => 'FULFILL-PROD',
            'quantity' => 100,
            'reserved_quantity' => 10,
        ]);

        $response = $this->postJson('/api/inventories/fulfill', [
            'sku' => 'FULFILL-PROD',
            'quantity' => 10,
            'order_id' => 7,
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'success' => true,
                'fulfilled_quantity' => 10,
            ]);

        $this->assertDatabaseHas('inventories', [
            'sku' => 'FULFILL-PROD',
            'quantity' => 90,          // 100 - 10
            'reserved_quantity' => 0,   // 10 - 10
        ]);
    }
}
