<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Modules\Tenancy\Models\Tenant;
use App\Modules\Inventory\Models\Product;
use App\Modules\Inventory\Models\ProductCategory;
use App\Modules\Inventory\Models\Brand;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create([
            'name' => 'Test Tenant',
            'slug' => 'test-tenant',
            'status' => 'active',
        ]);

        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);
    }

    public function test_can_create_product(): void
    {
        $category = ProductCategory::factory()->create(['tenant_id' => $this->tenant->id]);

        $productData = [
            'sku' => 'PROD-001',
            'name' => 'Test Product',
            'category_id' => $category->id,
            'unit_price' => 99.99,
            'cost_price' => 50.00,
            'track_inventory' => true,
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/products', $productData);

        $response->assertStatus(201);
        
        $this->assertDatabaseHas('products', [
            'sku' => 'PROD-001',
            'tenant_id' => $this->tenant->id,
        ]);
    }

    public function test_can_list_products(): void
    {
        Product::factory()->count(3)->create(['tenant_id' => $this->tenant->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/products');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_can_get_single_product(): void
    {
        $product = Product::factory()->create(['tenant_id' => $this->tenant->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/products/{$product->id}");

        $response->assertStatus(200)
            ->assertJsonFragment(['sku' => $product->sku]);
    }

    public function test_can_update_product(): void
    {
        $product = Product::factory()->create(['tenant_id' => $this->tenant->id]);

        $updateData = [
            'unit_price' => 149.99,
            'name' => 'Updated Product Name',
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/v1/products/{$product->id}", $updateData);

        $response->assertStatus(200);
        
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'unit_price' => 149.99,
            'name' => 'Updated Product Name',
        ]);
    }

    public function test_can_delete_product(): void
    {
        $product = Product::factory()->create(['tenant_id' => $this->tenant->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/products/{$product->id}");

        $response->assertStatus(200);
        
        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }

    public function test_cannot_create_product_with_duplicate_sku(): void
    {
        Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'sku' => 'DUPLICATE-SKU'
        ]);

        $category = ProductCategory::factory()->create(['tenant_id' => $this->tenant->id]);

        $productData = [
            'sku' => 'DUPLICATE-SKU',
            'name' => 'Test Product',
            'category_id' => $category->id,
            'unit_price' => 99.99,
            'cost_price' => 50.00,
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/products', $productData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['sku']);
    }

    public function test_can_search_products(): void
    {
        Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Laptop Computer',
            'sku' => 'LAP-001'
        ]);

        Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Mouse',
            'sku' => 'MOU-001'
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/products/search?q=Laptop');

        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'Laptop Computer']);
    }

    public function test_can_filter_products_by_category(): void
    {
        $category = ProductCategory::factory()->create(['tenant_id' => $this->tenant->id]);
        $otherCategory = ProductCategory::factory()->create(['tenant_id' => $this->tenant->id]);

        Product::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $category->id
        ]);

        Product::factory()->count(2)->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $otherCategory->id
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/products?category_id={$category->id}");

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_tenant_isolation_works(): void
    {
        $otherTenant = Tenant::factory()->create([
            'name' => 'Other Tenant',
            'slug' => 'other-tenant',
            'status' => 'active',
        ]);

        $otherProduct = Product::factory()->create([
            'tenant_id' => $otherTenant->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/products/{$otherProduct->id}");

        $response->assertStatus(404);
    }

    public function test_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/products');

        $response->assertStatus(401);
    }

    public function test_product_can_have_brand(): void
    {
        $brand = Brand::factory()->create(['tenant_id' => $this->tenant->id]);
        $category = ProductCategory::factory()->create(['tenant_id' => $this->tenant->id]);

        $productData = [
            'sku' => 'PROD-BRAND-001',
            'name' => 'Branded Product',
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'unit_price' => 99.99,
            'cost_price' => 50.00,
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/products', $productData);

        $response->assertStatus(201);
        
        $this->assertDatabaseHas('products', [
            'brand_id' => $brand->id,
        ]);
    }

    public function test_can_manage_product_inventory_tracking(): void
    {
        $product = Product::factory()->trackInventory()->create([
            'tenant_id' => $this->tenant->id,
            'min_stock_level' => 10,
            'max_stock_level' => 100,
            'reorder_point' => 20,
        ]);

        $this->assertTrue($product->track_inventory);
        $this->assertEquals(10, $product->min_stock_level);
        $this->assertEquals(100, $product->max_stock_level);
        $this->assertEquals(20, $product->reorder_point);
    }
}
