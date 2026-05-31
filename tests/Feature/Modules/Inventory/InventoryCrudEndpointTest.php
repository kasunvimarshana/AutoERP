<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Inventory;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class InventoryCrudEndpointTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_inventory_lists_transfer_adjustment_and_preview_endpoints_work(): void
    {
        [$tenantId, $organizationUnitId, $headers] = $this->authenticatedHeaders();

        $item = DB::table('items')->where('tenant_id', $tenantId)->where('sku', 'ITM-FILTER-001')->first();
        self::assertNotNull($item);

        $uomId = (int) $item->base_uom_id;
        $fromWarehouseId = (int) DB::table('warehouses')->where('tenant_id', $tenantId)->where('code', 'MAIN')->value('id');
        $toWarehouseId = (int) DB::table('warehouses')->where('tenant_id', $tenantId)->where('code', 'SERVICE')->value('id');
        $fromLocationId = (int) DB::table('warehouse_locations')->where('tenant_id', $tenantId)->where('code', 'MAIN-BIN')->value('id');
        $toLocationId = (int) DB::table('warehouse_locations')->where('tenant_id', $tenantId)->where('code', 'SERVICE-BIN')->value('id');
        $userId = (int) DB::table('users')->where('email', 'admin@example.com')->value('id');
        $kilometerUomId = (int) DB::table('unit_of_measures')->where('tenant_id', $tenantId)->where('code', 'KM')->value('id');

        $this->withHeaders($headers)
            ->getJson('/api/inventory/stock-levels')
            ->assertOk()
            ->assertJsonStructure(['data'])
            ->assertJsonPath('data.0.item.sku', 'ITM-FILTER-001')
            ->assertJsonPath('data.0.item_label', 'ITM-FILTER-001 - Oil Filter')
            ->assertJsonPath('data.0.warehouse_label', 'MAIN - Main Warehouse')
            ->assertJsonPath('data.0.location_label', 'MAIN-BIN - Main Bin')
            ->assertJsonPath('data.0.base_uom_label', 'PCS');

        $this->withHeaders($headers)
            ->getJson('/api/inventory/stock-levels?search=Oil&warehouse_id='.$fromWarehouseId.'&uom_id='.$uomId.'&status=good')
            ->assertOk()
            ->assertJsonPath('data.0.item_label', 'ITM-FILTER-001 - Oil Filter')
            ->assertJsonPath('data.0.warehouse_label', 'MAIN - Main Warehouse')
            ->assertJsonPath('data.0.base_uom_label', 'PCS');

        $uomSetup = $this->withHeaders($headers)
            ->getJson('/api/item/items/'.$item->id.'/uom-setup?context=inventory')
            ->assertOk()
            ->json('data.allowed_uoms');

        self::assertSame(['PCS', 'BOX', 'PACK'], array_values(array_map(
            static fn (array $uom): string => (string) $uom['code'],
            $uomSetup,
        )));

        $this->withHeaders($headers)
            ->getJson('/api/inventory/stock-movements')
            ->assertOk()
            ->assertJsonStructure(['data']);

        $this->withHeaders($headers)
            ->postJson('/api/inventory/engines/stock-availability/preview', [
                'warehouse_id' => $fromWarehouseId,
                'location_id' => $fromLocationId,
                'item_id' => (int) $item->id,
                'uom_id' => $uomId,
                'quantity' => 1,
            ])
            ->assertOk()
            ->assertJsonPath('calculated.decision', 'available')
            ->assertJsonPath('calculated.requested_quantity', '1.0000')
            ->assertJsonPath('calculated.baseRequestedQuantity', '1.0000');

        $this->withHeaders($headers)
            ->postJson('/api/inventory/engines/stock-availability/preview', [
                'warehouse_id' => $fromWarehouseId,
                'location_id' => $fromLocationId,
                'item_id' => (int) $item->id,
                'uom_id' => $kilometerUomId,
                'quantity' => 1,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['uom_id']);

        $transferResponse = $this->withHeaders($headers)
            ->postJson('/api/inventory/stock-transfers', [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $organizationUnitId,
                'reference_number' => 'TRF-FEATURE-001',
                'from_warehouse_id' => $fromWarehouseId,
                'to_warehouse_id' => $toWarehouseId,
                'from_location_id' => $fromLocationId,
                'to_location_id' => $toLocationId,
                'requested_by' => $userId,
                'status' => 'DRAFT',
                'lines' => [[
                    'item_id' => (int) $item->id,
                    'uom_id' => $uomId,
                    'quantity' => 1,
                    'from_location_id' => $fromLocationId,
                    'to_location_id' => $toLocationId,
                ]],
            ])
            ->assertCreated()
            ->assertJsonPath('data.reference_number', 'TRF-FEATURE-001');

        $transferId = (int) $transferResponse->json('data.id');

        $this->withHeaders($headers)
            ->getJson('/api/inventory/stock-transfers/'.$transferId)
            ->assertOk()
            ->assertJsonPath('data.id', $transferId);

        $this->withHeaders($headers)
            ->putJson('/api/inventory/stock-transfers/'.$transferId, [
                'status' => 'PENDING',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'PENDING');

        $adjustmentResponse = $this->withHeaders($headers)
            ->postJson('/api/inventory/stock-adjustments', [
                'tenant_id' => $tenantId,
                'organization_unit_id' => $organizationUnitId,
                'reference_number' => 'ADJ-FEATURE-001',
                'warehouse_id' => $fromWarehouseId,
                'location_id' => $fromLocationId,
                'status' => 'DRAFT',
                'counted_by' => $userId,
                'reason' => 'Feature test adjustment',
                'lines' => [[
                    'item_id' => (int) $item->id,
                    'uom_id' => $uomId,
                    'direction' => 'INCREASE',
                    'adjustment_quantity' => 1,
                ]],
            ])
            ->assertCreated()
            ->assertJsonPath('data.reference_number', 'ADJ-FEATURE-001');

        $adjustmentId = (int) $adjustmentResponse->json('data.id');

        $this->withHeaders($headers)
            ->putJson('/api/inventory/stock-adjustments/'.$adjustmentId, [
                'status' => 'COMPLETED',
                'approved_at' => now()->toISOString(),
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'COMPLETED');

        $this->withHeaders($headers)
            ->getJson('/api/inventory/trace-logs')
            ->assertOk()
            ->assertJsonStructure(['data']);

        $this->withHeaders($headers)
            ->getJson('/api/inventory/inventory-cost-layers')
            ->assertOk()
            ->assertJsonStructure(['data']);
    }

    public function test_inventory_validation_errors_are_returned(): void
    {
        [, , $headers] = $this->authenticatedHeaders();

        $this->withHeaders($headers)
            ->postJson('/api/inventory/stock-transfers', ['reference_number' => ''])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['tenant_id', 'reference_number', 'from_warehouse_id', 'to_warehouse_id', 'requested_by']);

        $this->withHeaders($headers)
            ->postJson('/api/inventory/engines/stock-availability/preview', [
                'quantity' => 1,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['item_id', 'uom_id']);
    }

    /**
     * @return array{0:int,1:int,2:array<string,string>}
     */
    private function authenticatedHeaders(): array
    {
        $tenantId = (int) DB::table('tenants')->where('code', 'AUTOERP')->value('id');
        $organizationUnitId = (int) DB::table('organization_units')
            ->where('tenant_id', $tenantId)
            ->where('code', 'MAIN')
            ->value('id');

        $loginResponse = $this->postJson('/api/auth/login', [
            'login_identifier' => 'admin@example.com',
            'password' => 'password',
            'provider_key' => 'internal',
            'tenant_id' => $tenantId,
        ]);

        $loginResponse->assertOk();

        return [
            $tenantId,
            $organizationUnitId,
            [
                'Authorization' => 'Bearer '.(string) $loginResponse->json('data.tokens.access_token'),
                'X-Organization-Unit-ID' => (string) $organizationUnitId,
                'X-Tenant-ID' => (string) $tenantId,
            ],
        ];
    }
}
