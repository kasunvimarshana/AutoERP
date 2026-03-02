<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Inventory;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReorderRuleTest extends TestCase
{
    use RefreshDatabase;

    private int $tenantId;

    private int $productId;

    private int $warehouseId;

    protected function setUp(): void
    {
        parent::setUp();

        $tenant = $this->postJson('/api/v1/tenants', [
            'name' => 'Reorder Test Tenant',
            'slug' => 'reorder-test-tenant',
        ]);
        $this->tenantId = $tenant->json('data.id');

        $product = $this->postJson('/api/v1/products', [
            'tenant_id' => $this->tenantId,
            'sku' => 'REORDER-TEST-001',
            'name' => 'Reorder Test Product',
            'type' => 'stockable',
            'uom' => 'pcs',
            'costing_method' => 'fifo',
            'cost_price' => '10.00',
            'sale_price' => '20.00',
        ]);
        $this->productId = $product->json('data.id');

        $warehouse = $this->postJson('/api/v1/warehouses', [
            'tenant_id' => $this->tenantId,
            'code' => 'WH-REORDER',
            'name' => 'Reorder Warehouse',
        ]);
        $this->warehouseId = $warehouse->json('data.id');
    }

    public function test_can_create_reorder_rule(): void
    {
        $response = $this->postJson('/api/v1/inventory/reorder-rules', [
            'tenant_id' => $this->tenantId,
            'product_id' => $this->productId,
            'warehouse_id' => $this->warehouseId,
            'reorder_point' => '50',
            'reorder_quantity' => '100',
            'is_active' => true,
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success', 'message',
                'data' => [
                    'id', 'tenant_id', 'product_id', 'warehouse_id',
                    'reorder_point', 'reorder_quantity', 'is_active',
                    'created_at', 'updated_at',
                ],
                'errors',
            ])
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.tenant_id', $this->tenantId)
            ->assertJsonPath('data.product_id', $this->productId)
            ->assertJsonPath('data.warehouse_id', $this->warehouseId);
    }

    public function test_can_list_reorder_rules(): void
    {
        $this->postJson('/api/v1/inventory/reorder-rules', [
            'tenant_id' => $this->tenantId,
            'product_id' => $this->productId,
            'warehouse_id' => $this->warehouseId,
            'reorder_point' => '20',
            'reorder_quantity' => '50',
        ]);

        $response = $this->getJson("/api/v1/inventory/reorder-rules?tenant_id={$this->tenantId}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success', 'message', 'data', 'errors',
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ]);

        $this->assertGreaterThanOrEqual(1, count($response->json('data')));
    }

    public function test_can_get_reorder_rule_by_id(): void
    {
        $created = $this->postJson('/api/v1/inventory/reorder-rules', [
            'tenant_id' => $this->tenantId,
            'product_id' => $this->productId,
            'warehouse_id' => $this->warehouseId,
            'reorder_point' => '30',
            'reorder_quantity' => '60',
        ]);
        $ruleId = $created->json('data.id');

        $response = $this->getJson("/api/v1/inventory/reorder-rules/{$ruleId}?tenant_id={$this->tenantId}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $ruleId)
            ->assertJsonPath('data.product_id', $this->productId);
    }

    public function test_returns_404_for_nonexistent_reorder_rule(): void
    {
        $response = $this->getJson('/api/v1/inventory/reorder-rules/99999?tenant_id='.$this->tenantId);

        $response->assertStatus(404)
            ->assertJsonPath('success', false);
    }

    public function test_can_update_reorder_rule(): void
    {
        $created = $this->postJson('/api/v1/inventory/reorder-rules', [
            'tenant_id' => $this->tenantId,
            'product_id' => $this->productId,
            'warehouse_id' => $this->warehouseId,
            'reorder_point' => '25',
            'reorder_quantity' => '75',
        ]);
        $ruleId = $created->json('data.id');

        $response = $this->putJson("/api/v1/inventory/reorder-rules/{$ruleId}", [
            'tenant_id' => $this->tenantId,
            'reorder_point' => '40',
            'reorder_quantity' => '80',
            'is_active' => true,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $ruleId);
    }

    public function test_can_delete_reorder_rule(): void
    {
        $created = $this->postJson('/api/v1/inventory/reorder-rules', [
            'tenant_id' => $this->tenantId,
            'product_id' => $this->productId,
            'warehouse_id' => $this->warehouseId,
            'reorder_point' => '15',
            'reorder_quantity' => '45',
        ]);
        $ruleId = $created->json('data.id');

        $response = $this->deleteJson(
            "/api/v1/inventory/reorder-rules/{$ruleId}?tenant_id={$this->tenantId}"
        );

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        // Confirm it's gone
        $this->getJson("/api/v1/inventory/reorder-rules/{$ruleId}?tenant_id={$this->tenantId}")
            ->assertStatus(404);
    }

    public function test_reorder_rule_unique_per_product_warehouse(): void
    {
        $this->postJson('/api/v1/inventory/reorder-rules', [
            'tenant_id' => $this->tenantId,
            'product_id' => $this->productId,
            'warehouse_id' => $this->warehouseId,
            'reorder_point' => '10',
            'reorder_quantity' => '50',
        ])->assertStatus(201);

        $response = $this->postJson('/api/v1/inventory/reorder-rules', [
            'tenant_id' => $this->tenantId,
            'product_id' => $this->productId,
            'warehouse_id' => $this->warehouseId,
            'reorder_point' => '20',
            'reorder_quantity' => '60',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_low_stock_alert_returns_items_below_reorder_point(): void
    {
        $this->postJson('/api/v1/inventory/reorder-rules', [
            'tenant_id' => $this->tenantId,
            'product_id' => $this->productId,
            'warehouse_id' => $this->warehouseId,
            'reorder_point' => '50',
            'reorder_quantity' => '100',
            'is_active' => true,
        ]);

        $this->postJson('/api/v1/inventory/receive', [
            'tenant_id' => $this->tenantId,
            'warehouse_id' => $this->warehouseId,
            'product_id' => $this->productId,
            'quantity' => '20',
            'unit_cost' => '10.00',
        ]);

        $response = $this->getJson(
            "/api/v1/inventory/low-stock?tenant_id={$this->tenantId}&warehouse_id={$this->warehouseId}"
        );

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $items = $response->json('data');
        $this->assertNotEmpty($items);

        $productIds = array_column(array_column($items, 'rule'), 'product_id');
        $this->assertContains($this->productId, $productIds);
    }

    public function test_low_stock_alert_excludes_items_above_reorder_point(): void
    {
        $this->postJson('/api/v1/inventory/reorder-rules', [
            'tenant_id' => $this->tenantId,
            'product_id' => $this->productId,
            'warehouse_id' => $this->warehouseId,
            'reorder_point' => '10',
            'reorder_quantity' => '50',
            'is_active' => true,
        ]);

        $this->postJson('/api/v1/inventory/receive', [
            'tenant_id' => $this->tenantId,
            'warehouse_id' => $this->warehouseId,
            'product_id' => $this->productId,
            'quantity' => '100',
            'unit_cost' => '10.00',
        ]);

        $response = $this->getJson(
            "/api/v1/inventory/low-stock?tenant_id={$this->tenantId}&warehouse_id={$this->warehouseId}"
        );

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $items = $response->json('data');
        $productIds = array_column(array_column($items, 'rule'), 'product_id');
        $this->assertNotContains($this->productId, $productIds);
    }

    public function test_abc_analysis_returns_categories(): void
    {
        $this->postJson('/api/v1/inventory/receive', [
            'tenant_id' => $this->tenantId,
            'warehouse_id' => $this->warehouseId,
            'product_id' => $this->productId,
            'quantity' => '100',
            'unit_cost' => '10.00',
        ]);

        $response = $this->getJson(
            "/api/v1/inventory/abc-analysis?tenant_id={$this->tenantId}"
        );

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $items = $response->json('data');
        $this->assertNotEmpty($items);

        $this->assertArrayHasKey('product_id', $items[0]);
        $this->assertArrayHasKey('abc_category', $items[0]);
    }

    public function test_abc_analysis_categorizes_correctly(): void
    {
        $this->postJson('/api/v1/inventory/receive', [
            'tenant_id' => $this->tenantId,
            'warehouse_id' => $this->warehouseId,
            'product_id' => $this->productId,
            'quantity' => '100',
            'unit_cost' => '10.00',
        ]);

        $response = $this->getJson(
            "/api/v1/inventory/abc-analysis?tenant_id={$this->tenantId}"
        );

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $items = $response->json('data');
        $this->assertCount(1, $items);

        // Single item has 100% cumulative value; since 100 > 95 (B threshold), it falls into category C
        $this->assertEquals('C', $items[0]['abc_category']);
        $this->assertEquals($this->productId, $items[0]['product_id']);
    }
}
