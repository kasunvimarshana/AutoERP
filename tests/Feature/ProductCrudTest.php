<?php

namespace Tests\Feature;

use App\Enums\TenantStatus;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ProductCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $staff;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create(['status' => TenantStatus::Active]);
        $this->admin = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->staff = User::factory()->create(['tenant_id' => $this->tenant->id]);

        // Grant admin full product permissions (using 'api' guard for JWT)
        $viewPerm = Permission::firstOrCreate(['name' => 'products.view', 'guard_name' => 'api']);
        $createPerm = Permission::firstOrCreate(['name' => 'products.create', 'guard_name' => 'api']);
        $updatePerm = Permission::firstOrCreate(['name' => 'products.update', 'guard_name' => 'api']);
        $deletePerm = Permission::firstOrCreate(['name' => 'products.delete', 'guard_name' => 'api']);

        $this->admin->givePermissionTo([$viewPerm, $createPerm, $updatePerm, $deletePerm]);
    }

    public function test_admin_can_create_product(): void
    {
        $response = $this->actingAs($this->admin, 'api')
            ->postJson('/api/v1/products', [
                'type' => 'goods',
                'name' => 'Test Widget',
                'sku' => 'WIDGET-001',
                'base_price' => 29.99,
                'currency' => 'USD',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Test Widget')
            ->assertJsonPath('data.sku', 'WIDGET-001');
    }

    public function test_user_without_permission_cannot_create_product(): void
    {
        $this->actingAs($this->staff, 'api')
            ->postJson('/api/v1/products', [
                'type' => 'goods',
                'name' => 'Test Widget',
            ])
            ->assertStatus(403);
    }

    public function test_admin_can_list_own_tenant_products(): void
    {
        // Create a product for this tenant
        $this->actingAs($this->admin, 'api')
            ->postJson('/api/v1/products', [
                'type' => 'goods',
                'name' => 'Tenant Product',
                'sku' => 'TP-001',
            ]);

        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/v1/products');

        $response->assertStatus(200)
            ->assertJsonStructure(['data', 'meta', 'links'])
            ->assertJsonPath('meta.total', 1);
    }

    public function test_admin_can_update_product(): void
    {
        // Create product first
        $createResponse = $this->actingAs($this->admin, 'api')
            ->postJson('/api/v1/products', [
                'type' => 'goods',
                'name' => 'Original Name',
                'sku' => 'ORIG-001',
            ]);

        $productId = $createResponse->json('data.id');

        $updateResponse = $this->actingAs($this->admin, 'api')
            ->putJson("/api/v1/products/{$productId}", [
                'name' => 'Updated Name',
                'base_price' => 49.99,
            ]);

        $updateResponse->assertStatus(200)
            ->assertJsonPath('data.name', 'Updated Name');
    }

    public function test_admin_can_delete_product(): void
    {
        $createResponse = $this->actingAs($this->admin, 'api')
            ->postJson('/api/v1/products', [
                'type' => 'goods',
                'name' => 'To Delete',
                'sku' => 'DEL-001',
            ]);

        $productId = $createResponse->json('data.id');

        $this->actingAs($this->admin, 'api')
            ->deleteJson("/api/v1/products/{$productId}")
            ->assertStatus(204);
    }

    public function test_product_name_is_required(): void
    {
        $this->actingAs($this->admin, 'api')
            ->postJson('/api/v1/products', [
                'type' => 'goods',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_product_type_must_be_valid_enum(): void
    {
        $this->actingAs($this->admin, 'api')
            ->postJson('/api/v1/products', [
                'type' => 'invalid_type',
                'name' => 'Test',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['type']);
    }
}
