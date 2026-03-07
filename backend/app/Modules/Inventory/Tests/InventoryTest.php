<?php

namespace App\Modules\Inventory\Tests;

use App\Modules\Inventory\Models\Inventory;
use App\Modules\Product\Models\Product;
use App\Modules\Tenant\Models\Tenant;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class InventoryTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $adminUser;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant    = Tenant::factory()->create(['is_active' => true]);
        $this->adminUser = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'role'      => 'admin',
        ]);
        $this->adminUser->assignRole('admin');
        $this->product = Product::factory()->create(['tenant_id' => $this->tenant->id]);
    }

    public function test_can_list_inventory(): void
    {
        Passport::actingAs($this->adminUser);
        Inventory::factory()->count(3)->create([
            'tenant_id'  => $this->tenant->id,
            'product_id' => $this->product->id,
        ]);

        $response = $this->getJson('/api/v1/inventory', ['X-Tenant-ID' => $this->tenant->id]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['data', 'meta']);
    }

    public function test_can_create_inventory(): void
    {
        Passport::actingAs($this->adminUser);

        $response = $this->postJson('/api/v1/inventory', [
            'product_id'         => $this->product->id,
            'warehouse_location' => 'A1-01',
            'quantity'           => 100,
            'reorder_point'      => 10,
            'reorder_quantity'   => 50,
        ], ['X-Tenant-ID' => $this->tenant->id]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.quantity', 100);
    }

    public function test_can_adjust_stock(): void
    {
        Passport::actingAs($this->adminUser);
        $inventory = Inventory::factory()->create([
            'tenant_id'  => $this->tenant->id,
            'product_id' => $this->product->id,
            'quantity'   => 50,
        ]);

        $response = $this->postJson("/api/v1/inventory/{$inventory->id}/adjust", [
            'delta'  => -10,
            'reason' => 'sale',
        ], ['X-Tenant-ID' => $this->tenant->id]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.quantity', 40);
    }

    public function test_cannot_adjust_below_zero(): void
    {
        Passport::actingAs($this->adminUser);
        $inventory = Inventory::factory()->create([
            'tenant_id'  => $this->tenant->id,
            'product_id' => $this->product->id,
            'quantity'   => 5,
        ]);

        $response = $this->postJson("/api/v1/inventory/{$inventory->id}/adjust", [
            'delta'  => -10,
            'reason' => 'sale',
        ], ['X-Tenant-ID' => $this->tenant->id]);

        $response->assertStatus(422);
    }
}
