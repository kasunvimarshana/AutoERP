<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Inventory;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryCarryingCostTest extends TestCase
{
    use RefreshDatabase;

    private int $tenantId;

    private int $productId;

    private int $warehouseId;

    protected function setUp(): void
    {
        parent::setUp();

        $tenant = $this->postJson('/api/v1/tenants', [
            'name' => 'Carrying Cost Test Tenant',
            'slug' => 'carrying-cost-test-tenant',
        ]);
        $this->tenantId = $tenant->json('data.id');

        $product = $this->postJson('/api/v1/products', [
            'tenant_id' => $this->tenantId,
            'sku' => 'CARRY-001',
            'name' => 'Carrying Cost Product',
            'type' => 'stockable',
            'uom' => 'pcs',
            'costing_method' => 'fifo',
            'cost_price' => '10.00',
            'sale_price' => '20.00',
        ]);
        $this->productId = $product->json('data.id');

        $warehouse = $this->postJson('/api/v1/warehouses', [
            'tenant_id' => $this->tenantId,
            'code' => 'WH-CARRY',
            'name' => 'Carrying Cost Warehouse',
        ]);
        $this->warehouseId = $warehouse->json('data.id');
    }

    // ─── Carrying Cost Tests ─────────────────────────────────────────────────

    public function test_carrying_costs_returns_empty_when_no_stock(): void
    {
        $response = $this->getJson(
            "/api/v1/inventory/carrying-costs?tenant_id={$this->tenantId}"
        );

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success', 'message', 'data', 'errors',
                'meta' => ['grand_total_carrying_cost', 'carrying_rate', 'period_days'],
            ]);

        $this->assertCount(0, $response->json('data'));
        $this->assertEquals('0', $response->json('meta.grand_total_carrying_cost'));
    }

    public function test_carrying_costs_computes_correctly(): void
    {
        // Receive 100 units at cost 10 each → inventory value = 1000
        $this->postJson('/api/v1/inventory/receive', [
            'tenant_id' => $this->tenantId,
            'warehouse_id' => $this->warehouseId,
            'product_id' => $this->productId,
            'quantity' => '100',
            'unit_cost' => '10.00',
        ]);

        // carrying_cost = 1000 × 0.25 / 365 × 365 = 250 (annual at 25%)
        $response = $this->getJson(
            "/api/v1/inventory/carrying-costs?tenant_id={$this->tenantId}&period_days=365&carrying_rate=0.25"
        );

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success', 'message',
                'data' => [
                    '*' => [
                        'product_id', 'warehouse_id', 'quantity_on_hand',
                        'average_cost', 'inventory_value', 'carrying_cost',
                        'carrying_rate', 'period_days',
                    ],
                ],
                'meta' => ['grand_total_carrying_cost', 'carrying_rate', 'period_days'],
                'errors',
            ]);

        $items = $response->json('data');
        $this->assertCount(1, $items);
        $this->assertEquals($this->productId, $items[0]['product_id']);
        $this->assertEquals('100.0000', $items[0]['quantity_on_hand']);
        $this->assertEquals('10.0000', $items[0]['average_cost']);
        $this->assertEquals('1000.0000', $items[0]['inventory_value']);
        $this->assertEquals('0.25', $items[0]['carrying_rate']);
        $this->assertEquals(365, $items[0]['period_days']);

        // Verify grand total equals item carrying cost
        $this->assertEquals(
            $response->json('meta.grand_total_carrying_cost'),
            $items[0]['carrying_cost']
        );
    }

    public function test_carrying_costs_uses_default_rate_and_period(): void
    {
        $this->postJson('/api/v1/inventory/receive', [
            'tenant_id' => $this->tenantId,
            'warehouse_id' => $this->warehouseId,
            'product_id' => $this->productId,
            'quantity' => '50',
            'unit_cost' => '20.00',
        ]);

        $response = $this->getJson(
            "/api/v1/inventory/carrying-costs?tenant_id={$this->tenantId}"
        );

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('meta.carrying_rate', '0.25')
            ->assertJsonPath('meta.period_days', 365);
    }

    public function test_carrying_costs_filtered_by_warehouse(): void
    {
        $this->postJson('/api/v1/inventory/receive', [
            'tenant_id' => $this->tenantId,
            'warehouse_id' => $this->warehouseId,
            'product_id' => $this->productId,
            'quantity' => '100',
            'unit_cost' => '10.00',
        ]);

        $response = $this->getJson(
            "/api/v1/inventory/carrying-costs?tenant_id={$this->tenantId}&warehouse_id={$this->warehouseId}"
        );

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $items = $response->json('data');
        $this->assertCount(1, $items);
        $this->assertEquals($this->warehouseId, $items[0]['warehouse_id']);
    }

    public function test_carrying_costs_grand_total_sums_all_items(): void
    {
        $product2 = $this->postJson('/api/v1/products', [
            'tenant_id' => $this->tenantId,
            'sku' => 'CARRY-002',
            'name' => 'Carrying Cost Product 2',
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
            'quantity' => '100',
            'unit_cost' => '10.00',
        ]);

        $this->postJson('/api/v1/inventory/receive', [
            'tenant_id' => $this->tenantId,
            'warehouse_id' => $this->warehouseId,
            'product_id' => $productId2,
            'quantity' => '200',
            'unit_cost' => '5.00',
        ]);

        $response = $this->getJson(
            "/api/v1/inventory/carrying-costs?tenant_id={$this->tenantId}&period_days=365&carrying_rate=0.25"
        );

        $response->assertStatus(200);
        $items = $response->json('data');
        $this->assertCount(2, $items);

        $grandTotal = $response->json('meta.grand_total_carrying_cost');
        $sumFromItems = array_reduce(
            $items,
            fn (string $carry, array $item) => bcadd($carry, $item['carrying_cost'], 4),
            '0'
        );

        $this->assertEquals(0, bccomp($grandTotal, $sumFromItems, 4));
    }

    public function test_carrying_costs_sorted_by_cost_descending(): void
    {
        $product2 = $this->postJson('/api/v1/products', [
            'tenant_id' => $this->tenantId,
            'sku' => 'CARRY-003',
            'name' => 'Carrying Cost Product 3',
            'type' => 'stockable',
            'uom' => 'pcs',
            'costing_method' => 'fifo',
            'cost_price' => '5.00',
            'sale_price' => '10.00',
        ]);
        $productId2 = $product2->json('data.id');

        // Product 1: 100 × 10 = 1000 (higher value)
        $this->postJson('/api/v1/inventory/receive', [
            'tenant_id' => $this->tenantId,
            'warehouse_id' => $this->warehouseId,
            'product_id' => $this->productId,
            'quantity' => '100',
            'unit_cost' => '10.00',
        ]);

        // Product 2: 10 × 5 = 50 (lower value)
        $this->postJson('/api/v1/inventory/receive', [
            'tenant_id' => $this->tenantId,
            'warehouse_id' => $this->warehouseId,
            'product_id' => $productId2,
            'quantity' => '10',
            'unit_cost' => '5.00',
        ]);

        $response = $this->getJson(
            "/api/v1/inventory/carrying-costs?tenant_id={$this->tenantId}"
        );

        $items = $response->json('data');
        $this->assertCount(2, $items);
        $this->assertGreaterThan(
            (float) $items[1]['carrying_cost'],
            (float) $items[0]['carrying_cost']
        );
    }
}
