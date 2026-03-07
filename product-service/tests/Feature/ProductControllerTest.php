<?php

namespace Tests\Feature;

use App\Events\ProductCreated;
use App\Events\ProductDeleted;
use App\Events\ProductUpdated;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * ProductControllerTest
 *
 * Feature tests for the Product Service CRUD endpoints.
 * Events are faked so RabbitMQ is not needed during testing.
 */
class ProductControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Fake all events so listeners (RabbitMQ publish) are not called
        Event::fake([
            ProductCreated::class,
            ProductUpdated::class,
            ProductDeleted::class,
        ]);
    }

    /**
     * Test listing products returns paginated response.
     */
    public function test_index_returns_paginated_products(): void
    {
        Product::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/products');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
                'meta' => ['current_page', 'per_page', 'total', 'last_page'],
            ])
            ->assertJson(['success' => true]);
    }

    /**
     * Test creating a product fires ProductCreated event.
     */
    public function test_store_creates_product_and_fires_event(): void
    {
        $data = [
            'name'        => 'Test Product',
            'description' => 'A test product',
            'price'       => 29.99,
            'stock'       => 100,
            'sku'         => 'TEST-001',
            'category'    => 'Electronics',
        ];

        $response = $this->postJson('/api/v1/products', $data);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Product created successfully.',
            ])
            ->assertJsonStructure([
                'data' => ['id', 'name', 'price', 'stock', 'inventory'],
            ]);

        $this->assertDatabaseHas('products', ['name' => 'Test Product', 'sku' => 'TEST-001']);

        Event::assertDispatched(ProductCreated::class, function ($event) {
            return $event->product->name === 'Test Product';
        });
    }

    /**
     * Test creating a product with invalid data returns validation errors.
     */
    public function test_store_validates_required_fields(): void
    {
        $response = $this->postJson('/api/v1/products', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'price', 'stock']);
    }

    /**
     * Test creating a product with duplicate SKU returns validation error.
     */
    public function test_store_rejects_duplicate_sku(): void
    {
        Product::factory()->create(['sku' => 'DUPE-001']);

        $response = $this->postJson('/api/v1/products', [
            'name'  => 'Another Product',
            'price' => 9.99,
            'stock' => 10,
            'sku'   => 'DUPE-001',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['sku']);
    }

    /**
     * Test showing a single product.
     */
    public function test_show_returns_product(): void
    {
        $product = Product::factory()->create();

        $response = $this->getJson("/api/v1/products/{$product->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data'    => [
                    'id'   => $product->id,
                    'name' => $product->name,
                ],
            ])
            ->assertJsonStructure(['data' => ['inventory']]);
    }

    /**
     * Test showing a non-existent product returns 404.
     */
    public function test_show_returns_404_for_nonexistent_product(): void
    {
        $response = $this->getJson('/api/v1/products/99999');

        $response->assertStatus(404)
            ->assertJson(['success' => false, 'message' => 'Product not found.']);
    }

    /**
     * Test updating a product fires ProductUpdated event.
     */
    public function test_update_modifies_product_and_fires_event(): void
    {
        $product = Product::factory()->create(['name' => 'Old Name', 'price' => 10.00]);

        $response = $this->putJson("/api/v1/products/{$product->id}", [
            'name'  => 'New Name',
            'price' => 25.00,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Product updated successfully.',
                'data'    => ['name' => 'New Name'],
            ]);

        $this->assertDatabaseHas('products', ['id' => $product->id, 'name' => 'New Name']);

        Event::assertDispatched(ProductUpdated::class, function ($event) use ($product) {
            return $event->product->id === $product->id
                && $event->previousData['name'] === 'Old Name';
        });
    }

    /**
     * Test deleting a product fires ProductDeleted event.
     */
    public function test_destroy_deletes_product_and_fires_event(): void
    {
        $product = Product::factory()->create(['name' => 'To Delete']);

        $response = $this->deleteJson("/api/v1/products/{$product->id}");

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertSoftDeleted('products', ['id' => $product->id]);

        Event::assertDispatched(ProductDeleted::class, function ($event) use ($product) {
            return $event->productId === $product->id
                && $event->productName === 'To Delete';
        });
    }

    /**
     * Test deleting a non-existent product returns 404.
     */
    public function test_destroy_returns_404_for_nonexistent_product(): void
    {
        $response = $this->deleteJson('/api/v1/products/99999');

        $response->assertStatus(404);
    }

    /**
     * Test index supports filtering by category.
     */
    public function test_index_filters_by_category(): void
    {
        Product::factory()->create(['category' => 'Electronics']);
        Product::factory()->create(['category' => 'Books']);

        $response = $this->getJson('/api/v1/products?category=Electronics');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('Electronics', $data[0]['category']);
    }

    /**
     * Test index supports search across name and SKU.
     */
    public function test_index_supports_search(): void
    {
        Product::factory()->create(['name' => 'Laptop Pro', 'sku' => 'LP-001']);
        Product::factory()->create(['name' => 'Mouse', 'sku' => 'MS-001']);

        $response = $this->getJson('/api/v1/products?search=Laptop');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('Laptop Pro', $data[0]['name']);
    }
}
