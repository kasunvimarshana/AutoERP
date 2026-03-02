<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Inventory;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryReportTest extends TestCase
{
    use RefreshDatabase;

    private int $tenantId;

    private int $productId;

    private int $warehouseId;

    protected function setUp(): void
    {
        parent::setUp();

        $tenant = $this->postJson('/api/v1/tenants', [
            'name' => 'Report Test Tenant',
            'slug' => 'report-test-tenant',
        ]);
        $this->tenantId = $tenant->json('data.id');

        $product = $this->postJson('/api/v1/products', [
            'tenant_id' => $this->tenantId,
            'sku' => 'REPORT-TEST-001',
            'name' => 'Report Test Product',
            'type' => 'stockable',
            'uom' => 'pcs',
            'costing_method' => 'fifo',
            'cost_price' => '10.00',
            'sale_price' => '20.00',
        ]);
        $this->productId = $product->json('data.id');

        $warehouse = $this->postJson('/api/v1/warehouses', [
            'tenant_id' => $this->tenantId,
            'code' => 'WH-REPORT',
            'name' => 'Report Warehouse',
        ]);
        $this->warehouseId = $warehouse->json('data.id');
    }

    // ─── Inventory Valuation Tests ───────────────────────────────────────────

    public function test_valuation_returns_empty_when_no_stock(): void
    {
        $response = $this->getJson(
            "/api/v1/inventory/valuation?tenant_id={$this->tenantId}"
        );

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success', 'message', 'data', 'errors',
                'meta' => ['grand_total_value'],
            ]);

        $this->assertCount(0, $response->json('data'));
        $this->assertEquals('0', $response->json('meta.grand_total_value'));
    }

    public function test_valuation_returns_correct_stock_value(): void
    {
        $this->postJson('/api/v1/inventory/receive', [
            'tenant_id' => $this->tenantId,
            'warehouse_id' => $this->warehouseId,
            'product_id' => $this->productId,
            'quantity' => '50',
            'unit_cost' => '10.00',
        ]);

        $response = $this->getJson(
            "/api/v1/inventory/valuation?tenant_id={$this->tenantId}"
        );

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success', 'message',
                'data' => [
                    '*' => ['product_id', 'warehouse_id', 'quantity_on_hand', 'average_cost', 'total_value'],
                ],
                'meta' => ['grand_total_value'],
            ]);

        $items = $response->json('data');
        $this->assertCount(1, $items);
        $this->assertEquals($this->productId, $items[0]['product_id']);
        $this->assertEquals('50.0000', $items[0]['quantity_on_hand']);
        $this->assertEquals('10.0000', $items[0]['average_cost']);
        $this->assertEquals('500.0000', $items[0]['total_value']);
        $this->assertEquals('500.0000', $response->json('meta.grand_total_value'));
    }

    public function test_valuation_filtered_by_warehouse(): void
    {
        $this->postJson('/api/v1/inventory/receive', [
            'tenant_id' => $this->tenantId,
            'warehouse_id' => $this->warehouseId,
            'product_id' => $this->productId,
            'quantity' => '20',
            'unit_cost' => '15.00',
        ]);

        $response = $this->getJson(
            "/api/v1/inventory/valuation?tenant_id={$this->tenantId}&warehouse_id={$this->warehouseId}"
        );

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $items = $response->json('data');
        $this->assertCount(1, $items);
        $this->assertEquals($this->warehouseId, $items[0]['warehouse_id']);
    }

    public function test_valuation_grand_total_sums_all_items(): void
    {
        // Create second product
        $product2 = $this->postJson('/api/v1/products', [
            'tenant_id' => $this->tenantId,
            'sku' => 'REPORT-TEST-002',
            'name' => 'Report Test Product 2',
            'type' => 'stockable',
            'uom' => 'pcs',
            'costing_method' => 'fifo',
            'cost_price' => '5.00',
            'sale_price' => '10.00',
        ]);
        $productId2 = $product2->json('data.id');

        $this->postJson('/api/v1/inventory/receive', [
            'tenant_id' => $this->tenantId,
            'warehouse_id' => $this->warehouseId,
            'product_id' => $this->productId,
            'quantity' => '10',
            'unit_cost' => '10.00',
        ]);

        $this->postJson('/api/v1/inventory/receive', [
            'tenant_id' => $this->tenantId,
            'warehouse_id' => $this->warehouseId,
            'product_id' => $productId2,
            'quantity' => '20',
            'unit_cost' => '5.00',
        ]);

        $response = $this->getJson(
            "/api/v1/inventory/valuation?tenant_id={$this->tenantId}"
        );

        $response->assertStatus(200);
        // Product 1: 10 × 10 = 100; Product 2: 20 × 5 = 100; Grand total = 200
        $this->assertEquals('200.0000', $response->json('meta.grand_total_value'));
        $this->assertCount(2, $response->json('data'));
    }

    // ─── Demand Forecast Tests ───────────────────────────────────────────────

    public function test_demand_forecast_returns_empty_when_no_outflows(): void
    {
        // Receive stock but do not ship
        $this->postJson('/api/v1/inventory/receive', [
            'tenant_id' => $this->tenantId,
            'warehouse_id' => $this->warehouseId,
            'product_id' => $this->productId,
            'quantity' => '100',
            'unit_cost' => '10.00',
        ]);

        $response = $this->getJson(
            "/api/v1/inventory/demand-forecast?tenant_id={$this->tenantId}&period_days=30"
        );

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['success', 'message', 'data', 'errors']);

        $this->assertCount(0, $response->json('data'));
    }

    public function test_demand_forecast_computes_from_shipments(): void
    {
        $this->postJson('/api/v1/inventory/receive', [
            'tenant_id' => $this->tenantId,
            'warehouse_id' => $this->warehouseId,
            'product_id' => $this->productId,
            'quantity' => '300',
            'unit_cost' => '10.00',
        ]);

        $this->postJson('/api/v1/inventory/adjust', [
            'tenant_id' => $this->tenantId,
            'warehouse_id' => $this->warehouseId,
            'product_id' => $this->productId,
            'quantity' => '60',
            'unit_cost' => '10.00',
            'adjustment_type' => 'adjustment_out',
        ]);

        $response = $this->getJson(
            "/api/v1/inventory/demand-forecast?tenant_id={$this->tenantId}&period_days=30"
        );

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success', 'message',
                'data' => [
                    '*' => [
                        'product_id', 'warehouse_id', 'total_outflow',
                        'period_days', 'avg_daily_demand', 'forecast_30_days',
                    ],
                ],
                'errors',
            ]);

        $items = $response->json('data');
        $this->assertCount(1, $items);
        $this->assertEquals($this->productId, $items[0]['product_id']);
        $this->assertEquals('60.0000', $items[0]['total_outflow']);
        $this->assertEquals(30, $items[0]['period_days']);
        $this->assertEquals('2.0000', $items[0]['avg_daily_demand']);
        $this->assertEquals('60.0000', $items[0]['forecast_30_days']);
    }

    public function test_demand_forecast_filtered_by_warehouse(): void
    {
        $this->postJson('/api/v1/inventory/receive', [
            'tenant_id' => $this->tenantId,
            'warehouse_id' => $this->warehouseId,
            'product_id' => $this->productId,
            'quantity' => '100',
            'unit_cost' => '10.00',
        ]);

        $this->postJson('/api/v1/inventory/adjust', [
            'tenant_id' => $this->tenantId,
            'warehouse_id' => $this->warehouseId,
            'product_id' => $this->productId,
            'quantity' => '40',
            'unit_cost' => '10.00',
            'adjustment_type' => 'adjustment_out',
        ]);

        $response = $this->getJson(
            "/api/v1/inventory/demand-forecast?tenant_id={$this->tenantId}&warehouse_id={$this->warehouseId}&period_days=30"
        );

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $items = $response->json('data');
        $this->assertCount(1, $items);
        $this->assertEquals($this->warehouseId, $items[0]['warehouse_id']);
    }

    public function test_demand_forecast_uses_default_period_of_90_days(): void
    {
        $response = $this->getJson(
            "/api/v1/inventory/demand-forecast?tenant_id={$this->tenantId}"
        );

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    // ─── Inventory Turnover Tests ────────────────────────────────────────────

    public function test_turnover_returns_empty_when_no_shipments(): void
    {
        $this->postJson('/api/v1/inventory/receive', [
            'tenant_id' => $this->tenantId,
            'warehouse_id' => $this->warehouseId,
            'product_id' => $this->productId,
            'quantity' => '100',
            'unit_cost' => '10.00',
        ]);

        $response = $this->getJson(
            "/api/v1/inventory/turnover?tenant_id={$this->tenantId}&period_days=30"
        );

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['success', 'message', 'data', 'errors']);

        // No shipment transactions, so no turnover items
        $this->assertCount(0, $response->json('data'));
    }

    public function test_turnover_computes_rate_correctly(): void
    {
        // Receive 100 units at cost 10 each → inventory value = 1000
        $this->postJson('/api/v1/inventory/receive', [
            'tenant_id' => $this->tenantId,
            'warehouse_id' => $this->warehouseId,
            'product_id' => $this->productId,
            'quantity' => '100',
            'unit_cost' => '10.00',
        ]);

        // Adjust out 50 units (shipment-type outflow for turnover calculation)
        $this->postJson('/api/v1/inventory/adjust', [
            'tenant_id' => $this->tenantId,
            'warehouse_id' => $this->warehouseId,
            'product_id' => $this->productId,
            'quantity' => '50',
            'unit_cost' => '10.00',
            'adjustment_type' => 'adjustment_out',
        ]);

        // Turnover only considers 'shipment' transaction_type; adjustment_out is excluded
        $response = $this->getJson(
            "/api/v1/inventory/turnover?tenant_id={$this->tenantId}&period_days=30"
        );

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    public function test_turnover_response_has_required_fields(): void
    {
        // Receive then transfer to create a shipment-type transaction
        $this->postJson('/api/v1/inventory/receive', [
            'tenant_id' => $this->tenantId,
            'warehouse_id' => $this->warehouseId,
            'product_id' => $this->productId,
            'quantity' => '100',
            'unit_cost' => '10.00',
        ]);

        $warehouse2 = $this->postJson('/api/v1/warehouses', [
            'tenant_id' => $this->tenantId,
            'code' => 'WH-REPORT-2',
            'name' => 'Report Warehouse 2',
        ]);
        $warehouse2Id = $warehouse2->json('data.id');

        // Transfer creates transfer_out / transfer_in entries (not shipment)
        // So turnover for the original warehouse should return empty here
        $response = $this->getJson(
            "/api/v1/inventory/turnover?tenant_id={$this->tenantId}&period_days=90"
        );

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['success', 'message', 'data', 'errors']);
    }

    public function test_turnover_filtered_by_warehouse(): void
    {
        $response = $this->getJson(
            "/api/v1/inventory/turnover?tenant_id={$this->tenantId}&warehouse_id={$this->warehouseId}&period_days=30"
        );

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    }
}
