<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Modules\Tenancy\Models\Tenant;
use App\Modules\Inventory\Models\ProductCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProductCategoryTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();

        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);
    }

    public function test_can_create_category(): void
    {
        $categoryData = [
            'name' => 'Electronics',
            'slug' => 'electronics',
            'description' => 'Electronic products',
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/product-categories', $categoryData);

        $response->assertStatus(201);
        
        $this->assertDatabaseHas('product_categories', [
            'name' => 'Electronics',
            'tenant_id' => $this->tenant->id,
        ]);
    }

    public function test_can_create_nested_category(): void
    {
        $parent = ProductCategory::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Electronics',
        ]);

        $categoryData = [
            'parent_id' => $parent->id,
            'name' => 'Laptops',
            'slug' => 'laptops',
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/product-categories', $categoryData);

        $response->assertStatus(201);
        
        $this->assertDatabaseHas('product_categories', [
            'parent_id' => $parent->id,
            'name' => 'Laptops',
        ]);
    }

    public function test_can_list_categories(): void
    {
        ProductCategory::factory()->count(5)->create(['tenant_id' => $this->tenant->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/product-categories');

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data');
    }

    public function test_can_update_category(): void
    {
        $category = ProductCategory::factory()->create(['tenant_id' => $this->tenant->id]);

        $updateData = [
            'name' => 'Updated Category',
            'description' => 'Updated description',
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/v1/product-categories/{$category->id}", $updateData);

        $response->assertStatus(200);
        
        $this->assertDatabaseHas('product_categories', [
            'id' => $category->id,
            'name' => 'Updated Category',
        ]);
    }

    public function test_can_delete_category(): void
    {
        $category = ProductCategory::factory()->create(['tenant_id' => $this->tenant->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/product-categories/{$category->id}");

        $response->assertStatus(200);
        
        $this->assertDatabaseMissing('product_categories', ['id' => $category->id]);
    }

    public function test_category_has_hierarchical_structure(): void
    {
        $parent = ProductCategory::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Parent Category',
        ]);

        $child1 = ProductCategory::factory()->create([
            'tenant_id' => $this->tenant->id,
            'parent_id' => $parent->id,
            'name' => 'Child 1',
        ]);

        $child2 = ProductCategory::factory()->create([
            'tenant_id' => $this->tenant->id,
            'parent_id' => $parent->id,
            'name' => 'Child 2',
        ]);

        $this->assertEquals(2, $parent->children()->count());
        $this->assertEquals($parent->id, $child1->parent->id);
        $this->assertEquals($parent->id, $child2->parent->id);
    }

    public function test_tenant_isolation_works(): void
    {
        $otherTenant = Tenant::factory()->create();

        $otherCategory = ProductCategory::factory()->create([
            'tenant_id' => $otherTenant->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/v1/product-categories/{$otherCategory->id}");

        $response->assertStatus(404);
    }

    public function test_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/product-categories');

        $response->assertStatus(401);
    }
}
