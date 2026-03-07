<?php

namespace Tests\Feature\Api\V1;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Services\PassportTokenValidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;
    private int $tenantId = 1;
    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        app()->instance('current_tenant_id', $this->tenantId);

        $this->adminUser = User::create([
            'tenant_id' => $this->tenantId,
            'name' => 'Admin',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'roles' => ['admin'],
            'is_active' => true,
        ]);

        $this->category = Category::create([
            'tenant_id' => $this->tenantId,
            'name' => 'Test Category',
            'slug' => 'test-category',
            'is_active' => true,
        ]);

        $mock = Mockery::mock(PassportTokenValidationService::class);
        $mock->shouldReceive('validate')->andReturn([
            'active' => true,
            'user_id' => $this->adminUser->id,
            'tenant_id' => $this->tenantId,
            'email' => $this->adminUser->email,
            'name' => $this->adminUser->name,
            'roles' => ['admin'],
            'attributes' => [],
        ]);
        app()->instance(PassportTokenValidationService::class, $mock);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_health_endpoint(): void
    {
        $this->getJson('/api/health')
            ->assertStatus(200)
            ->assertJson(['status' => 'ok', 'service' => 'product-service']);
    }

    public function test_list_products(): void
    {
        Product::create([
            'tenant_id' => $this->tenantId,
            'name' => 'Test Product',
            'sku' => 'TEST-001',
            'price' => 100.00,
            'is_active' => true,
        ]);

        $this->withToken('fake-token')
            ->getJson('/api/v1/products')
            ->assertStatus(200)
            ->assertJsonStructure(['data']);
    }

    public function test_list_products_with_pagination(): void
    {
        $this->withToken('fake-token')
            ->getJson('/api/v1/products?per_page=5')
            ->assertStatus(200)
            ->assertJsonStructure(['data', 'meta', 'links']);
    }

    public function test_list_products_without_per_page_returns_all(): void
    {
        Product::create(['tenant_id' => $this->tenantId, 'name' => 'Product A', 'sku' => 'A-001', 'price' => 10.00]);
        Product::create(['tenant_id' => $this->tenantId, 'name' => 'Product B', 'sku' => 'B-001', 'price' => 20.00]);

        $response = $this->withToken('fake-token')
            ->getJson('/api/v1/products')
            ->assertStatus(200)
            ->assertJsonStructure(['data']);

        $this->assertArrayNotHasKey('meta', $response->json());
        $this->assertArrayNotHasKey('links', $response->json());
        $this->assertCount(2, $response->json('data'));
    }

    public function test_create_product(): void
    {
        $this->withToken('fake-token')
            ->postJson('/api/v1/products', [
                'name' => 'New Product',
                'sku' => 'NEW-001',
                'price' => 199.99,
                'category_id' => $this->category->id,
                'description' => 'A new product',
            ])
            ->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'data' => ['id', 'name', 'sku', 'price'],
            ]);
    }

    public function test_create_product_with_duplicate_sku_fails(): void
    {
        Product::create([
            'tenant_id' => $this->tenantId,
            'name' => 'Existing Product',
            'sku' => 'DUP-001',
            'price' => 50.00,
        ]);

        $this->withToken('fake-token')
            ->postJson('/api/v1/products', [
                'name' => 'Duplicate SKU Product',
                'sku' => 'DUP-001',
                'price' => 60.00,
            ])
            ->assertStatus(422);
    }

    public function test_get_product(): void
    {
        $product = Product::create([
            'tenant_id' => $this->tenantId,
            'name' => 'Test Product',
            'sku' => 'GET-001',
            'price' => 75.00,
        ]);

        $this->withToken('fake-token')
            ->getJson("/api/v1/products/{$product->id}")
            ->assertStatus(200)
            ->assertJsonFragment(['sku' => 'GET-001']);
    }

    public function test_update_product(): void
    {
        $product = Product::create([
            'tenant_id' => $this->tenantId,
            'name' => 'Old Name',
            'sku' => 'UPD-001',
            'price' => 100.00,
        ]);

        $this->withToken('fake-token')
            ->putJson("/api/v1/products/{$product->id}", ['name' => 'New Name', 'price' => 150.00])
            ->assertStatus(200)
            ->assertJsonFragment(['name' => 'New Name']);
    }

    public function test_delete_product(): void
    {
        $product = Product::create([
            'tenant_id' => $this->tenantId,
            'name' => 'Delete Me',
            'sku' => 'DEL-001',
            'price' => 100.00,
        ]);

        $this->withToken('fake-token')
            ->deleteJson("/api/v1/products/{$product->id}")
            ->assertStatus(204);

        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }

    public function test_list_categories(): void
    {
        $this->withToken('fake-token')
            ->getJson('/api/v1/categories')
            ->assertStatus(200);
    }

    public function test_list_categories_with_pagination(): void
    {
        $this->withToken('fake-token')
            ->getJson('/api/v1/categories?per_page=5')
            ->assertStatus(200)
            ->assertJsonStructure(['data', 'meta', 'links']);
    }

    public function test_list_categories_without_per_page_returns_all(): void
    {
        Category::create([
            'tenant_id' => $this->tenantId,
            'name' => 'Second Category',
            'slug' => 'second-category',
            'is_active' => true,
        ]);

        $response = $this->withToken('fake-token')
            ->getJson('/api/v1/categories')
            ->assertStatus(200)
            ->assertJsonStructure(['data']);

        $this->assertArrayNotHasKey('meta', $response->json());
        $this->assertArrayNotHasKey('links', $response->json());
        $this->assertCount(2, $response->json('data'));
    }

    public function test_unauthenticated_request_rejected(): void
    {
        $this->getJson('/api/v1/products')->assertStatus(401);
    }
}
