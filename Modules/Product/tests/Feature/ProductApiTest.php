<?php

declare(strict_types=1);

namespace Modules\Product\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Product\Models\Product;
use Tests\TestCase;

/**
 * Product API Test
 *
 * Tests for Product CRUD operations
 */
class ProductApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create authenticated user for testing
        $this->actingAs($this->createUser());
    }

    /**
     * Test: Can list products
     */
    public function test_can_list_products(): void
    {
        // Arrange: Create test products
        Product::factory()->count(3)->create();

        // Act: Get all products
        $response = $this->getJson('/api/v1/products');

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'sku',
                        'name',
                        'type',
                        'status',
                        'pricing',
                        'inventory',
                    ],
                ],
            ]);
    }

    /**
     * Test: Can create a product
     */
    public function test_can_create_product(): void
    {
        // Arrange
        $data = [
            'name' => 'Test Product',
            'description' => 'Test Description',
            'type' => 'goods',
            'status' => 'active',
            'cost_price' => 100.00,
            'selling_price' => 150.00,
            'track_inventory' => true,
            'current_stock' => 50,
        ];

        // Act
        $response = $this->postJson('/api/v1/products', $data);

        // Assert
        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'sku',
                    'name',
                ],
            ]);

        $this->assertDatabaseHas('products', [
            'name' => 'Test Product',
            'type' => 'goods',
        ]);
    }

    /**
     * Test: Can show a specific product
     */
    public function test_can_show_product(): void
    {
        // Arrange
        $product = Product::factory()->create();

        // Act
        $response = $this->getJson("/api/v1/products/{$product->id}");

        // Assert
        $response->assertStatus(200)
            ->assertJsonFragment([
                'id' => $product->id,
                'name' => $product->name,
            ]);
    }

    /**
     * Test: Can update a product
     */
    public function test_can_update_product(): void
    {
        // Arrange
        $product = Product::factory()->create([
            'name' => 'Original Name',
        ]);

        $updateData = [
            'name' => 'Updated Name',
            'description' => 'Updated Description',
        ];

        // Act
        $response = $this->putJson("/api/v1/products/{$product->id}", $updateData);

        // Assert
        $response->assertStatus(200)
            ->assertJsonFragment([
                'name' => 'Updated Name',
            ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Updated Name',
        ]);
    }

    /**
     * Test: Can delete a product
     */
    public function test_can_delete_product(): void
    {
        // Arrange
        $product = Product::factory()->create();

        // Act
        $response = $this->deleteJson("/api/v1/products/{$product->id}");

        // Assert
        $response->assertStatus(200);
        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }

    /**
     * Test: Validation fails for invalid data
     */
    public function test_validation_fails_for_invalid_data(): void
    {
        // Act
        $response = $this->postJson('/api/v1/products', []);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    /**
     * Test: SKU must be unique
     */
    public function test_sku_must_be_unique(): void
    {
        // Arrange
        $product = Product::factory()->create(['sku' => 'UNIQUE-SKU']);

        // Act
        $response = $this->postJson('/api/v1/products', [
            'name' => 'Test Product',
            'sku' => 'UNIQUE-SKU',
        ]);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['sku']);
    }

    /**
     * Helper: Create authenticated user
     */
    protected function createUser()
    {
        return \App\Models\User::factory()->create();
    }
}
