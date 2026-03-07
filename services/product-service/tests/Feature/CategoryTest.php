<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsTenant();
    }

    private function createCategory(array $overrides = []): Category
    {
        return Category::create(array_merge([
            'tenant_id'  => $this->tenantId,
            'name'       => 'Default Category',
            'slug'       => 'default-category-'.uniqid(),
            'is_active'  => true,
            'sort_order' => 0,
        ], $overrides));
    }

    // -------------------------------------------------------------------------
    // Index
    // -------------------------------------------------------------------------

    public function test_index_returns_paginated_categories(): void
    {
        $this->createCategory(['name' => 'Cat A', 'slug' => 'cat-a']);
        $this->createCategory(['name' => 'Cat B', 'slug' => 'cat-b']);

        $response = $this->getJson('/api/v1/categories');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [['id', 'name', 'slug', 'is_active']],
                'meta',
                'links',
            ]);

        $this->assertGreaterThanOrEqual(2, count($response->json('data')));
    }

    public function test_index_filters_by_is_active(): void
    {
        $this->createCategory(['name' => 'Active Cat', 'slug' => 'active-cat', 'is_active' => true]);
        $this->createCategory(['name' => 'Inactive Cat', 'slug' => 'inactive-cat', 'is_active' => false]);

        $response = $this->getJson('/api/v1/categories?filter[is_active]=1');
        $response->assertStatus(200);

        foreach ($response->json('data') as $cat) {
            $this->assertTrue($cat['is_active']);
        }
    }

    // -------------------------------------------------------------------------
    // Show
    // -------------------------------------------------------------------------

    public function test_show_returns_category(): void
    {
        $category = $this->createCategory(['name' => 'Show Cat', 'slug' => 'show-cat']);

        $this->getJson("/api/v1/categories/{$category->id}")
            ->assertStatus(200)
            ->assertJsonPath('id', $category->id)
            ->assertJsonPath('slug', 'show-cat');
    }

    public function test_show_returns_404_for_nonexistent(): void
    {
        $this->getJson('/api/v1/categories/99999')->assertStatus(404);
    }

    // -------------------------------------------------------------------------
    // Store
    // -------------------------------------------------------------------------

    public function test_store_creates_category(): void
    {
        $response = $this->postJson('/api/v1/categories', [
            'name'       => 'New Category',
            'slug'       => 'new-category',
            'is_active'  => true,
            'sort_order' => 1,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('name', 'New Category')
            ->assertJsonPath('slug', 'new-category');

        $this->assertDatabaseHas('categories', ['slug' => 'new-category', 'tenant_id' => $this->tenantId]);
    }

    public function test_store_auto_generates_slug_from_name(): void
    {
        $response = $this->postJson('/api/v1/categories', [
            'name'      => 'Auto Slug Test',
            'is_active' => true,
        ]);

        $response->assertStatus(201);
        $this->assertEquals('auto-slug-test', $response->json('slug'));
    }

    public function test_store_validates_required_name(): void
    {
        $this->postJson('/api/v1/categories', [])->assertStatus(422);
    }

    public function test_store_rejects_duplicate_slug(): void
    {
        $this->createCategory(['name' => 'Dupe Cat', 'slug' => 'dupe-slug']);

        $response = $this->postJson('/api/v1/categories', [
            'name' => 'Another Cat',
            'slug' => 'dupe-slug',
        ]);

        $response->assertStatus(422);
    }

    // -------------------------------------------------------------------------
    // Update
    // -------------------------------------------------------------------------

    public function test_update_modifies_category(): void
    {
        $category = $this->createCategory(['name' => 'Old Name', 'slug' => 'old-name']);

        $this->patchJson("/api/v1/categories/{$category->id}", ['name' => 'New Name'])
            ->assertStatus(200)
            ->assertJsonPath('name', 'New Name');

        $this->assertDatabaseHas('categories', ['id' => $category->id, 'name' => 'New Name']);
    }

    // -------------------------------------------------------------------------
    // Destroy
    // -------------------------------------------------------------------------

    public function test_destroy_deletes_category(): void
    {
        $category = $this->createCategory(['name' => 'To Delete', 'slug' => 'to-delete']);

        $this->deleteJson("/api/v1/categories/{$category->id}")
            ->assertStatus(200)
            ->assertJsonPath('status', 'success');

        $this->assertSoftDeleted('categories', ['id' => $category->id]);
    }

    public function test_destroy_fails_when_category_has_products(): void
    {
        $category = $this->createCategory(['name' => 'Has Products', 'slug' => 'has-products']);

        Product::create([
            'tenant_id'   => $this->tenantId,
            'category_id' => $category->id,
            'sku'         => 'CAT-PROD-001',
            'name'        => 'Product in category',
            'price'       => 5.00,
            'status'      => 'active',
            'is_active'   => true,
        ]);

        $this->deleteJson("/api/v1/categories/{$category->id}")->assertStatus(422);
    }

    // -------------------------------------------------------------------------
    // Tenant isolation
    // -------------------------------------------------------------------------

    public function test_tenant_isolation(): void
    {
        $other = Category::withoutGlobalScope('tenant')->create([
            'tenant_id'  => 'other-tenant-888',
            'name'       => 'Foreign Cat',
            'slug'       => 'foreign-cat',
            'is_active'  => true,
            'sort_order' => 0,
        ]);

        $this->getJson("/api/v1/categories/{$other->id}")->assertStatus(404);
    }
}
