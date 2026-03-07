<?php

namespace App\Modules\Order\Tests;

use App\Modules\Inventory\Models\Inventory;
use App\Modules\Order\Models\Order;
use App\Modules\Product\Models\Product;
use App\Modules\Tenant\Models\Tenant;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $adminUser;
    private Product $product;
    private Inventory $inventory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant    = Tenant::factory()->create(['is_active' => true]);
        $this->adminUser = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'role'      => 'admin',
        ]);
        $this->adminUser->assignRole('admin');

        $this->product = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'price'     => 25.00,
        ]);

        $this->inventory = Inventory::factory()->create([
            'tenant_id'  => $this->tenant->id,
            'product_id' => $this->product->id,
            'quantity'   => 100,
        ]);
    }

    public function test_can_create_order_via_saga(): void
    {
        Passport::actingAs($this->adminUser);

        $response = $this->postJson('/api/v1/orders', [
            'customer_name'  => 'John Doe',
            'customer_email' => 'john@example.com',
            'items'          => [
                ['product_id' => $this->product->id, 'quantity' => 5],
            ],
        ], ['X-Tenant-ID' => $this->tenant->id]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.status', 'confirmed');

        $this->assertDatabaseHas('inventories', [
            'id'       => $this->inventory->id,
            'quantity' => 95,
        ]);
    }

    public function test_cannot_create_order_with_insufficient_inventory(): void
    {
        Passport::actingAs($this->adminUser);

        $response = $this->postJson('/api/v1/orders', [
            'customer_name'  => 'John Doe',
            'customer_email' => 'john@example.com',
            'items'          => [
                ['product_id' => $this->product->id, 'quantity' => 200],
            ],
        ], ['X-Tenant-ID' => $this->tenant->id]);

        $response->assertStatus(422);
    }

    public function test_can_cancel_order(): void
    {
        Passport::actingAs($this->adminUser);

        $order = Order::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status'    => 'confirmed',
        ]);

        $response = $this->postJson("/api/v1/orders/{$order->id}/cancel", [], ['X-Tenant-ID' => $this->tenant->id]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.status', 'cancelled');
    }

    public function test_can_list_orders_with_status_filter(): void
    {
        Passport::actingAs($this->adminUser);

        Order::factory()->count(3)->create(['tenant_id' => $this->tenant->id, 'status' => 'confirmed']);
        Order::factory()->count(2)->create(['tenant_id' => $this->tenant->id, 'status' => 'pending']);

        $response = $this->getJson('/api/v1/orders?filter[status]=confirmed', ['X-Tenant-ID' => $this->tenant->id]);

        $response->assertStatus(200);
        $response->assertJsonCount(3, 'data');
    }
}
