<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\TenantStatus;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class OrderCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create(['status' => TenantStatus::Active]);
        $this->admin = User::factory()->create(['tenant_id' => $this->tenant->id]);

        $createPerm = Permission::firstOrCreate(['name' => 'orders.create', 'guard_name' => 'api']);
        $confirmPerm = Permission::firstOrCreate(['name' => 'orders.confirm', 'guard_name' => 'api']);
        $cancelPerm = Permission::firstOrCreate(['name' => 'orders.cancel', 'guard_name' => 'api']);

        $this->admin->givePermissionTo([$createPerm, $confirmPerm, $cancelPerm]);
    }

    public function test_admin_can_create_order(): void
    {
        $response = $this->actingAs($this->admin, 'api')
            ->postJson('/api/v1/orders', [
                'type' => 'sale',
                'currency' => 'USD',
                'lines' => [
                    [
                        'product_name' => 'Widget A',
                        'quantity' => '2',
                        'unit_price' => '25.00',
                        'tax_rate' => '10',
                    ],
                ],
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.status', OrderStatus::Draft->value);
    }

    public function test_user_without_permission_cannot_create_order(): void
    {
        $noPermUser = User::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->actingAs($noPermUser, 'api')
            ->postJson('/api/v1/orders', ['type' => 'sale'])
            ->assertStatus(403);
    }

    public function test_admin_can_confirm_order(): void
    {
        $createResponse = $this->actingAs($this->admin, 'api')
            ->postJson('/api/v1/orders', [
                'type' => 'sale',
                'currency' => 'USD',
                'lines' => [
                    [
                        'product_name' => 'Widget A',
                        'quantity' => '1',
                        'unit_price' => '10.00',
                    ],
                ],
            ]);

        $orderId = $createResponse->json('data.id');

        $this->actingAs($this->admin, 'api')
            ->patchJson("/api/v1/orders/{$orderId}/confirm")
            ->assertStatus(200)
            ->assertJsonPath('data.status', OrderStatus::Confirmed->value);
    }

    public function test_admin_can_cancel_order(): void
    {
        $createResponse = $this->actingAs($this->admin, 'api')
            ->postJson('/api/v1/orders', [
                'type' => 'sale',
                'currency' => 'USD',
            ]);

        $orderId = $createResponse->json('data.id');

        $this->actingAs($this->admin, 'api')
            ->patchJson("/api/v1/orders/{$orderId}/cancel")
            ->assertStatus(200)
            ->assertJsonPath('data.status', OrderStatus::Cancelled->value);
    }

    public function test_order_totals_are_correctly_calculated(): void
    {
        $response = $this->actingAs($this->admin, 'api')
            ->postJson('/api/v1/orders', [
                'type' => 'sale',
                'currency' => 'USD',
                'lines' => [
                    [
                        'product_name' => 'Item A',
                        'quantity' => '2',
                        'unit_price' => '50.00',
                        'tax_rate' => '10',
                    ],
                    [
                        'product_name' => 'Item B',
                        'quantity' => '1',
                        'unit_price' => '20.00',
                        'tax_rate' => '0',
                    ],
                ],
            ]);

        $response->assertStatus(201);

        // subtotal = (2 * 50) + (1 * 20) = 120
        $this->assertEquals(120, (float) $response->json('data.subtotal'));
        // tax = (100 * 10/100) + 0 = 10
        $this->assertEquals(10, (float) $response->json('data.tax_amount'));
        // total = 120 + 10 = 130
        $this->assertEquals(130, (float) $response->json('data.total'));
    }

    public function test_cannot_confirm_already_confirmed_order(): void
    {
        $createResponse = $this->actingAs($this->admin, 'api')
            ->postJson('/api/v1/orders', [
                'type' => 'sale',
                'lines' => [
                    [
                        'product_name' => 'Item',
                        'quantity' => '1',
                        'unit_price' => '10.00',
                    ],
                ],
            ]);

        $orderId = $createResponse->json('data.id');

        // Confirm once - should succeed
        $this->actingAs($this->admin, 'api')
            ->patchJson("/api/v1/orders/{$orderId}/confirm")
            ->assertStatus(200);

        // Confirm again - should fail with error
        $this->actingAs($this->admin, 'api')
            ->patchJson("/api/v1/orders/{$orderId}/confirm")
            ->assertStatus(500);
    }
}
