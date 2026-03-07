<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsTenant();
    }

    private function makeCategory(): Category
    {
        return Category::create([
            'tenant_id'  => $this->tenantId,
            'name'       => 'Test Category',
            'slug'       => 'test-category',
            'is_active'  => true,
            'sort_order' => 0,
        ]);
    }

    // -------------------------------------------------------------------------
    // Index
    // -------------------------------------------------------------------------

    public function test_index_returns_paginated_products(): void
    {
        Product::create([
            'tenant_id' => $this->tenantId,
            'sku'       => 'TEST-001',
            'name'      => 'Test Product',
            'price'     => 10.00,
            'status'    => 'active',
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/v1/products');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [['id', 'sku', 'name', 'price', 'status']],
                'meta' => ['current_page', 'total', 'per_page'],
                'links',
            ]);
    }

    public function test_index_filters_by_status(): void
    {
        Product::create(['tenant_id' => $this->tenantId, 'sku' => 'A1', 'name' => 'Active', 'price' => 1, 'status' => 'active', 'is_active' => true]);
        Product::create(['tenant_id' => $this->tenantId, 'sku' => 'I1', 'name' => 'Inactive', 'price' => 1, 'status' => 'inactive', 'is_active' => false]);

        $response = $this->getJson('/api/v1/products?filter[status]=active');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('active', $response->json('data.0.status'));
    }

    // -------------------------------------------------------------------------
    // Show
    // -------------------------------------------------------------------------

    public function test_show_returns_product_with_inventory_null_when_service_unavailable(): void
    {
        $product = Product::create([
            'tenant_id' => $this->tenantId,
            'sku'       => 'SHOW-001',
            'name'      => 'Show Product',
            'price'     => 25.00,
            'status'    => 'active',
            'is_active' => true,
        ]);

        // InventoryClientService::getInventoryForProduct returns null (unavailable)
        $this->mock(\App\Services\InventoryClientService::class)
            ->shouldReceive('getInventoryForProduct')
            ->andReturn(null);

        $response = $this->getJson("/api/v1/products/{$product->id}");

        $response->assertStatus(200)
            ->assertJsonPath('id', $product->id)
            ->assertJsonPath('inventory', null);
    }

    public function test_show_returns_404_for_nonexistent_product(): void
    {
        $this->getJson('/api/v1/products/99999')->assertStatus(404);
    }

    // -------------------------------------------------------------------------
    // Store
    // -------------------------------------------------------------------------

    public function test_store_creates_product(): void
    {
        $category = $this->makeCategory();

        $this->mock(\App\Services\InventoryClientService::class)
            ->shouldReceive('createInventoryRecord')->andReturn(null);

        $this->mock(\App\Webhooks\WebhookDispatcher::class)
            ->shouldReceive('dispatch')->andReturn(null);

        $payload = [
            'sku'         => 'NEW-001',
            'name'        => 'New Product',
            'price'       => 49.99,
            'category_id' => $category->id,
            'status'      => 'active',
            'is_active'   => true,
        ];

        $response = $this->postJson('/api/v1/products', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('sku', 'NEW-001')
            ->assertJsonPath('name', 'New Product');

        $this->assertDatabaseHas('products', ['sku' => 'NEW-001', 'tenant_id' => $this->tenantId]);
    }

    public function test_store_validates_required_fields(): void
    {
        $response = $this->postJson('/api/v1/products', []);

        $response->assertStatus(422)
            ->assertJsonStructure(['status', 'message', 'errors']);
    }

    public function test_store_rejects_duplicate_sku(): void
    {
        Product::create(['tenant_id' => $this->tenantId, 'sku' => 'DUP-001', 'name' => 'Dupe', 'price' => 1, 'status' => 'active', 'is_active' => true]);

        $this->mock(\App\Webhooks\WebhookDispatcher::class)
            ->shouldReceive('dispatch')->andReturn(null);

        $response = $this->postJson('/api/v1/products', [
            'sku'       => 'DUP-001',
            'name'      => 'Another Dupe',
            'price'     => 5.00,
            'is_active' => true,
        ]);

        $response->assertStatus(422);
    }

    // -------------------------------------------------------------------------
    // Update
    // -------------------------------------------------------------------------

    public function test_update_modifies_product(): void
    {
        $product = Product::create([
            'tenant_id' => $this->tenantId,
            'sku'       => 'UPD-001',
            'name'      => 'Before',
            'price'     => 10.00,
            'status'    => 'active',
            'is_active' => true,
        ]);

        $this->mock(\App\Webhooks\WebhookDispatcher::class)
            ->shouldReceive('dispatch')->andReturn(null);

        $response = $this->patchJson("/api/v1/products/{$product->id}", ['name' => 'After']);

        $response->assertStatus(200)->assertJsonPath('name', 'After');
        $this->assertDatabaseHas('products', ['id' => $product->id, 'name' => 'After']);
    }

    // -------------------------------------------------------------------------
    // Destroy
    // -------------------------------------------------------------------------

    public function test_destroy_soft_deletes_product(): void
    {
        $product = Product::create([
            'tenant_id' => $this->tenantId,
            'sku'       => 'DEL-001',
            'name'      => 'To Delete',
            'price'     => 5.00,
            'status'    => 'active',
            'is_active' => true,
        ]);

        $this->mock(\App\Webhooks\WebhookDispatcher::class)
            ->shouldReceive('dispatch')->andReturn(null);

        $response = $this->deleteJson("/api/v1/products/{$product->id}");

        $response->assertStatus(200)->assertJsonPath('status', 'success');
        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }

    // -------------------------------------------------------------------------
    // Tenant isolation
    // -------------------------------------------------------------------------

    public function test_tenant_isolation_prevents_cross_tenant_access(): void
    {
        $otherTenantProduct = Product::withoutGlobalScope('tenant')->create([
            'tenant_id' => 'other-tenant-999',
            'sku'       => 'OTHER-001',
            'name'      => 'Other Tenant Product',
            'price'     => 100.00,
            'status'    => 'active',
            'is_active' => true,
        ]);

        $this->getJson("/api/v1/products/{$otherTenantProduct->id}")->assertStatus(404);
    }
}
