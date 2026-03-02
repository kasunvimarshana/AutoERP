<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Wms;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WmsTest extends TestCase
{
    use RefreshDatabase;

    private int $tenantId;
    private int $warehouseId = 1;

    protected function setUp(): void
    {
        parent::setUp();

        $tenantResponse = $this->postJson('/api/v1/tenants', [
            'name' => 'WMS Test Tenant',
            'slug' => 'wms-test-tenant',
        ]);
        $this->tenantId = $tenantResponse->json('data.id');
    }

    // ─────────────────────────────────────────────
    // Zone tests
    // ─────────────────────────────────────────────

    public function test_can_create_zone(): void
    {
        $response = $this->postJson('/api/v1/wms/zones', [
            'tenant_id' => $this->tenantId,
            'warehouse_id' => $this->warehouseId,
            'name' => 'Cold Storage',
            'code' => 'COLD',
            'description' => 'Temperature-controlled cold storage area',
            'sort_order' => 1,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Cold Storage')
            ->assertJsonPath('data.code', 'COLD')
            ->assertJsonPath('data.tenant_id', $this->tenantId)
            ->assertJsonPath('data.warehouse_id', $this->warehouseId)
            ->assertJsonPath('data.is_active', true)
            ->assertJsonStructure(['data' => [
                'id', 'tenant_id', 'warehouse_id', 'name', 'code',
                'description', 'sort_order', 'is_active', 'created_at', 'updated_at',
            ]]);
    }

    public function test_zone_code_must_be_unique_per_warehouse_and_tenant(): void
    {
        $payload = [
            'tenant_id' => $this->tenantId,
            'warehouse_id' => $this->warehouseId,
            'name' => 'Bulk Storage',
            'code' => 'BULK',
        ];

        $this->postJson('/api/v1/wms/zones', $payload)->assertStatus(201);

        $response = $this->postJson('/api/v1/wms/zones', $payload);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_can_list_zones(): void
    {
        $this->postJson('/api/v1/wms/zones', [
            'tenant_id' => $this->tenantId,
            'warehouse_id' => $this->warehouseId,
            'name' => 'Zone One',
            'code' => 'Z1',
        ])->assertStatus(201);

        $this->postJson('/api/v1/wms/zones', [
            'tenant_id' => $this->tenantId,
            'warehouse_id' => $this->warehouseId,
            'name' => 'Zone Two',
            'code' => 'Z2',
        ])->assertStatus(201);

        $response = $this->getJson(
            "/api/v1/wms/zones?tenant_id={$this->tenantId}&warehouse_id={$this->warehouseId}"
        );

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('meta.total', 2);
    }

    public function test_can_get_zone_by_id(): void
    {
        $zone = $this->postJson('/api/v1/wms/zones', [
            'tenant_id' => $this->tenantId,
            'warehouse_id' => $this->warehouseId,
            'name' => 'Picking Zone',
            'code' => 'PICK',
        ])->json('data');

        $response = $this->getJson("/api/v1/wms/zones/{$zone['id']}?tenant_id={$this->tenantId}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $zone['id'])
            ->assertJsonPath('data.code', 'PICK');
    }

    public function test_returns_404_for_nonexistent_zone(): void
    {
        $response = $this->getJson("/api/v1/wms/zones/99999?tenant_id={$this->tenantId}");

        $response->assertStatus(404)
            ->assertJsonPath('success', false);
    }

    public function test_can_update_zone(): void
    {
        $zone = $this->postJson('/api/v1/wms/zones', [
            'tenant_id' => $this->tenantId,
            'warehouse_id' => $this->warehouseId,
            'name' => 'Old Name',
            'code' => 'OLD',
        ])->json('data');

        $response = $this->putJson("/api/v1/wms/zones/{$zone['id']}?tenant_id={$this->tenantId}", [
            'name' => 'Updated Name',
            'description' => 'Updated description',
            'sort_order' => 5,
            'is_active' => true,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Updated Name')
            ->assertJsonPath('data.sort_order', 5);
    }

    public function test_can_delete_zone(): void
    {
        $zone = $this->postJson('/api/v1/wms/zones', [
            'tenant_id' => $this->tenantId,
            'warehouse_id' => $this->warehouseId,
            'name' => 'Delete Me',
            'code' => 'DEL',
        ])->json('data');

        $response = $this->deleteJson("/api/v1/wms/zones/{$zone['id']}?tenant_id={$this->tenantId}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->getJson("/api/v1/wms/zones/{$zone['id']}?tenant_id={$this->tenantId}")
            ->assertStatus(404);
    }

    public function test_can_get_aisles_for_zone_empty_initially(): void
    {
        $zone = $this->postJson('/api/v1/wms/zones', [
            'tenant_id' => $this->tenantId,
            'warehouse_id' => $this->warehouseId,
            'name' => 'Empty Zone',
            'code' => 'EMPTY',
        ])->json('data');

        $response = $this->getJson("/api/v1/wms/zones/{$zone['id']}/aisles?tenant_id={$this->tenantId}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(0, 'data');
    }

    // ─────────────────────────────────────────────
    // Aisle tests
    // ─────────────────────────────────────────────

    public function test_can_create_aisle(): void
    {
        $zone = $this->postJson('/api/v1/wms/zones', [
            'tenant_id' => $this->tenantId,
            'warehouse_id' => $this->warehouseId,
            'name' => 'Zone A',
            'code' => 'ZA',
        ])->json('data');

        $response = $this->postJson('/api/v1/wms/aisles', [
            'tenant_id' => $this->tenantId,
            'zone_id' => $zone['id'],
            'name' => 'Aisle A',
            'code' => 'A',
            'sort_order' => 1,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Aisle A')
            ->assertJsonPath('data.code', 'A')
            ->assertJsonPath('data.zone_id', $zone['id'])
            ->assertJsonPath('data.tenant_id', $this->tenantId)
            ->assertJsonStructure(['data' => [
                'id', 'tenant_id', 'zone_id', 'name', 'code',
                'description', 'sort_order', 'is_active', 'created_at', 'updated_at',
            ]]);
    }

    // ─────────────────────────────────────────────
    // Bin tests
    // ─────────────────────────────────────────────

    public function test_can_create_bin(): void
    {
        $zone = $this->postJson('/api/v1/wms/zones', [
            'tenant_id' => $this->tenantId,
            'warehouse_id' => $this->warehouseId,
            'name' => 'Zone B',
            'code' => 'ZB',
        ])->json('data');

        $aisle = $this->postJson('/api/v1/wms/aisles', [
            'tenant_id' => $this->tenantId,
            'zone_id' => $zone['id'],
            'name' => 'Aisle B',
            'code' => 'B',
        ])->json('data');

        $response = $this->postJson('/api/v1/wms/bins', [
            'tenant_id' => $this->tenantId,
            'aisle_id' => $aisle['id'],
            'code' => 'B-01-01',
            'description' => 'Shelf 1, Row 1',
            'max_capacity' => 100,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.code', 'B-01-01')
            ->assertJsonPath('data.aisle_id', $aisle['id'])
            ->assertJsonPath('data.max_capacity', 100)
            ->assertJsonPath('data.current_capacity', 0)
            ->assertJsonStructure(['data' => [
                'id', 'tenant_id', 'aisle_id', 'code', 'description',
                'max_capacity', 'current_capacity', 'is_active', 'created_at', 'updated_at',
            ]]);
    }

    public function test_can_get_bins_for_aisle(): void
    {
        $zone = $this->postJson('/api/v1/wms/zones', [
            'tenant_id' => $this->tenantId,
            'warehouse_id' => $this->warehouseId,
            'name' => 'Zone C',
            'code' => 'ZC',
        ])->json('data');

        $aisle = $this->postJson('/api/v1/wms/aisles', [
            'tenant_id' => $this->tenantId,
            'zone_id' => $zone['id'],
            'name' => 'Aisle C',
            'code' => 'C',
        ])->json('data');

        $this->postJson('/api/v1/wms/bins', [
            'tenant_id' => $this->tenantId,
            'aisle_id' => $aisle['id'],
            'code' => 'C-01-01',
        ])->assertStatus(201);

        $this->postJson('/api/v1/wms/bins', [
            'tenant_id' => $this->tenantId,
            'aisle_id' => $aisle['id'],
            'code' => 'C-01-02',
        ])->assertStatus(201);

        $response = $this->getJson("/api/v1/wms/aisles/{$aisle['id']}/bins?tenant_id={$this->tenantId}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data');
    }

    // ─────────────────────────────────────────────
    // Cycle Count tests
    // ─────────────────────────────────────────────

    public function test_can_start_cycle_count_creates_in_draft(): void
    {
        $response = $this->postJson('/api/v1/wms/cycle-counts', [
            'tenant_id' => $this->tenantId,
            'warehouse_id' => $this->warehouseId,
            'notes' => 'Monthly cycle count',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.tenant_id', $this->tenantId)
            ->assertJsonPath('data.warehouse_id', $this->warehouseId)
            ->assertJsonStructure(['data' => [
                'id', 'tenant_id', 'warehouse_id', 'status', 'notes',
                'started_at', 'completed_at', 'created_at', 'updated_at',
            ]]);
    }

    public function test_can_advance_cycle_count_to_in_progress(): void
    {
        $cc = $this->postJson('/api/v1/wms/cycle-counts', [
            'tenant_id' => $this->tenantId,
            'warehouse_id' => $this->warehouseId,
        ])->json('data');

        $response = $this->postJson(
            "/api/v1/wms/cycle-counts/{$cc['id']}/start?tenant_id={$this->tenantId}"
        );

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'in_progress');

        $this->assertNotNull($response->json('data.started_at'));
    }

    public function test_can_record_cycle_count_line(): void
    {
        $cc = $this->postJson('/api/v1/wms/cycle-counts', [
            'tenant_id' => $this->tenantId,
            'warehouse_id' => $this->warehouseId,
        ])->json('data');

        $this->postJson("/api/v1/wms/cycle-counts/{$cc['id']}/start?tenant_id={$this->tenantId}");

        $response = $this->postJson("/api/v1/wms/cycle-counts/{$cc['id']}/lines", [
            'tenant_id' => $this->tenantId,
            'product_id' => 42,
            'system_qty' => '100.0000',
            'counted_qty' => '98.0000',
            'notes' => 'Two units missing',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.product_id', 42)
            ->assertJsonPath('data.cycle_count_id', $cc['id'])
            ->assertJsonStructure(['data' => [
                'id', 'cycle_count_id', 'tenant_id', 'product_id', 'bin_id',
                'system_qty', 'counted_qty', 'variance', 'notes', 'created_at', 'updated_at',
            ]]);
    }

    public function test_cannot_record_line_on_draft_cycle_count(): void
    {
        $cc = $this->postJson('/api/v1/wms/cycle-counts', [
            'tenant_id' => $this->tenantId,
            'warehouse_id' => $this->warehouseId,
        ])->json('data');

        // Do NOT advance to in_progress — it's still draft
        $response = $this->postJson("/api/v1/wms/cycle-counts/{$cc['id']}/lines", [
            'tenant_id' => $this->tenantId,
            'product_id' => 10,
            'system_qty' => '50.0000',
            'counted_qty' => '50.0000',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_can_complete_cycle_count(): void
    {
        $cc = $this->postJson('/api/v1/wms/cycle-counts', [
            'tenant_id' => $this->tenantId,
            'warehouse_id' => $this->warehouseId,
        ])->json('data');

        $this->postJson("/api/v1/wms/cycle-counts/{$cc['id']}/start?tenant_id={$this->tenantId}");

        $response = $this->postJson(
            "/api/v1/wms/cycle-counts/{$cc['id']}/complete?tenant_id={$this->tenantId}"
        );

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'completed');

        $this->assertNotNull($response->json('data.completed_at'));
    }

    public function test_cannot_complete_non_in_progress_cycle_count(): void
    {
        $cc = $this->postJson('/api/v1/wms/cycle-counts', [
            'tenant_id' => $this->tenantId,
            'warehouse_id' => $this->warehouseId,
        ])->json('data');

        // Cycle count is still draft — cannot complete
        $response = $this->postJson(
            "/api/v1/wms/cycle-counts/{$cc['id']}/complete?tenant_id={$this->tenantId}"
        );

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_can_get_cycle_count_lines_with_variance_calculated(): void
    {
        $cc = $this->postJson('/api/v1/wms/cycle-counts', [
            'tenant_id' => $this->tenantId,
            'warehouse_id' => $this->warehouseId,
        ])->json('data');

        $this->postJson("/api/v1/wms/cycle-counts/{$cc['id']}/start?tenant_id={$this->tenantId}");

        $this->postJson("/api/v1/wms/cycle-counts/{$cc['id']}/lines", [
            'tenant_id' => $this->tenantId,
            'product_id' => 5,
            'system_qty' => '100.0000',
            'counted_qty' => '95.0000',
        ])->assertStatus(201);

        $this->postJson("/api/v1/wms/cycle-counts/{$cc['id']}/lines", [
            'tenant_id' => $this->tenantId,
            'product_id' => 6,
            'system_qty' => '50.0000',
            'counted_qty' => '55.0000',
        ])->assertStatus(201);

        $response = $this->getJson(
            "/api/v1/wms/cycle-counts/{$cc['id']}/lines?tenant_id={$this->tenantId}"
        );

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data');

        $lines = $response->json('data');

        // First line: variance = 95 - 100 = -5
        $firstLine = collect($lines)->firstWhere('product_id', 5);
        $this->assertNotNull($firstLine);
        $this->assertEquals('-5.0000', $firstLine['variance']);

        // Second line: variance = 55 - 50 = +5
        $secondLine = collect($lines)->firstWhere('product_id', 6);
        $this->assertNotNull($secondLine);
        $this->assertEquals('5.0000', $secondLine['variance']);
    }
}
