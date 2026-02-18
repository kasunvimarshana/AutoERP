<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Inventory;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Modules\IAM\Models\User;
use Modules\Inventory\Models\Product;
use Modules\Inventory\Models\Warehouse;
use Tests\TestCase;

class ProductApiTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create and authenticate a test user
        $this->user = User::factory()->create();
        $this->actingAs($this->user, 'sanctum');
    }

    /** @test */
    public function it_can_list_products(): void
    {
        // Arrange
        Product::factory()->count(3)->create();

        // Act
        $response = $this->getJson('/api/inventory/products');

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'sku',
                        'type',
                        'cost_price',
                        'selling_price',
                        'status',
                    ]
                ],
                'meta' => [
                    'current_page',
                    'total',
                ]
            ]);
    }

    /** @test */
    public function it_can_create_product(): void
    {
        // Arrange
        $productData = [
            'name' => 'New Product',
            'sku' => 'NP-001',
            'type' => 'inventory',
            'description' => 'Test product description',
            'cost_price' => 100.00,
            'selling_price' => 150.00,
            'reorder_point' => 10,
            'status' => 'active',
        ];

        // Act
        $response = $this->postJson('/api/inventory/products', $productData);

        // Assert
        $response->assertStatus(201)
            ->assertJsonFragment([
                'name' => 'New Product',
                'sku' => 'NP-001',
            ]);

        $this->assertDatabaseHas('products', [
            'name' => 'New Product',
            'sku' => 'NP-001',
        ]);
    }

    /** @test */
    public function it_validates_required_fields_when_creating_product(): void
    {
        // Act
        $response = $this->postJson('/api/inventory/products', []);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'type']);
    }

    /** @test */
    public function it_can_show_product_details(): void
    {
        // Arrange
        $product = Product::factory()->create();

        // Act
        $response = $this->getJson("/api/inventory/products/{$product->id}");

        // Assert
        $response->assertStatus(200)
            ->assertJsonFragment([
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
            ]);
    }

    /** @test */
    public function it_returns_404_for_nonexistent_product(): void
    {
        // Act
        $response = $this->getJson('/api/inventory/products/999999');

        // Assert
        $response->assertStatus(404);
    }

    /** @test */
    public function it_can_update_product(): void
    {
        // Arrange
        $product = Product::factory()->create([
            'name' => 'Original Name',
            'selling_price' => 100.00,
        ]);

        $updateData = [
            'name' => 'Updated Name',
            'selling_price' => 150.00,
        ];

        // Act
        $response = $this->putJson("/api/inventory/products/{$product->id}", $updateData);

        // Assert
        $response->assertStatus(200)
            ->assertJsonFragment([
                'name' => 'Updated Name',
                'selling_price' => 150.00,
            ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Updated Name',
            'selling_price' => 150.00,
        ]);
    }

    /** @test */
    public function it_can_delete_product(): void
    {
        // Arrange
        $product = Product::factory()->create();

        // Act
        $response = $this->deleteJson("/api/inventory/products/{$product->id}");

        // Assert
        $response->assertStatus(204);
        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }

    /** @test */
    public function it_can_search_products(): void
    {
        // Arrange
        Product::factory()->create(['name' => 'Laptop Computer', 'sku' => 'LAP-001']);
        Product::factory()->create(['name' => 'Desktop Computer', 'sku' => 'DES-001']);
        Product::factory()->create(['name' => 'Mouse', 'sku' => 'MOU-001']);

        // Act
        $response = $this->getJson('/api/inventory/products?search=Computer');

        // Assert
        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(2, $data);
    }

    /** @test */
    public function it_can_filter_products_by_status(): void
    {
        // Arrange
        Product::factory()->count(3)->create(['status' => 'active']);
        Product::factory()->count(2)->create(['status' => 'inactive']);

        // Act
        $response = $this->getJson('/api/inventory/products?status=active');

        // Assert
        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(3, $data);
    }

    /** @test */
    public function it_can_get_low_stock_products(): void
    {
        // Arrange
        Product::factory()->create(['reorder_point' => 10]);
        Product::factory()->create(['reorder_point' => 5]);

        // Act
        $response = $this->getJson('/api/inventory/products/low-stock');

        // Assert
        $response->assertStatus(200);
    }

    /** @test */
    public function it_enforces_unique_sku_constraint(): void
    {
        // Arrange
        Product::factory()->create(['sku' => 'UNIQUE-001']);

        $duplicateData = [
            'name' => 'Another Product',
            'sku' => 'UNIQUE-001',
            'type' => 'inventory',
            'cost_price' => 50.00,
            'selling_price' => 75.00,
        ];

        // Act
        $response = $this->postJson('/api/inventory/products', $duplicateData);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['sku']);
    }

    /** @test */
    public function it_can_bulk_import_products(): void
    {
        // Arrange
        $productsData = [
            [
                'name' => 'Product 1',
                'sku' => 'BULK-001',
                'type' => 'inventory',
                'cost_price' => 100.00,
                'selling_price' => 150.00,
            ],
            [
                'name' => 'Product 2',
                'sku' => 'BULK-002',
                'type' => 'inventory',
                'cost_price' => 200.00,
                'selling_price' => 250.00,
            ],
        ];

        // Act
        $response = $this->postJson('/api/inventory/products/bulk-import', [
            'products' => $productsData
        ]);

        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseHas('products', ['sku' => 'BULK-001']);
        $this->assertDatabaseHas('products', ['sku' => 'BULK-002']);
    }
}
