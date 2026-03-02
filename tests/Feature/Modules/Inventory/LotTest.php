<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Inventory;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LotTest extends TestCase
{
    use RefreshDatabase;

    private int $tenantId;

    private int $productId;

    private int $warehouseId;

    protected function setUp(): void
    {
        parent::setUp();

        $tenant = $this->postJson('/api/v1/tenants', [
            'name' => 'Lot Test Tenant',
            'slug' => 'lot-test-tenant',
        ]);
        $this->tenantId = $tenant->json('data.id');

        $product = $this->postJson('/api/v1/products', [
            'tenant_id' => $this->tenantId,
            'sku' => 'LOT-TEST-001',
            'name' => 'Lot Test Product',
            'type' => 'stockable',
            'uom' => 'pcs',
            'costing_method' => 'fifo',
            'cost_price' => '10.00',
            'sale_price' => '20.00',
        ]);
        $this->productId = $product->json('data.id');

        $warehouse = $this->postJson('/api/v1/warehouses', [
            'tenant_id' => $this->tenantId,
            'code' => 'WH-LOT',
            'name' => 'Lot Warehouse',
        ]);
        $this->warehouseId = $warehouse->json('data.id');
    }

    public function test_can_create_lot(): void
    {
        $response = $this->postJson('/api/v1/inventory/lots', [
            'tenant_id' => $this->tenantId,
            'product_id' => $this->productId,
            'warehouse_id' => $this->warehouseId,
            'lot_number' => 'LOT-2024-001',
            'serial_number' => 'SN-001',
            'batch_number' => 'BATCH-001',
            'manufactured_date' => '2024-01-01',
            'expiry_date' => '2025-01-01',
            'quantity' => '100',
            'notes' => 'Initial lot',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success', 'message',
                'data' => [
                    'id', 'tenant_id', 'product_id', 'warehouse_id',
                    'lot_number', 'serial_number', 'batch_number',
                    'manufactured_date', 'expiry_date', 'quantity', 'notes',
                    'created_at', 'updated_at',
                ],
                'errors',
            ])
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.lot_number', 'LOT-2024-001')
            ->assertJsonPath('data.tenant_id', $this->tenantId)
            ->assertJsonPath('data.product_id', $this->productId)
            ->assertJsonPath('data.warehouse_id', $this->warehouseId);
    }

    public function test_can_list_lots(): void
    {
        $this->postJson('/api/v1/inventory/lots', [
            'tenant_id' => $this->tenantId,
            'product_id' => $this->productId,
            'warehouse_id' => $this->warehouseId,
            'lot_number' => 'LOT-LIST-001',
            'quantity' => '50',
        ]);

        $response = $this->getJson("/api/v1/inventory/lots?tenant_id={$this->tenantId}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success', 'message', 'data', 'errors',
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ]);

        $this->assertGreaterThanOrEqual(1, count($response->json('data')));
    }

    public function test_can_list_lots_filtered_by_product(): void
    {
        $this->postJson('/api/v1/inventory/lots', [
            'tenant_id' => $this->tenantId,
            'product_id' => $this->productId,
            'warehouse_id' => $this->warehouseId,
            'lot_number' => 'LOT-PROD-001',
            'quantity' => '10',
        ]);

        $response = $this->getJson(
            "/api/v1/inventory/lots?tenant_id={$this->tenantId}&product_id={$this->productId}"
        );

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        foreach ($response->json('data') as $lot) {
            $this->assertEquals($this->productId, $lot['product_id']);
        }
    }

    public function test_can_list_lots_filtered_by_warehouse(): void
    {
        $this->postJson('/api/v1/inventory/lots', [
            'tenant_id' => $this->tenantId,
            'product_id' => $this->productId,
            'warehouse_id' => $this->warehouseId,
            'lot_number' => 'LOT-WH-001',
            'quantity' => '20',
        ]);

        $response = $this->getJson(
            "/api/v1/inventory/lots?tenant_id={$this->tenantId}&warehouse_id={$this->warehouseId}"
        );

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        foreach ($response->json('data') as $lot) {
            $this->assertEquals($this->warehouseId, $lot['warehouse_id']);
        }
    }

    public function test_can_get_lot_by_id(): void
    {
        $created = $this->postJson('/api/v1/inventory/lots', [
            'tenant_id' => $this->tenantId,
            'product_id' => $this->productId,
            'warehouse_id' => $this->warehouseId,
            'lot_number' => 'LOT-GET-001',
            'quantity' => '30',
        ]);
        $lotId = $created->json('data.id');

        $response = $this->getJson("/api/v1/inventory/lots/{$lotId}?tenant_id={$this->tenantId}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $lotId)
            ->assertJsonPath('data.lot_number', 'LOT-GET-001');
    }

    public function test_returns_404_for_nonexistent_lot(): void
    {
        $response = $this->getJson('/api/v1/inventory/lots/99999?tenant_id='.$this->tenantId);

        $response->assertStatus(404)
            ->assertJsonPath('success', false);
    }

    public function test_can_update_lot(): void
    {
        $created = $this->postJson('/api/v1/inventory/lots', [
            'tenant_id' => $this->tenantId,
            'product_id' => $this->productId,
            'warehouse_id' => $this->warehouseId,
            'lot_number' => 'LOT-UPDATE-001',
            'quantity' => '40',
        ]);
        $lotId = $created->json('data.id');

        $response = $this->putJson("/api/v1/inventory/lots/{$lotId}", [
            'tenant_id' => $this->tenantId,
            'lot_number' => 'LOT-UPDATE-REVISED',
            'quantity' => '45',
            'notes' => 'Updated notes',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.lot_number', 'LOT-UPDATE-REVISED')
            ->assertJsonPath('data.notes', 'Updated notes');
    }

    public function test_can_delete_lot(): void
    {
        $created = $this->postJson('/api/v1/inventory/lots', [
            'tenant_id' => $this->tenantId,
            'product_id' => $this->productId,
            'warehouse_id' => $this->warehouseId,
            'lot_number' => 'LOT-DELETE-001',
            'quantity' => '10',
        ]);
        $lotId = $created->json('data.id');

        $response = $this->deleteJson("/api/v1/inventory/lots/{$lotId}?tenant_id={$this->tenantId}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        // Confirm it's gone
        $this->getJson("/api/v1/inventory/lots/{$lotId}?tenant_id={$this->tenantId}")
            ->assertStatus(404);
    }

    public function test_create_lot_validates_required_fields(): void
    {
        $response = $this->postJson('/api/v1/inventory/lots', []);

        $response->assertStatus(422);
    }

    public function test_lot_quantity_must_be_non_negative(): void
    {
        $response = $this->postJson('/api/v1/inventory/lots', [
            'tenant_id' => $this->tenantId,
            'product_id' => $this->productId,
            'warehouse_id' => $this->warehouseId,
            'lot_number' => 'LOT-NEG-001',
            'quantity' => '-1',
        ]);

        $response->assertStatus(422);
    }
}
