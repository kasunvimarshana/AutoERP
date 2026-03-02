<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Inventory;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryTest extends TestCase
{
    use RefreshDatabase;

    private int $tenantId;

    private int $productId;

    private int $warehouseId;

    protected function setUp(): void
    {
        parent::setUp();

        $tenant = $this->postJson('/api/v1/tenants', [
            'name' => 'Inventory Test Tenant',
            'slug' => 'inventory-test-tenant',
        ]);
        $this->tenantId = $tenant->json('data.id');

        $product = $this->postJson('/api/v1/products', [
            'tenant_id' => $this->tenantId,
            'sku' => 'INV-TEST-001',
            'name' => 'Test Product',
            'type' => 'stockable',
            'uom' => 'pcs',
            'costing_method' => 'fifo',
            'cost_price' => '10.00',
            'sale_price' => '20.00',
        ]);
        $this->productId = $product->json('data.id');

        $warehouse = $this->postJson('/api/v1/warehouses', [
            'tenant_id' => $this->tenantId,
            'code' => 'WH-MAIN',
            'name' => 'Main Warehouse',
        ]);
        $this->warehouseId = $warehouse->json('data.id');
    }

    // ─── Warehouse Tests ────────────────────────────────────────────────────────

    public function test_can_create_warehouse(): void
    {
        $response = $this->postJson('/api/v1/warehouses', [
            'tenant_id' => $this->tenantId,
            'code' => 'WH-SECONDARY',
            'name' => 'Secondary Warehouse',
            'address' => '123 Logistics Rd',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success', 'message',
                'data' => ['id', 'tenant_id', 'code', 'name', 'address', 'status', 'created_at'],
                'errors',
            ])
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.code', 'WH-SECONDARY')
            ->assertJsonPath('data.tenant_id', $this->tenantId)
            ->assertJsonPath('data.status', 'active');
    }

    public function test_warehouse_code_must_be_unique_per_tenant(): void
    {
        $this->postJson('/api/v1/warehouses', [
            'tenant_id' => $this->tenantId,
            'code' => 'WH-DUP',
            'name' => 'First',
        ]);

        $response = $this->postJson('/api/v1/warehouses', [
            'tenant_id' => $this->tenantId,
            'code' => 'WH-DUP',
            'name' => 'Second',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_can_list_warehouses(): void
    {
        $this->postJson('/api/v1/warehouses', [
            'tenant_id' => $this->tenantId,
            'code' => 'WH-A',
            'name' => 'Warehouse A',
        ]);

        $response = $this->getJson("/api/v1/warehouses?tenant_id={$this->tenantId}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success', 'message', 'data', 'errors',
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ]);

        // WH-MAIN (from setUp) + WH-A = 2 warehouses
        $this->assertGreaterThanOrEqual(2, count($response->json('data')));
    }

    public function test_can_get_warehouse_by_id(): void
    {
        $response = $this->getJson("/api/v1/warehouses/{$this->warehouseId}?tenant_id={$this->tenantId}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $this->warehouseId)
            ->assertJsonPath('data.code', 'WH-MAIN');
    }

    public function test_returns_404_for_nonexistent_warehouse(): void
    {
        $response = $this->getJson("/api/v1/warehouses/99999?tenant_id={$this->tenantId}");

        $response->assertStatus(404)
            ->assertJsonPath('success', false);
    }

    // ─── Receive Stock Tests ─────────────────────────────────────────────────

    public function test_can_receive_stock(): void
    {
        $response = $this->postJson('/api/v1/inventory/receive', [
            'tenant_id' => $this->tenantId,
            'warehouse_id' => $this->warehouseId,
            'product_id' => $this->productId,
            'quantity' => '100',
            'unit_cost' => '10.00',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success', 'message',
                'data' => [
                    'id', 'tenant_id', 'warehouse_id', 'product_id',
                    'transaction_type', 'quantity', 'unit_cost', 'total_cost', 'created_at',
                ],
                'errors',
            ])
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.transaction_type', 'receipt')
            ->assertJsonPath('data.quantity', '100.0000');
    }

    public function test_receive_stock_updates_balance(): void
    {
        $this->postJson('/api/v1/inventory/receive', [
            'tenant_id' => $this->tenantId,
            'warehouse_id' => $this->warehouseId,
            'product_id' => $this->productId,
            'quantity' => '50',
            'unit_cost' => '10.00',
        ]);

        $this->postJson('/api/v1/inventory/receive', [
            'tenant_id' => $this->tenantId,
            'warehouse_id' => $this->warehouseId,
            'product_id' => $this->productId,
            'quantity' => '30',
            'unit_cost' => '12.00',
        ]);

        $response = $this->getJson(
            "/api/v1/inventory/stock?tenant_id={$this->tenantId}&warehouse_id={$this->warehouseId}"
        );

        $response->assertStatus(200);
        $balances = $response->json('data');
        $this->assertCount(1, $balances);
        $this->assertEquals('80.0000', $balances[0]['quantity_on_hand']);
    }

    // ─── Adjust Stock Tests ──────────────────────────────────────────────────

    public function test_can_adjust_stock_in(): void
    {
        $this->postJson('/api/v1/inventory/receive', [
            'tenant_id' => $this->tenantId,
            'warehouse_id' => $this->warehouseId,
            'product_id' => $this->productId,
            'quantity' => '100',
            'unit_cost' => '10.00',
        ]);

        $response = $this->postJson('/api/v1/inventory/adjust', [
            'tenant_id' => $this->tenantId,
            'warehouse_id' => $this->warehouseId,
            'product_id' => $this->productId,
            'quantity' => '10',
            'unit_cost' => '10.00',
            'adjustment_type' => 'adjustment_in',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.transaction_type', 'adjustment_in');
    }

    public function test_cannot_adjust_stock_out_beyond_available(): void
    {
        $this->postJson('/api/v1/inventory/receive', [
            'tenant_id' => $this->tenantId,
            'warehouse_id' => $this->warehouseId,
            'product_id' => $this->productId,
            'quantity' => '10',
            'unit_cost' => '10.00',
        ]);

        $response = $this->postJson('/api/v1/inventory/adjust', [
            'tenant_id' => $this->tenantId,
            'warehouse_id' => $this->warehouseId,
            'product_id' => $this->productId,
            'quantity' => '50',
            'unit_cost' => '10.00',
            'adjustment_type' => 'adjustment_out',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    // ─── Transfer Stock Tests ────────────────────────────────────────────────

    public function test_can_transfer_stock_between_warehouses(): void
    {
        $secondWarehouse = $this->postJson('/api/v1/warehouses', [
            'tenant_id' => $this->tenantId,
            'code' => 'WH-SECOND',
            'name' => 'Second Warehouse',
        ]);
        $secondWarehouseId = $secondWarehouse->json('data.id');

        $this->postJson('/api/v1/inventory/receive', [
            'tenant_id' => $this->tenantId,
            'warehouse_id' => $this->warehouseId,
            'product_id' => $this->productId,
            'quantity' => '100',
            'unit_cost' => '10.00',
        ]);

        $response = $this->postJson('/api/v1/inventory/transfer', [
            'tenant_id' => $this->tenantId,
            'source_warehouse_id' => $this->warehouseId,
            'destination_warehouse_id' => $secondWarehouseId,
            'product_id' => $this->productId,
            'quantity' => '40',
            'unit_cost' => '10.00',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['transfer_out', 'transfer_in']]);

        // Verify source balance reduced
        $sourceBalance = $this->getJson(
            "/api/v1/inventory/stock?tenant_id={$this->tenantId}&warehouse_id={$this->warehouseId}"
        );
        $this->assertEquals('60.0000', $sourceBalance->json('data.0.quantity_on_hand'));

        // Verify destination balance increased
        $destBalance = $this->getJson(
            "/api/v1/inventory/stock?tenant_id={$this->tenantId}&warehouse_id={$secondWarehouseId}"
        );
        $this->assertEquals('40.0000', $destBalance->json('data.0.quantity_on_hand'));
    }

    public function test_cannot_transfer_more_than_available(): void
    {
        $secondWarehouse = $this->postJson('/api/v1/warehouses', [
            'tenant_id' => $this->tenantId,
            'code' => 'WH-THIRD',
            'name' => 'Third Warehouse',
        ]);

        $this->postJson('/api/v1/inventory/receive', [
            'tenant_id' => $this->tenantId,
            'warehouse_id' => $this->warehouseId,
            'product_id' => $this->productId,
            'quantity' => '10',
            'unit_cost' => '10.00',
        ]);

        $response = $this->postJson('/api/v1/inventory/transfer', [
            'tenant_id' => $this->tenantId,
            'source_warehouse_id' => $this->warehouseId,
            'destination_warehouse_id' => $secondWarehouse->json('data.id'),
            'product_id' => $this->productId,
            'quantity' => '100',
            'unit_cost' => '10.00',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    // ─── Ledger Tests ────────────────────────────────────────────────────────

    public function test_can_retrieve_stock_ledger(): void
    {
        $this->postJson('/api/v1/inventory/receive', [
            'tenant_id' => $this->tenantId,
            'warehouse_id' => $this->warehouseId,
            'product_id' => $this->productId,
            'quantity' => '100',
            'unit_cost' => '10.00',
            'notes' => 'Initial receipt',
        ]);

        $response = $this->getJson(
            "/api/v1/inventory/ledger?tenant_id={$this->tenantId}&warehouse_id={$this->warehouseId}&product_id={$this->productId}"
        );

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [['id', 'transaction_type', 'quantity', 'unit_cost', 'total_cost', 'created_at']],
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ]);

        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('receipt', $response->json('data.0.transaction_type'));
    }

    public function test_adjustment_type_validation(): void
    {
        $response = $this->postJson('/api/v1/inventory/adjust', [
            'tenant_id' => $this->tenantId,
            'warehouse_id' => $this->warehouseId,
            'product_id' => $this->productId,
            'quantity' => '10',
            'unit_cost' => '10.00',
            'adjustment_type' => 'invalid_type',
        ]);

        $response->assertStatus(422);
    }

    // ─── Barcode Scan Tests ──────────────────────────────────────────────────

    public function test_scan_returns_product_and_stock_by_barcode(): void
    {
        // Create a product with a barcode
        $product = $this->postJson('/api/v1/products', [
            'tenant_id' => $this->tenantId,
            'sku' => 'SCAN-001',
            'name' => 'Scannable Product',
            'type' => 'stockable',
            'uom' => 'pcs',
            'costing_method' => 'fifo',
            'cost_price' => '5.00',
            'sale_price' => '10.00',
            'barcode' => '1234567890128',
        ]);
        $productId = $product->json('data.id');

        $this->postJson('/api/v1/inventory/receive', [
            'tenant_id' => $this->tenantId,
            'warehouse_id' => $this->warehouseId,
            'product_id' => $productId,
            'quantity' => '75',
            'unit_cost' => '5.00',
        ]);

        $response = $this->getJson(
            "/api/v1/inventory/scan?tenant_id={$this->tenantId}&barcode=1234567890128"
        );

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success', 'message',
                'data' => ['product_id', 'sku', 'name', 'barcode', 'uom', 'balances'],
                'errors',
            ])
            ->assertJsonPath('data.product_id', $productId)
            ->assertJsonPath('data.sku', 'SCAN-001')
            ->assertJsonPath('data.barcode', '1234567890128');

        $balances = $response->json('data.balances');
        $this->assertCount(1, $balances);
        $this->assertEquals('75.0000', $balances[0]['quantity_on_hand']);
    }

    public function test_scan_returns_404_for_unknown_barcode(): void
    {
        $response = $this->getJson(
            "/api/v1/inventory/scan?tenant_id={$this->tenantId}&barcode=NONEXISTENT"
        );

        $response->assertStatus(404)
            ->assertJsonPath('success', false);
    }

    public function test_scan_returns_422_when_barcode_missing(): void
    {
        $response = $this->getJson(
            "/api/v1/inventory/scan?tenant_id={$this->tenantId}"
        );

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_scan_returns_empty_balances_when_no_stock_received(): void
    {
        $product = $this->postJson('/api/v1/products', [
            'tenant_id' => $this->tenantId,
            'sku' => 'SCAN-002',
            'name' => 'Unstocked Product',
            'type' => 'stockable',
            'uom' => 'pcs',
            'costing_method' => 'fifo',
            'cost_price' => '1.00',
            'sale_price' => '2.00',
            'barcode' => '9780201633610',
        ]);

        $response = $this->getJson(
            "/api/v1/inventory/scan?tenant_id={$this->tenantId}&barcode=9780201633610"
        );

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.product_id', $product->json('data.id'));

        $this->assertCount(0, $response->json('data.balances'));
    }
}
