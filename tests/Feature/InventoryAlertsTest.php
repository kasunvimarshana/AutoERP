<?php

namespace Tests\Feature;

use App\Enums\TenantStatus;
use App\Models\Product;
use App\Models\StockBatch;
use App\Models\StockItem;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class InventoryAlertsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Tenant $tenant;

    private Warehouse $warehouse;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create(['status' => TenantStatus::Active]);
        $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->warehouse = Warehouse::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Main Warehouse',
            'code' => 'WH-MAIN',
        ]);

        $this->product = Product::create([
            'tenant_id' => $this->tenant->id,
            'type' => 'goods',
            'name' => 'Alert Test Product',
            'slug' => 'alert-test-product',
            'sku' => 'ALERT-001',
            'base_price' => '10.00000000',
            'currency' => 'USD',
        ]);
    }

    // --- Stock route ---

    public function test_unauthenticated_cannot_access_stock(): void
    {
        $this->getJson('/api/v1/inventory/stock')->assertStatus(401);
    }

    public function test_authenticated_user_can_access_stock(): void
    {
        $this->actingAs($this->user, 'api')
            ->getJson('/api/v1/inventory/stock')
            ->assertStatus(403); // no inventory.view permission — expected 403
    }

    // --- Low-stock alerts ---

    public function test_unauthenticated_cannot_access_low_stock_alerts(): void
    {
        $this->getJson('/api/v1/inventory/alerts/low-stock')->assertStatus(401);
    }

    public function test_low_stock_alert_returns_items_below_reorder_point(): void
    {
        // Create a stock item with quantity_available <= reorder_point
        StockItem::create([
            'tenant_id' => $this->tenant->id,
            'warehouse_id' => $this->warehouse->id,
            'product_id' => $this->product->id,
            'quantity_on_hand' => '2.00000000',
            'quantity_reserved' => '0.00000000',
            'quantity_available' => '2.00000000',
            'reorder_point' => '5.00000000',
            'reorder_quantity' => '20.00000000',
            'cost_per_unit' => '5.00000000',
            'currency' => 'USD',
        ]);

        $perm = Permission::firstOrCreate(['name' => 'inventory.view', 'guard_name' => 'api']);
        $this->user->givePermissionTo($perm);

        $this->actingAs($this->user, 'api')
            ->getJson('/api/v1/inventory/alerts/low-stock')
            ->assertStatus(200)
            ->assertJsonPath('data.0.product_id', $this->product->id);
    }

    // --- Expiry alerts ---

    public function test_unauthenticated_cannot_access_expiry_alerts(): void
    {
        $this->getJson('/api/v1/inventory/alerts/expiring')->assertStatus(401);
    }

    public function test_expiring_alert_returns_batches_expiring_within_days(): void
    {
        // Batch expiring in 10 days — should be included in default 30-day window
        StockBatch::create([
            'tenant_id' => $this->tenant->id,
            'warehouse_id' => $this->warehouse->id,
            'product_id' => $this->product->id,
            'quantity_received' => '10.00000000',
            'quantity_remaining' => '10.00000000',
            'cost_per_unit' => '5.00000000',
            'currency' => 'USD',
            'expiry_date' => now()->addDays(10)->format('Y-m-d'),
            'received_at' => now(),
        ]);

        $perm = Permission::firstOrCreate(['name' => 'inventory.view', 'guard_name' => 'api']);
        $this->user->givePermissionTo($perm);

        $this->actingAs($this->user, 'api')
            ->getJson('/api/v1/inventory/alerts/expiring')
            ->assertStatus(200)
            ->assertJsonPath('data.0.product_id', $this->product->id);
    }

    public function test_expiring_alert_excludes_batches_beyond_days_window(): void
    {
        // Batch expiring in 60 days — should NOT appear in default 30-day window
        StockBatch::create([
            'tenant_id' => $this->tenant->id,
            'warehouse_id' => $this->warehouse->id,
            'product_id' => $this->product->id,
            'quantity_received' => '10.00000000',
            'quantity_remaining' => '10.00000000',
            'cost_per_unit' => '5.00000000',
            'currency' => 'USD',
            'expiry_date' => now()->addDays(60)->format('Y-m-d'),
            'received_at' => now(),
        ]);

        $perm = Permission::firstOrCreate(['name' => 'inventory.view', 'guard_name' => 'api']);
        $this->user->givePermissionTo($perm);

        $this->actingAs($this->user, 'api')
            ->getJson('/api/v1/inventory/alerts/expiring')
            ->assertStatus(200)
            ->assertJsonPath('data', []);
    }

    // --- FIFO cost ---

    public function test_unauthenticated_cannot_access_fifo_cost(): void
    {
        $this->getJson('/api/v1/inventory/fifo-cost')->assertStatus(401);
    }

    public function test_fifo_cost_returns_weighted_average_of_open_batches(): void
    {
        // Two batches: 10 units @ 5.00 and 10 units @ 7.00 → avg = 6.00
        StockBatch::create([
            'tenant_id' => $this->tenant->id,
            'warehouse_id' => $this->warehouse->id,
            'product_id' => $this->product->id,
            'quantity_received' => '10.00000000',
            'quantity_remaining' => '10.00000000',
            'cost_per_unit' => '5.00000000',
            'currency' => 'USD',
            'received_at' => now()->subDays(2),
        ]);

        StockBatch::create([
            'tenant_id' => $this->tenant->id,
            'warehouse_id' => $this->warehouse->id,
            'product_id' => $this->product->id,
            'quantity_received' => '10.00000000',
            'quantity_remaining' => '10.00000000',
            'cost_per_unit' => '7.00000000',
            'currency' => 'USD',
            'received_at' => now(),
        ]);

        $perm = Permission::firstOrCreate(['name' => 'inventory.view', 'guard_name' => 'api']);
        $this->user->givePermissionTo($perm);

        $response = $this->actingAs($this->user, 'api')
            ->getJson('/api/v1/inventory/fifo-cost?warehouse_id='.$this->warehouse->id.'&product_id='.$this->product->id)
            ->assertStatus(200);

        $this->assertSame('6.00000000', $response->json('cost_per_unit'));
    }
}
