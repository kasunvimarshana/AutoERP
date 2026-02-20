<?php

namespace Tests\Feature;

use App\Enums\TenantStatus;
use App\Models\ProductCategory;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductCategoryTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create(['status' => TenantStatus::Active]);
        $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);
    }

    public function test_unauthenticated_cannot_list_product_categories(): void
    {
        $this->getJson('/api/v1/product-categories')->assertStatus(401);
    }

    public function test_authenticated_user_can_list_product_categories(): void
    {
        $this->actingAs($this->user, 'api')
            ->getJson('/api/v1/product-categories')
            ->assertStatus(200);
    }

    public function test_authenticated_user_can_get_category_tree(): void
    {
        $parent = ProductCategory::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Electronics',
            'slug' => 'electronics',
        ]);

        ProductCategory::factory()->create([
            'tenant_id' => $this->tenant->id,
            'parent_id' => $parent->id,
            'name' => 'Phones',
            'slug' => 'phones',
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->getJson('/api/v1/product-categories/tree')
            ->assertStatus(200);

        $tree = $response->json();
        $this->assertIsArray($tree);
        $this->assertCount(1, $tree);
        $this->assertEquals('Electronics', $tree[0]['name']);
        $this->assertCount(1, $tree[0]['children_list']);
        $this->assertEquals('Phones', $tree[0]['children_list'][0]['name']);
    }

    public function test_deleting_parent_orphans_children_to_root(): void
    {
        $parent = ProductCategory::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Parent Cat',
            'slug' => 'parent-cat',
        ]);

        $child = ProductCategory::factory()->create([
            'tenant_id' => $this->tenant->id,
            'parent_id' => $parent->id,
            'name' => 'Child Cat',
            'slug' => 'child-cat',
        ]);

        $this->actingAs($this->user, 'api')
            ->deleteJson("/api/v1/product-categories/{$parent->id}")
            ->assertStatus(403); // user lacks products.manage permission by default

        // Verify the re-parenting logic by calling the service directly
        app(\App\Services\ProductCategoryService::class)->delete($parent->id);

        $this->assertNull($child->fresh()->parent_id);
        $this->assertSoftDeleted('product_categories', ['id' => $parent->id]);
    }

    public function test_unauthenticated_cannot_create_product_category(): void
    {
        $this->postJson('/api/v1/product-categories', ['name' => 'Electronics'])->assertStatus(401);
    }
}
