<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

final class ItemUomCrudTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @var array<string, string>
     */
    private array $headers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        $login = $this->postJson('/api/auth/login', [
            'tenant_id' => 1,
            'organization_unit_id' => 1,
            'provider_key' => 'internal',
            'login_identifier' => 'admin@example.com',
            'password' => 'password',
            'device_name' => 'Item UOM feature test',
        ])->assertOk();

        $this->headers = [
            'Authorization' => 'Bearer '.$login->json('data.tokens.access_token'),
            'X-Tenant-ID' => '1',
            'X-Organization-Unit-ID' => '1',
        ];
    }

    public function test_uom_crud_and_lookup_are_tenant_scoped(): void
    {
        $created = $this->withHeaders($this->headers)
            ->postJson('/api/uom/uoms', [
                'uom_code' => 'BOX',
                'name' => 'Box',
                'symbol' => 'box',
                'decimal_precision' => 0,
                'is_base' => false,
                'status' => 'active',
                'notes' => 'Packaging unit.',
            ])
            ->assertCreated()
            ->assertJsonPath('data.uom_code', 'BOX')
            ->assertJsonPath('data.decimal_precision', 0);

        $uomId = (int) $created->json('data.id');

        $this->withHeaders($this->headers)
            ->postJson('/api/uom/uoms', [
                'uom_code' => 'BOX',
                'name' => 'Duplicate Box',
                'status' => 'active',
            ])
            ->assertUnprocessable()
            ->assertJsonStructure(['errors' => ['uom_code']]);

        $this->withHeaders($this->headers)
            ->getJson('/api/uom/uoms?search=Box&status=active')
            ->assertOk()
            ->assertJsonPath('meta.total', 1);

        $this->withHeaders($this->headers)
            ->getJson('/api/uom/uoms/lookup')
            ->assertOk()
            ->assertJsonFragment([
                'id' => $uomId,
                'uom_code' => 'BOX',
                'name' => 'Box',
                'symbol' => 'box',
            ])
            ->assertJsonMissingPath('data.0.notes');

        $this->withHeaders($this->headers)
            ->patchJson('/api/uom/uoms/'.$uomId, [
                'decimal_precision' => 3,
                'status' => 'inactive',
            ])
            ->assertOk()
            ->assertJsonPath('data.decimal_precision', 3)
            ->assertJsonPath('data.status', 'inactive');

        $this->withHeaders($this->headers)
            ->getJson('/api/uom/uoms/lookup')
            ->assertOk()
            ->assertJsonMissing(['uom_code' => 'BOX']);

        $foreignUomId = DB::table('unit_of_measures')->insertGetId([
            'tenant_id' => $this->createOtherTenant(),
            'organization_unit_id' => null,
            'uom_code' => 'FOREIGN',
            'name' => 'Foreign Unit',
            'decimal_precision' => 2,
            'is_base' => false,
            'status' => 'active',
            'row_version' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->withHeaders($this->headers)
            ->getJson('/api/uom/uoms/'.$foreignUomId)
            ->assertNotFound();

        $this->withHeaders($this->headers)
            ->deleteJson('/api/uom/uoms/'.$uomId)
            ->assertNoContent();

        $this->assertSoftDeleted('unit_of_measures', ['id' => $uomId, 'tenant_id' => 1]);
    }

    public function test_item_crud_validates_tenant_uoms_and_non_negative_values(): void
    {
        $baseUomId = (int) DB::table('unit_of_measures')
            ->where('tenant_id', 1)
            ->where('uom_code', 'PCS')
            ->value('id');
        $salesUomId = (int) DB::table('unit_of_measures')
            ->where('tenant_id', 1)
            ->where('uom_code', 'DAY')
            ->value('id');

        $created = $this->withHeaders($this->headers)
            ->postJson('/api/item/items', $this->itemPayload($baseUomId, $salesUomId))
            ->assertCreated()
            ->assertJsonPath('data.item_code', 'ITEM-100')
            ->assertJsonPath('data.base_uom.uom_code', 'PCS')
            ->assertJsonPath('data.sales_uom.uom_code', 'DAY')
            ->assertJsonPath('data.cost_price', '125.5000');

        $itemId = (int) $created->json('data.id');

        $this->withHeaders($this->headers)
            ->postJson('/api/item/items', $this->itemPayload($baseUomId, $salesUomId))
            ->assertUnprocessable()
            ->assertJsonStructure(['errors' => ['item_code', 'barcode']]);

        $this->withHeaders($this->headers)
            ->postJson('/api/item/items', [
                ...$this->itemPayload($baseUomId, $salesUomId),
                'item_code' => 'ITEM-NEGATIVE',
                'barcode' => 'NEGATIVE',
                'cost_price' => -1,
            ])
            ->assertUnprocessable()
            ->assertJsonStructure(['errors' => ['cost_price']]);

        $otherTenantId = $this->createOtherTenant();
        $foreignUomId = DB::table('unit_of_measures')->insertGetId([
            'tenant_id' => $otherTenantId,
            'organization_unit_id' => null,
            'uom_code' => 'OTHER-UOM',
            'name' => 'Other Tenant Unit',
            'decimal_precision' => 2,
            'is_base' => false,
            'status' => 'active',
            'row_version' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->withHeaders($this->headers)
            ->postJson('/api/item/items', [
                ...$this->itemPayload($baseUomId, $salesUomId),
                'item_code' => 'ITEM-FOREIGN-UOM',
                'barcode' => 'FOREIGN-UOM',
                'base_uom_id' => $foreignUomId,
            ])
            ->assertUnprocessable()
            ->assertJsonStructure(['errors' => ['base_uom_id']]);

        $this->withHeaders($this->headers)
            ->getJson('/api/item/items?search=Brake&status=active')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.total', 1)
            ->assertJsonMissingPath('data.0.description');

        $this->withHeaders($this->headers)
            ->patchJson('/api/item/items/'.$itemId, [
                'name' => 'Premium Brake Pad',
                'sales_price' => 195.75,
                'status' => 'inactive',
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Premium Brake Pad')
            ->assertJsonPath('data.sales_price', '195.7500')
            ->assertJsonPath('data.status', 'inactive')
            ->assertJsonPath('data.row_version', 2);

        $this->withHeaders($this->headers)
            ->deleteJson('/api/uom/uoms/'.$salesUomId)
            ->assertNoContent();

        $this->withHeaders($this->headers)
            ->getJson('/api/item/items/'.$itemId)
            ->assertOk()
            ->assertJsonPath('data.sales_uom.uom_code', 'DAY');

        $foreignItemId = DB::table('items')->insertGetId([
            'tenant_id' => $otherTenantId,
            'organization_unit_id' => null,
            'item_code' => 'ITEM-OTHER',
            'name' => 'Other Tenant Item',
            'base_uom_id' => $foreignUomId,
            'track_inventory' => true,
            'is_stock_item' => true,
            'is_service_item' => false,
            'cost_price' => 0,
            'sales_price' => 0,
            'reorder_level' => 0,
            'reorder_quantity' => 0,
            'status' => 'active',
            'row_version' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->withHeaders($this->headers)
            ->getJson('/api/item/items/'.$foreignItemId)
            ->assertNotFound();

        $this->withHeaders($this->headers)
            ->deleteJson('/api/item/items/'.$itemId)
            ->assertNoContent();

        $this->assertSoftDeleted('items', ['id' => $itemId, 'tenant_id' => 1]);
    }

    /**
     * @return array<string, mixed>
     */
    private function itemPayload(int $baseUomId, int $salesUomId): array
    {
        return [
            'item_code' => 'ITEM-100',
            'name' => 'Brake Pad',
            'display_name' => 'Brake Pad Premium',
            'item_type' => 'inventory',
            'base_uom_id' => $baseUomId,
            'purchase_uom_id' => $baseUomId,
            'sales_uom_id' => $salesUomId,
            'sku' => 'BP-100',
            'barcode' => '8901234567890',
            'description' => 'Ceramic brake pad.',
            'track_inventory' => true,
            'is_stock_item' => true,
            'is_service_item' => false,
            'cost_price' => 125.5,
            'sales_price' => 175,
            'reorder_level' => 10,
            'reorder_quantity' => 25,
            'status' => 'active',
            'notes' => 'Standard workshop stock.',
        ];
    }

    private function createOtherTenant(): int
    {
        $existingId = DB::table('tenants')->where('code', 'ITEM-OTHER')->value('id');
        if ($existingId !== null) {
            return (int) $existingId;
        }

        $tenant = (array) DB::table('tenants')->where('id', 1)->first();
        unset($tenant['id']);

        $tenant['code'] = 'ITEM-OTHER';
        $tenant['name'] = 'Item Other Tenant';
        $tenant['slug'] = 'item-other';
        $tenant['uuid'] = (string) Str::uuid();
        $tenant['isolation_key'] = 'item-other';
        $tenant['created_at'] = now();
        $tenant['updated_at'] = now();

        return (int) DB::table('tenants')->insertGetId($tenant);
    }
}
