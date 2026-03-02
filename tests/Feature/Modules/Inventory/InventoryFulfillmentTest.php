<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Inventory;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryFulfillmentTest extends TestCase
{
    use RefreshDatabase;

    private int $tenantId;

    private int $productId;

    private int $warehouseId;

    protected function setUp(): void
    {
        parent::setUp();

        $tenant = $this->postJson('/api/v1/tenants', [
            'name' => 'Fulfillment Test Tenant',
            'slug' => 'fulfillment-test-tenant',
        ]);
        $this->tenantId = $tenant->json('data.id');

        $product = $this->postJson('/api/v1/products', [
            'tenant_id' => $this->tenantId,
            'sku' => 'FULFIL-001',
            'name' => 'Fulfillment Product',
            'type' => 'stockable',
            'uom' => 'pcs',
            'costing_method' => 'fifo',
            'cost_price' => '10.00',
            'sale_price' => '20.00',
        ]);
        $this->productId = $product->json('data.id');

        $warehouse = $this->postJson('/api/v1/warehouses', [
            'tenant_id' => $this->tenantId,
            'code' => 'WH-FULFIL',
            'name' => 'Fulfillment Warehouse',
        ]);
        $this->warehouseId = $warehouse->json('data.id');

        // Pre-seed stock
        $this->postJson('/api/v1/inventory/receive', [
            'tenant_id' => $this->tenantId,
            'warehouse_id' => $this->warehouseId,
            'product_id' => $this->productId,
            'quantity' => '100',
            'unit_cost' => '10.00',
        ]);
    }

    // ─── Ship Stock Tests ────────────────────────────────────────────────────

    public function test_can_ship_stock(): void
    {
        $response = $this->postJson('/api/v1/inventory/ship', [
            'tenant_id' => $this->tenantId,
            'warehouse_id' => $this->warehouseId,
            'product_id' => $this->productId,
            'quantity' => '30',
            'unit_cost' => '10.00',
            'reference_type' => 'sales_order',
            'reference_id' => '42',
            'notes' => 'Shipped to customer',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success', 'message',
                'data' => ['id', 'transaction_type', 'quantity', 'unit_cost', 'total_cost', 'created_at'],
                'errors',
            ])
            ->assertJsonPath('data.transaction_type', 'shipment')
            ->assertJsonPath('data.quantity', '30.0000');
    }

    public function test_ship_stock_deducts_balance(): void
    {
        $this->postJson('/api/v1/inventory/ship', [
            'tenant_id' => $this->tenantId,
            'warehouse_id' => $this->warehouseId,
            'product_id' => $this->productId,
            'quantity' => '25',
            'unit_cost' => '10.00',
        ]);

        $response = $this->getJson(
            "/api/v1/inventory/stock?tenant_id={$this->tenantId}&warehouse_id={$this->warehouseId}"
        );

        $response->assertStatus(200);
        $balances = $response->json('data');
        $this->assertCount(1, $balances);
        $this->assertEquals('75.0000', $balances[0]['quantity_on_hand']);
    }

    public function test_cannot_ship_more_than_available(): void
    {
        $response = $this->postJson('/api/v1/inventory/ship', [
            'tenant_id' => $this->tenantId,
            'warehouse_id' => $this->warehouseId,
            'product_id' => $this->productId,
            'quantity' => '200',
            'unit_cost' => '10.00',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_ship_creates_ledger_entry_with_shipment_type(): void
    {
        $this->postJson('/api/v1/inventory/ship', [
            'tenant_id' => $this->tenantId,
            'warehouse_id' => $this->warehouseId,
            'product_id' => $this->productId,
            'quantity' => '10',
            'unit_cost' => '10.00',
        ]);

        $ledger = $this->getJson(
            "/api/v1/inventory/ledger?tenant_id={$this->tenantId}&warehouse_id={$this->warehouseId}&product_id={$this->productId}"
        );

        $entries = $ledger->json('data');
        $shipmentEntries = array_filter($entries, fn ($e) => $e['transaction_type'] === 'shipment');
        $this->assertCount(1, $shipmentEntries);
    }

    public function test_shipment_is_counted_in_turnover_calculation(): void
    {
        $this->postJson('/api/v1/inventory/ship', [
            'tenant_id' => $this->tenantId,
            'warehouse_id' => $this->warehouseId,
            'product_id' => $this->productId,
            'quantity' => '50',
            'unit_cost' => '10.00',
        ]);

        $response = $this->getJson(
            "/api/v1/inventory/turnover?tenant_id={$this->tenantId}&period_days=30"
        );

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $items = $response->json('data');
        $this->assertNotEmpty($items);
        $productItem = array_values(array_filter($items, fn ($i) => $i['product_id'] === $this->productId))[0] ?? null;
        $this->assertNotNull($productItem);
        $this->assertEquals('500.0000', $productItem['cogs']);
    }

    // ─── Reserve / Release Tests ─────────────────────────────────────────────

    public function test_can_reserve_stock(): void
    {
        $response = $this->postJson('/api/v1/inventory/reserve-stock', [
            'tenant_id' => $this->tenantId,
            'warehouse_id' => $this->warehouseId,
            'product_id' => $this->productId,
            'quantity' => '20',
            'reference_type' => 'sales_order',
            'reference_id' => '10',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success', 'message',
                'data' => ['quantity_on_hand', 'quantity_reserved', 'quantity_available'],
                'errors',
            ])
            ->assertJsonPath('data.quantity_reserved', '20.0000')
            ->assertJsonPath('data.quantity_available', '80.0000');
    }

    public function test_cannot_reserve_more_than_available(): void
    {
        $response = $this->postJson('/api/v1/inventory/reserve-stock', [
            'tenant_id' => $this->tenantId,
            'warehouse_id' => $this->warehouseId,
            'product_id' => $this->productId,
            'quantity' => '150',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_can_release_reservation(): void
    {
        $this->postJson('/api/v1/inventory/reserve-stock', [
            'tenant_id' => $this->tenantId,
            'warehouse_id' => $this->warehouseId,
            'product_id' => $this->productId,
            'quantity' => '30',
        ]);

        $response = $this->postJson('/api/v1/inventory/release-stock', [
            'tenant_id' => $this->tenantId,
            'warehouse_id' => $this->warehouseId,
            'product_id' => $this->productId,
            'quantity' => '30',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.quantity_reserved', '0.0000')
            ->assertJsonPath('data.quantity_available', '100.0000');
    }

    public function test_cannot_release_more_than_reserved(): void
    {
        $this->postJson('/api/v1/inventory/reserve-stock', [
            'tenant_id' => $this->tenantId,
            'warehouse_id' => $this->warehouseId,
            'product_id' => $this->productId,
            'quantity' => '10',
        ]);

        $response = $this->postJson('/api/v1/inventory/release-stock', [
            'tenant_id' => $this->tenantId,
            'warehouse_id' => $this->warehouseId,
            'product_id' => $this->productId,
            'quantity' => '50',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_reserved_stock_reduces_available_for_shipment(): void
    {
        // Reserve 80 units leaving only 20 available
        $this->postJson('/api/v1/inventory/reserve-stock', [
            'tenant_id' => $this->tenantId,
            'warehouse_id' => $this->warehouseId,
            'product_id' => $this->productId,
            'quantity' => '80',
        ]);

        // Trying to ship 50 should fail (only 20 available)
        $response = $this->postJson('/api/v1/inventory/ship', [
            'tenant_id' => $this->tenantId,
            'warehouse_id' => $this->warehouseId,
            'product_id' => $this->productId,
            'quantity' => '50',
            'unit_cost' => '10.00',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_reserve_fails_when_no_stock_balance_exists(): void
    {
        $product2 = $this->postJson('/api/v1/products', [
            'tenant_id' => $this->tenantId,
            'sku' => 'FULFIL-002',
            'name' => 'Unstocked Product',
            'type' => 'stockable',
            'uom' => 'pcs',
            'costing_method' => 'fifo',
            'cost_price' => '5.00',
            'sale_price' => '10.00',
        ]);

        $response = $this->postJson('/api/v1/inventory/reserve-stock', [
            'tenant_id' => $this->tenantId,
            'warehouse_id' => $this->warehouseId,
            'product_id' => $product2->json('data.id'),
            'quantity' => '5',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    // ─── Return Stock Tests ──────────────────────────────────────────────────

    public function test_can_return_stock(): void
    {
        $response = $this->postJson('/api/v1/inventory/return', [
            'tenant_id' => $this->tenantId,
            'warehouse_id' => $this->warehouseId,
            'product_id' => $this->productId,
            'quantity' => '5',
            'unit_cost' => '10.00',
            'reference_type' => 'sales_return',
            'reference_id' => '99',
            'notes' => 'Customer returned item',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success', 'message',
                'data' => ['id', 'transaction_type', 'quantity', 'unit_cost', 'total_cost', 'created_at'],
                'errors',
            ])
            ->assertJsonPath('data.transaction_type', 'return_in')
            ->assertJsonPath('data.quantity', '5.0000');
    }

    public function test_return_stock_increases_balance(): void
    {
        $this->postJson('/api/v1/inventory/return', [
            'tenant_id' => $this->tenantId,
            'warehouse_id' => $this->warehouseId,
            'product_id' => $this->productId,
            'quantity' => '15',
            'unit_cost' => '10.00',
        ]);

        $response = $this->getJson(
            "/api/v1/inventory/stock?tenant_id={$this->tenantId}&warehouse_id={$this->warehouseId}"
        );

        $balances = $response->json('data');
        $this->assertEquals('115.0000', $balances[0]['quantity_on_hand']);
    }

    public function test_return_to_empty_warehouse_creates_new_balance(): void
    {
        $newWarehouse = $this->postJson('/api/v1/warehouses', [
            'tenant_id' => $this->tenantId,
            'code' => 'WH-RETURN',
            'name' => 'Return Warehouse',
        ]);
        $newWarehouseId = $newWarehouse->json('data.id');

        $response = $this->postJson('/api/v1/inventory/return', [
            'tenant_id' => $this->tenantId,
            'warehouse_id' => $newWarehouseId,
            'product_id' => $this->productId,
            'quantity' => '8',
            'unit_cost' => '10.00',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.transaction_type', 'return_in');

        $balance = $this->getJson(
            "/api/v1/inventory/stock?tenant_id={$this->tenantId}&warehouse_id={$newWarehouseId}"
        );
        $this->assertEquals('8.0000', $balance->json('data.0.quantity_on_hand'));
    }
}
