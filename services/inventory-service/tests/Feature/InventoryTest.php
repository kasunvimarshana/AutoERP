<?php

namespace Tests\Feature;

use App\Models\InventoryItem;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryTest extends TestCase
{
    use RefreshDatabase;

    private int $tenantId = 1;

    private Warehouse $warehouse;

    protected function setUp(): void
    {
        parent::setUp();

        $this->warehouse = Warehouse::create([
            'tenant_id' => $this->tenantId,
            'name'      => 'Test Warehouse',
            'code'      => 'TEST-001',
            'is_active' => true,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Index
    |--------------------------------------------------------------------------
    */

    public function test_index_returns_paginated_inventory_for_tenant(): void
    {
        InventoryItem::create([
            'tenant_id'    => $this->tenantId,
            'product_id'   => 1,
            'warehouse_id' => $this->warehouse->id,
            'sku'          => 'SKU-001',
            'quantity'     => 100,
        ]);

        $response = $this->withTenantHeaders()->getJson('/api/v1/inventory');

        $response->assertOk()
                 ->assertJsonStructure(['data', 'meta', 'links']);
    }

    public function test_index_is_scoped_to_tenant(): void
    {
        // Item for tenant 1
        InventoryItem::create([
            'tenant_id'    => $this->tenantId,
            'product_id'   => 1,
            'warehouse_id' => $this->warehouse->id,
            'sku'          => 'SKU-001',
            'quantity'     => 50,
        ]);

        // Item for tenant 2 (different tenant)
        $warehouse2 = Warehouse::create([
            'tenant_id' => 2,
            'name'      => 'Tenant 2 WH',
            'code'      => 'T2-001',
            'is_active' => true,
        ]);

        InventoryItem::create([
            'tenant_id'    => 2,
            'product_id'   => 2,
            'warehouse_id' => $warehouse2->id,
            'sku'          => 'SKU-002',
            'quantity'     => 200,
        ]);

        $response = $this->withTenantHeaders()->getJson('/api/v1/inventory');

        $response->assertOk();
        $data = $response->json('data');

        // Only tenant 1's item should appear
        $this->assertCount(1, $data);
        $this->assertEquals('SKU-001', $data[0]['sku']);
    }

    /*
    |--------------------------------------------------------------------------
    | Show
    |--------------------------------------------------------------------------
    */

    public function test_show_returns_inventory_item(): void
    {
        $item = InventoryItem::create([
            'tenant_id'    => $this->tenantId,
            'product_id'   => 1,
            'warehouse_id' => $this->warehouse->id,
            'sku'          => 'SKU-001',
            'quantity'     => 50,
        ]);

        $response = $this->withTenantHeaders()->getJson("/api/v1/inventory/{$item->id}");

        $response->assertOk()
                 ->assertJsonPath('data.id', $item->id)
                 ->assertJsonPath('data.sku', 'SKU-001');
    }

    public function test_show_returns_404_for_other_tenant(): void
    {
        $otherWarehouse = Warehouse::create([
            'tenant_id' => 99,
            'name'      => 'Other WH',
            'code'      => 'OTHER-001',
            'is_active' => true,
        ]);

        $item = InventoryItem::create([
            'tenant_id'    => 99,
            'product_id'   => 5,
            'warehouse_id' => $otherWarehouse->id,
            'sku'          => 'HIDDEN-SKU',
            'quantity'     => 10,
        ]);

        $response = $this->withTenantHeaders()->getJson("/api/v1/inventory/{$item->id}");

        $response->assertNotFound();
    }

    /*
    |--------------------------------------------------------------------------
    | Store
    |--------------------------------------------------------------------------
    */

    public function test_store_creates_inventory_item(): void
    {
        $response = $this->withTenantHeaders()->postJson('/api/v1/inventory', [
            'product_id'    => 10,
            'warehouse_id'  => $this->warehouse->id,
            'sku'           => 'NEW-SKU-001',
            'quantity'      => 100,
            'reorder_point' => 10,
        ]);

        $response->assertCreated()
                 ->assertJsonPath('data.sku', 'NEW-SKU-001')
                 ->assertJsonPath('data.quantity', 100);

        $this->assertDatabaseHas('inventory_items', [
            'tenant_id'  => $this->tenantId,
            'product_id' => 10,
            'sku'        => 'NEW-SKU-001',
        ]);
    }

    public function test_store_validates_required_fields(): void
    {
        $response = $this->withTenantHeaders()->postJson('/api/v1/inventory', []);

        $response->assertUnprocessable()
                 ->assertJsonStructure(['errors']);
    }

    /*
    |--------------------------------------------------------------------------
    | Update
    |--------------------------------------------------------------------------
    */

    public function test_update_modifies_inventory_item(): void
    {
        $item = InventoryItem::create([
            'tenant_id'    => $this->tenantId,
            'product_id'   => 1,
            'warehouse_id' => $this->warehouse->id,
            'sku'          => 'OLD-SKU',
            'quantity'     => 50,
        ]);

        $response = $this->withTenantHeaders()->putJson("/api/v1/inventory/{$item->id}", [
            'sku'           => 'NEW-SKU',
            'reorder_point' => 5,
        ]);

        $response->assertOk()
                 ->assertJsonPath('data.sku', 'NEW-SKU');
    }

    /*
    |--------------------------------------------------------------------------
    | Destroy
    |--------------------------------------------------------------------------
    */

    public function test_destroy_soft_deletes_item(): void
    {
        $item = InventoryItem::create([
            'tenant_id'    => $this->tenantId,
            'product_id'   => 1,
            'warehouse_id' => $this->warehouse->id,
            'sku'          => 'DEL-SKU',
            'quantity'     => 10,
        ]);

        $response = $this->withTenantHeaders()->deleteJson("/api/v1/inventory/{$item->id}");

        $response->assertOk();
        $this->assertSoftDeleted('inventory_items', ['id' => $item->id]);
    }

    /*
    |--------------------------------------------------------------------------
    | Transactions
    |--------------------------------------------------------------------------
    */

    public function test_transactions_endpoint_returns_audit_log(): void
    {
        $item = InventoryItem::create([
            'tenant_id'    => $this->tenantId,
            'product_id'   => 1,
            'warehouse_id' => $this->warehouse->id,
            'sku'          => 'AUDIT-SKU',
            'quantity'     => 50,
        ]);

        $item->transactions()->create([
            'tenant_id'       => $this->tenantId,
            'type'            => 'add',
            'quantity_before' => 0,
            'quantity_change' => 50,
            'quantity_after'  => 50,
            'reserved_before' => 0,
            'reserved_change' => 0,
            'reserved_after'  => 0,
            'reason'          => 'Initial stock',
        ]);

        $response = $this->withTenantHeaders()->getJson("/api/v1/inventory/{$item->id}/transactions");

        $response->assertOk()
                 ->assertJsonStructure(['data', 'meta'])
                 ->assertJsonCount(1, 'data');
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    private function withTenantHeaders(): static
    {
        return $this->withHeaders([
            'X-Tenant-ID' => (string) $this->tenantId,
            'Accept'      => 'application/json',
        ]);
    }
}
