<?php

namespace Tests\Feature;

use App\Enums\TenantStatus;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class StockTransferTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Tenant $tenant;

    private Warehouse $warehouseA;

    private Warehouse $warehouseB;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create(['status' => TenantStatus::Active]);
        $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->warehouseA = Warehouse::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Warehouse A',
            'code' => 'WH-A',
        ]);

        $this->warehouseB = Warehouse::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Warehouse B',
            'code' => 'WH-B',
        ]);

        $this->product = Product::create([
            'tenant_id' => $this->tenant->id,
            'type' => 'goods',
            'name' => 'Transfer Product',
            'slug' => 'transfer-product',
            'sku' => 'TRF-001',
            'base_price' => '10.00000000',
            'currency' => 'USD',
        ]);
    }

    // ── Authentication ──────────────────────────────────────────────────────

    public function test_unauthenticated_cannot_list_transfers(): void
    {
        $this->getJson('/api/v1/stock-transfers')->assertStatus(401);
    }

    public function test_unauthenticated_cannot_create_transfer(): void
    {
        $this->postJson('/api/v1/stock-transfers', [])->assertStatus(401);
    }

    // ── Authorization ───────────────────────────────────────────────────────

    public function test_user_without_permission_cannot_create_transfer(): void
    {
        $this->actingAs($this->user, 'api')
            ->postJson('/api/v1/stock-transfers', [])
            ->assertStatus(403);
    }

    // ── Create draft transfer ───────────────────────────────────────────────

    public function test_user_can_create_draft_transfer(): void
    {
        $this->grantPermissions(['stock_transfers.create']);

        $response = $this->actingAs($this->user, 'api')
            ->postJson('/api/v1/stock-transfers', [
                'from_warehouse_id' => $this->warehouseA->id,
                'to_warehouse_id' => $this->warehouseB->id,
                'notes' => 'Monthly stock balancing',
                'lines' => [
                    [
                        'product_id' => $this->product->id,
                        'quantity' => '5',
                        'cost_per_unit' => '10.00',
                    ],
                ],
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'draft');

        $this->assertStringStartsWith('TRF-', $response->json('data.reference_number'));
    }

    public function test_cannot_transfer_to_same_warehouse(): void
    {
        $this->grantPermissions(['stock_transfers.create']);

        $this->actingAs($this->user, 'api')
            ->postJson('/api/v1/stock-transfers', [
                'from_warehouse_id' => $this->warehouseA->id,
                'to_warehouse_id' => $this->warehouseA->id,
                'lines' => [
                    ['product_id' => $this->product->id, 'quantity' => '5'],
                ],
            ])
            ->assertStatus(422);
    }

    // ── Lifecycle ───────────────────────────────────────────────────────────

    public function test_user_can_dispatch_and_receive_transfer(): void
    {
        $this->grantPermissions([
            'stock_transfers.create',
            'stock_transfers.dispatch',
            'stock_transfers.receive',
            'inventory.adjust',
        ]);

        // First add stock to source warehouse
        $this->actingAs($this->user, 'api')
            ->postJson('/api/v1/stock-adjustments', [
                'warehouse_id' => $this->warehouseA->id,
                'reason' => 'correction',
                'lines' => [
                    ['product_id' => $this->product->id, 'quantity' => '10', 'unit_cost' => '8.00'],
                ],
            ]);

        // Create transfer
        $createResp = $this->actingAs($this->user, 'api')
            ->postJson('/api/v1/stock-transfers', [
                'from_warehouse_id' => $this->warehouseA->id,
                'to_warehouse_id' => $this->warehouseB->id,
                'lines' => [
                    ['product_id' => $this->product->id, 'quantity' => '5', 'cost_per_unit' => '8.00'],
                ],
            ]);

        $transferId = $createResp->json('data.id');

        // Dispatch (deducts from source)
        $this->actingAs($this->user, 'api')
            ->patchJson("/api/v1/stock-transfers/{$transferId}/dispatch")
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'in_transit');

        // Receive (adds to destination)
        $this->actingAs($this->user, 'api')
            ->patchJson("/api/v1/stock-transfers/{$transferId}/receive")
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'received');
    }

    public function test_user_can_cancel_draft_transfer(): void
    {
        $this->grantPermissions(['stock_transfers.create', 'stock_transfers.cancel']);

        $transferId = $this->actingAs($this->user, 'api')
            ->postJson('/api/v1/stock-transfers', [
                'from_warehouse_id' => $this->warehouseA->id,
                'to_warehouse_id' => $this->warehouseB->id,
                'lines' => [
                    ['product_id' => $this->product->id, 'quantity' => '3'],
                ],
            ])->json('data.id');

        $this->actingAs($this->user, 'api')
            ->patchJson("/api/v1/stock-transfers/{$transferId}/cancel")
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'cancelled');
    }

    /** @param array<string> $permissions */
    private function grantPermissions(array $permissions): void
    {
        foreach ($permissions as $perm) {
            $permission = Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'api']);
            $this->user->givePermissionTo($permission);
        }
        // Flush cache so fresh permissions are used in the test
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
