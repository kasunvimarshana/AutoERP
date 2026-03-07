<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Product;
use App\Repositories\Interfaces\ProductRepositoryInterface;
use App\Services\ProductService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Event;
use App\Events\ProductCreated;
use App\Events\ProductUpdated;
use App\Events\ProductDeleted;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    private ProductService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = $this->app->make(ProductService::class);

        // Prevent listeners from attempting a real RabbitMQ connection during tests
        Event::fake([ProductCreated::class, ProductUpdated::class, ProductDeleted::class]);
    }

    // -------------------------------------------------------------------------
    // INDEX
    // -------------------------------------------------------------------------

    public function test_index_returns_paginated_products(): void
    {
        Product::factory()->count(20)->create();

        $result = $this->service->getAllProducts(['per_page' => 10]);

        $this->assertSame(10, $result->perPage());
        $this->assertSame(20, $result->total());
    }

    public function test_index_filters_by_category(): void
    {
        Product::factory()->count(3)->create(['category' => 'Electronics']);
        Product::factory()->count(2)->create(['category' => 'Furniture']);

        $result = $this->service->getAllProducts(['category' => 'Electronics']);

        $this->assertSame(3, $result->total());
        $result->getCollection()->each(
            fn (Product $p) => $this->assertSame('Electronics', $p->category)
        );
    }

    public function test_index_filters_by_status(): void
    {
        Product::factory()->count(4)->create(['status' => 'active']);
        Product::factory()->count(1)->create(['status' => 'inactive']);

        $result = $this->service->getAllProducts(['status' => 'active']);

        $this->assertSame(4, $result->total());
    }

    public function test_index_searches_by_name(): void
    {
        Product::factory()->create(['name' => 'Super Widget']);
        Product::factory()->create(['name' => 'Ordinary Product']);

        $result = $this->service->getAllProducts(['search' => 'Super']);

        $this->assertSame(1, $result->total());
        $this->assertSame('Super Widget', $result->getCollection()->first()->name);
    }

    public function test_index_sorts_by_price_ascending(): void
    {
        Product::factory()->create(['price' => 99.99]);
        Product::factory()->create(['price' => 9.99]);
        Product::factory()->create(['price' => 49.99]);

        $result = $this->service->getAllProducts([
            'sort_by'        => 'price',
            'sort_direction' => 'asc',
        ]);

        $prices = $result->getCollection()->pluck('price')->map(fn ($p) => (float) $p)->toArray();

        $this->assertSame([9.99, 49.99, 99.99], $prices);
    }

    // -------------------------------------------------------------------------
    // SHOW
    // -------------------------------------------------------------------------

    public function test_get_product_by_id_returns_product(): void
    {
        $product = Product::factory()->create();

        $found = $this->service->getProductById($product->id);

        $this->assertNotNull($found);
        $this->assertSame($product->id, $found->id);
    }

    public function test_get_product_by_id_returns_null_for_missing_id(): void
    {
        $result = $this->service->getProductById(99999);

        $this->assertNull($result);
    }

    // -------------------------------------------------------------------------
    // STORE
    // -------------------------------------------------------------------------

    public function test_create_product_persists_and_fires_event(): void
    {
        $data = [
            'name'           => 'Test Product',
            'description'    => 'A test description.',
            'sku'            => 'TEST-SKU-001',
            'price'          => 49.99,
            'category'       => 'Test',
            'status'         => 'active',
            'stock_quantity' => 10,
        ];

        $product = $this->service->createProduct($data);

        $this->assertDatabaseHas('products', ['sku' => 'TEST-SKU-001']);
        $this->assertSame('Test Product', $product->name);

        Event::assertDispatched(ProductCreated::class, function (ProductCreated $e) use ($product): bool {
            return $e->product->id === $product->id;
        });
    }

    public function test_create_product_returns_correct_attributes(): void
    {
        $data = [
            'name'           => 'Widget',
            'sku'            => 'WIDGET-001',
            'price'          => 19.99,
            'category'       => 'Gadgets',
            'status'         => 'active',
            'stock_quantity' => 5,
        ];

        $product = $this->service->createProduct($data);

        $this->assertSame('Widget', $product->name);
        $this->assertSame('WIDGET-001', $product->sku);
        $this->assertSame('Gadgets', $product->category);
        $this->assertSame(5, $product->stock_quantity);
    }

    // -------------------------------------------------------------------------
    // UPDATE
    // -------------------------------------------------------------------------

    public function test_update_product_persists_changes_and_fires_event(): void
    {
        $product = Product::factory()->create(['price' => 10.00]);

        $updated = $this->service->updateProduct($product->id, ['price' => 25.50]);

        $this->assertNotNull($updated);
        $this->assertSame('25.5000', $updated->price);

        $this->assertDatabaseHas('products', ['id' => $product->id, 'price' => 25.50]);

        Event::assertDispatched(ProductUpdated::class, function (ProductUpdated $e) use ($product): bool {
            return $e->product->id === $product->id;
        });
    }

    public function test_update_product_returns_null_for_missing_id(): void
    {
        $result = $this->service->updateProduct(99999, ['name' => 'Ghost']);

        $this->assertNull($result);
    }

    // -------------------------------------------------------------------------
    // DESTROY
    // -------------------------------------------------------------------------

    public function test_delete_product_soft_deletes_and_fires_event(): void
    {
        $product = Product::factory()->create();

        $result = $this->service->deleteProduct($product->id);

        $this->assertTrue($result);
        $this->assertSoftDeleted('products', ['id' => $product->id]);

        Event::assertDispatched(ProductDeleted::class, function (ProductDeleted $e) use ($product): bool {
            return $e->productId === $product->id;
        });
    }

    public function test_delete_product_returns_false_for_missing_id(): void
    {
        $result = $this->service->deleteProduct(99999);

        $this->assertFalse($result);
        Event::assertNotDispatched(ProductDeleted::class);
    }

    // -------------------------------------------------------------------------
    // HTTP Layer
    // -------------------------------------------------------------------------

    public function test_http_index_returns_products_list(): void
    {
        // By-pass auth middleware in unit environment by using withoutMiddleware
        Product::factory()->count(3)->create();

        $response = $this->withoutMiddleware()->getJson('/api/v1/products');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [['id', 'name', 'sku', 'price', 'category', 'status', 'stock_quantity']],
        ]);
    }

    public function test_http_store_validates_required_fields(): void
    {
        $response = $this->withoutMiddleware()->postJson('/api/v1/products', []);

        $response->assertStatus(422);
        $response->assertJsonStructure(['message', 'errors']);
    }

    public function test_http_store_creates_product(): void
    {
        $payload = [
            'name'           => 'HTTP Product',
            'sku'            => 'HTTP-001',
            'price'          => 9.99,
            'category'       => 'Test',
            'status'         => 'active',
            'stock_quantity' => 1,
        ];

        $response = $this->withoutMiddleware()->postJson('/api/v1/products', $payload);

        $response->assertStatus(201);
        $response->assertJsonFragment(['sku' => 'HTTP-001']);
        $this->assertDatabaseHas('products', ['sku' => 'HTTP-001']);
    }

    public function test_http_show_returns_404_for_unknown_product(): void
    {
        $response = $this->withoutMiddleware()->getJson('/api/v1/products/99999');

        $response->assertNotFound();
    }

    public function test_http_update_product(): void
    {
        $product = Product::factory()->create();

        $response = $this->withoutMiddleware()->putJson(
            "/api/v1/products/{$product->id}",
            ['name' => 'Updated Name']
        );

        $response->assertOk();
        $response->assertJsonFragment(['name' => 'Updated Name']);
    }

    public function test_http_destroy_product(): void
    {
        $product = Product::factory()->create();

        $response = $this->withoutMiddleware()->deleteJson("/api/v1/products/{$product->id}");

        $response->assertOk();
        $response->assertJsonFragment(['message' => 'Product deleted successfully.']);
        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }
}
