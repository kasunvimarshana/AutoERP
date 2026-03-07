<?php

namespace App\Modules\Product\Tests;

use App\Modules\Product\Models\Product;
use App\Modules\Tenant\Models\Tenant;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant    = Tenant::factory()->create(['is_active' => true]);
        $this->adminUser = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'role'      => 'admin',
        ]);
        $this->adminUser->assignRole('admin');
    }

    public function test_can_list_products(): void
    {
        Passport::actingAs($this->adminUser);
        Product::factory()->count(5)->create(['tenant_id' => $this->tenant->id]);

        $response = $this->getJson('/api/v1/products', ['X-Tenant-ID' => $this->tenant->id]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['data', 'meta']);
    }

    public function test_can_create_product(): void
    {
        Passport::actingAs($this->adminUser);

        $response = $this->postJson('/api/v1/products', [
            'name'       => 'Test Widget',
            'sku'        => 'TWG-001',
            'price'      => 29.99,
            'cost_price' => 15.00,
            'category'   => 'electronics',
            'unit'       => 'pcs',
        ], ['X-Tenant-ID' => $this->tenant->id]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.sku', 'TWG-001');
    }

    public function test_can_update_product(): void
    {
        Passport::actingAs($this->adminUser);
        $product = Product::factory()->create(['tenant_id' => $this->tenant->id]);

        $response = $this->putJson("/api/v1/products/{$product->id}", [
            'price' => 49.99,
        ], ['X-Tenant-ID' => $this->tenant->id]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.price', 49.99);
    }

    public function test_can_delete_product(): void
    {
        Passport::actingAs($this->adminUser);
        $product = Product::factory()->create(['tenant_id' => $this->tenant->id]);

        $response = $this->deleteJson("/api/v1/products/{$product->id}", [], ['X-Tenant-ID' => $this->tenant->id]);

        $response->assertStatus(204);
    }

    public function test_can_search_products(): void
    {
        Passport::actingAs($this->adminUser);
        Product::factory()->create(['tenant_id' => $this->tenant->id, 'name' => 'Special Gadget']);
        Product::factory()->create(['tenant_id' => $this->tenant->id, 'name' => 'Regular Item']);

        $response = $this->getJson('/api/v1/products?search=Special', ['X-Tenant-ID' => $this->tenant->id]);

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
    }

    public function test_can_filter_by_category(): void
    {
        Passport::actingAs($this->adminUser);
        Product::factory()->count(3)->create(['tenant_id' => $this->tenant->id, 'category' => 'electronics']);
        Product::factory()->count(2)->create(['tenant_id' => $this->tenant->id, 'category' => 'clothing']);

        $response = $this->getJson('/api/v1/products?filter[category]=electronics', ['X-Tenant-ID' => $this->tenant->id]);

        $response->assertStatus(200);
        $response->assertJsonCount(3, 'data');
    }
}
