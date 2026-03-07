<?php

namespace Tests\Feature;

use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockAdjustmentTest extends TestCase
{
    use RefreshDatabase;

    private int $tenantId = 1;

    private Warehouse $warehouse;

    private InventoryItem $item;

    protected function setUp(): void
    {
        parent::setUp();

        $this->warehouse = Warehouse::create([
            'tenant_id' => $this->tenantId,
            'name'      => 'Test Warehouse',
            'code'      => 'TEST-001',
            'is_active' => true,
        ]);

        $this->item = InventoryItem::create([
            'tenant_id'         => $this->tenantId,
            'product_id'        => 1,
            'warehouse_id'      => $this->warehouse->id,
            'sku'               => 'TEST-SKU',
            'quantity'          => 100,
            'reserved_quantity' => 0,
            'reorder_point'     => 10,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Adjust Stock
    |--------------------------------------------------------------------------
    */

    public function test_adjust_stock_add_increases_quantity(): void
    {
        $response = $this->withTenantHeaders()->postJson(
            "/api/v1/inventory/{$this->item->id}/adjust-stock",
            [
                'type'     => 'add',
                'quantity' => 50,
                'reason'   => 'Received purchase order',
            ]
        );

        $response->assertOk()
                 ->assertJsonPath('data.quantity', 150);

        $this->assertDatabaseHas('inventory_items', [
            'id'       => $this->item->id,
            'quantity' => 150,
        ]);

        // Audit trail created
        $this->assertDatabaseHas('inventory_transactions', [
            'inventory_item_id' => $this->item->id,
            'type'              => 'add',
            'quantity_before'   => 100,
            'quantity_change'   => 50,
            'quantity_after'    => 150,
        ]);
    }

    public function test_adjust_stock_subtract_decreases_quantity(): void
    {
        $response = $this->withTenantHeaders()->postJson(
            "/api/v1/inventory/{$this->item->id}/adjust-stock",
            [
                'type'     => 'subtract',
                'quantity' => 30,
                'reason'   => 'Damaged goods removed',
            ]
        );

        $response->assertOk()
                 ->assertJsonPath('data.quantity', 70);
    }

    public function test_adjust_stock_set_overrides_quantity(): void
    {
        $response = $this->withTenantHeaders()->postJson(
            "/api/v1/inventory/{$this->item->id}/adjust-stock",
            [
                'type'     => 'set',
                'quantity' => 200,
                'reason'   => 'Physical stock count reconciliation',
            ]
        );

        $response->assertOk()
                 ->assertJsonPath('data.quantity', 200);
    }

    public function test_adjust_stock_subtract_below_zero_returns_conflict(): void
    {
        $response = $this->withTenantHeaders()->postJson(
            "/api/v1/inventory/{$this->item->id}/adjust-stock",
            [
                'type'     => 'subtract',
                'quantity' => 999,
                'reason'   => 'This should fail',
            ]
        );

        $response->assertConflict()
                 ->assertJsonStructure(['error', 'available', 'requested']);
    }

    public function test_adjust_stock_validates_type(): void
    {
        $response = $this->withTenantHeaders()->postJson(
            "/api/v1/inventory/{$this->item->id}/adjust-stock",
            [
                'type'     => 'invalid_type',
                'quantity' => 10,
                'reason'   => 'Test',
            ]
        );

        $response->assertUnprocessable();
    }

    /*
    |--------------------------------------------------------------------------
    | Reserve Stock
    |--------------------------------------------------------------------------
    */

    public function test_reserve_stock_increments_reserved_quantity(): void
    {
        $response = $this->withTenantHeaders()->postJson(
            "/api/v1/inventory/{$this->item->id}/reserve-stock",
            [
                'quantity'       => 20,
                'reason'         => 'Order #ORD-123',
                'reference_type' => 'order',
                'reference_id'   => 'ORD-123',
            ]
        );

        $response->assertOk()
                 ->assertJsonPath('data.reserved_quantity', 20)
                 ->assertJsonPath('data.available_quantity', 80);
    }

    public function test_reserve_stock_fails_when_insufficient(): void
    {
        $response = $this->withTenantHeaders()->postJson(
            "/api/v1/inventory/{$this->item->id}/reserve-stock",
            [
                'quantity' => 500,
                'reason'   => 'Too much',
            ]
        );

        $response->assertConflict()
                 ->assertJsonStructure(['error', 'available', 'requested']);
    }

    /*
    |--------------------------------------------------------------------------
    | Release Stock
    |--------------------------------------------------------------------------
    */

    public function test_release_stock_decrements_reserved_quantity(): void
    {
        // First reserve some stock
        $this->item->update(['reserved_quantity' => 30]);

        $response = $this->withTenantHeaders()->postJson(
            "/api/v1/inventory/{$this->item->id}/release-stock",
            [
                'quantity' => 20,
                'reason'   => 'Order cancelled',
            ]
        );

        $response->assertOk()
                 ->assertJsonPath('data.reserved_quantity', 10);
    }

    public function test_release_stock_caps_at_reserved_quantity(): void
    {
        // Reserved is only 5 but we try to release 100
        $this->item->update(['reserved_quantity' => 5]);

        $response = $this->withTenantHeaders()->postJson(
            "/api/v1/inventory/{$this->item->id}/release-stock",
            [
                'quantity' => 100,
                'reason'   => 'Bulk release',
            ]
        );

        // Should succeed but only release the 5 that were reserved
        $response->assertOk()
                 ->assertJsonPath('data.reserved_quantity', 0);
    }

    /*
    |--------------------------------------------------------------------------
    | Low Stock Detection
    |--------------------------------------------------------------------------
    */

    public function test_adjust_stock_triggers_low_stock_when_below_reorder_point(): void
    {
        // item has reorder_point = 10 and quantity = 100
        // subtract down to 8 (below reorder_point of 10)

        \Illuminate\Support\Facades\Event::fake([
            \App\Events\LowStockDetected::class,
        ]);

        $this->withTenantHeaders()->postJson(
            "/api/v1/inventory/{$this->item->id}/adjust-stock",
            [
                'type'     => 'subtract',
                'quantity' => 93,
                'reason'   => 'Sales depletion',
            ]
        );

        \Illuminate\Support\Facades\Event::assertDispatched(\App\Events\LowStockDetected::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    private function withTenantHeaders(): static
    {
        return $this->withHeaders([
            'X-Tenant-ID' => (string) $this->tenantId,
            'Accept'      => 'application/json',
        ]);
    }
}
