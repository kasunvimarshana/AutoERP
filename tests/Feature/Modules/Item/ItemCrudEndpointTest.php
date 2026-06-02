<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Item;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class ItemCrudEndpointTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_item_crud_works_with_real_backend_context(): void
    {
        [$tenantId, $organizationUnitId, $headers] = $this->authenticatedHeaders();

        $categoryId = (int) DB::table('item_categories')
            ->where('tenant_id', $tenantId)
            ->where('code', 'GENERAL')
            ->value('id');
        $uomId = (int) DB::table('unit_of_measures')
            ->where('tenant_id', $tenantId)
            ->where('name', 'Each')
            ->value('id');
        $hourUomId = (int) DB::table('unit_of_measures')
            ->where('tenant_id', $tenantId)
            ->where('name', 'Hour')
            ->value('id');
        $itemTypeId = (int) DB::table('item_types')
            ->whereNull('tenant_id')
            ->where('code', 'INVENTORY_PRODUCT')
            ->value('id');

        $this->withHeaders($headers)
            ->getJson('/api/item/item-types')
            ->assertOk()
            ->assertJsonPath('data.0.code', 'COMBO');

        $createResponse = $this->withHeaders($headers)->postJson('/api/item/items', [
            'base_uom_id' => $uomId,
            'category_id' => $categoryId,
            'item_type_id' => $itemTypeId,
            'name' => 'Test Stock Item',
            'sku' => 'ITM-TST-001',
            'status' => 'ACTIVE',
            'type' => 'inventory_product',
            'is_stockable' => true,
            'is_purchasable' => true,
            'is_sellable' => true,
        ]);

        $createResponse
            ->assertCreated()
            ->assertJsonPath('data.sku', 'ITM-TST-001')
            ->assertJsonPath('data.name', 'Test Stock Item');

        $itemId = (int) $createResponse->json('data.id');

        $this->assertDatabaseHas('items', [
            'id' => $itemId,
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'sku' => 'ITM-TST-001',
            'lead_time_days' => 0,
        ]);

        $this->withHeaders($headers)
            ->getJson('/api/item/items?search=ITM-TST-001')
            ->assertOk()
            ->assertJsonPath('data.0.id', $itemId);

        $this->withHeaders($headers)
            ->getJson('/api/item/items/lookup?q=ITM-TST-001&limit=10')
            ->assertOk()
            ->assertJsonPath('data.0.id', $itemId)
            ->assertJsonPath('data.0.category_name', 'General');

        $this->withHeaders($headers)
            ->getJson('/api/item/items/'.$itemId)
            ->assertOk()
            ->assertJsonPath('data.id', $itemId);

        $this->withHeaders($headers)
            ->getJson('/api/item/items/'.$itemId.'/capabilities')
            ->assertOk()
            ->assertJsonPath('data.stockable', true)
            ->assertJsonPath('data.uom_configured', true);

        $this->withHeaders($headers)
            ->getJson('/api/item/items/'.$itemId.'/inventory-summary')
            ->assertOk()
            ->assertJsonPath('data.is_stockable', true)
            ->assertJsonPath('data.quantity_on_hand', 0);

        $this->withHeaders($headers)
            ->getJson('/api/item/items/'.$itemId.'/uom-setup')
            ->assertOk()
            ->assertJsonPath('data.base_uom_id', $uomId);

        $this->withHeaders($headers)
            ->getJson('/api/item/items/'.$itemId.'/usage-summary')
            ->assertOk()
            ->assertJsonPath('data.purchasable', true)
            ->assertJsonPath('data.sellable', true);

        $this->withHeaders($headers)
            ->getJson('/api/item/items/'.$itemId.'/pricing-references')
            ->assertOk()
            ->assertJsonPath('data.count', 0);

        $this->withHeaders($headers)
            ->postJson('/api/item/items/preview-type-setup', [
                'base_uom_id' => $uomId,
                'is_stockable' => true,
                'is_purchasable' => true,
                'is_sellable' => true,
                'type' => 'inventory_product',
            ])
            ->assertOk()
            ->assertJsonPath('data.capabilities.stockable', true);

        $createdCategory = $this->withHeaders($headers)
            ->postJson('/api/item/item-categories', [
                'code' => 'TEST-CAT',
                'name' => 'Test Category',
                'is_active' => true,
            ])
            ->assertCreated()
            ->assertJsonPath('data.code', 'TEST-CAT')
            ->json('data.id');

        $createdAttribute = $this->withHeaders($headers)
            ->postJson('/api/item/item-attributes', [
                'name' => 'Test Attribute',
                'type' => 'TEXT',
                'is_required' => false,
            ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Test Attribute')
            ->json('data.id');

        $createdVariant = $this->withHeaders($headers)
            ->postJson('/api/item/item-variants', [
                'item_id' => $itemId,
                'sku' => 'ITM-TST-001-V1',
                'name' => 'Variant One',
                'is_active' => true,
            ])
            ->assertCreated()
            ->assertJsonPath('data.sku', 'ITM-TST-001-V1')
            ->json('data.id');

        $componentItemId = (int) DB::table('items')
            ->where('tenant_id', $tenantId)
            ->where('sku', 'ITM-FILTER-001')
            ->value('id');

        $this->withHeaders($headers)
            ->postJson('/api/item/combo-items', [
                'combo_item_id' => $itemId,
                'component_item_id' => $componentItemId,
                'quantity' => 1,
                'uom_id' => $hourUomId,
            ])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'The selected UOM is not configured for this component item.');

        $createdCombo = $this->withHeaders($headers)
            ->postJson('/api/item/combo-items', [
                'combo_item_id' => $itemId,
                'component_item_id' => $componentItemId,
                'quantity' => 1,
                'uom_id' => $uomId,
            ])
            ->assertCreated()
            ->assertJsonPath('data.combo_item_id', $itemId)
            ->json('data.id');

        $createdIdentifier = $this->withHeaders($headers)
            ->postJson('/api/item/item-identifiers', [
                'item_id' => $itemId,
                'technology' => 'barcode_1d',
                'format' => 'code128',
                'value' => 'ITM-TST-001-BARCODE',
                'is_active' => true,
            ])
            ->assertCreated()
            ->assertJsonPath('data.value', 'ITM-TST-001-BARCODE')
            ->json('data.id');

        foreach ([
            '/api/item/item-categories/'.$createdCategory,
            '/api/item/item-attributes/'.$createdAttribute,
            '/api/item/item-variants/'.$createdVariant,
            '/api/item/combo-items/'.$createdCombo,
            '/api/item/item-identifiers/'.$createdIdentifier,
        ] as $deleteUrl) {
            $this->withHeaders($headers)->deleteJson($deleteUrl)->assertNoContent();
        }

        $this->withHeaders($headers)
            ->putJson('/api/item/items/'.$itemId, [
                'base_uom_id' => $uomId,
                'category_id' => $categoryId,
                'item_type_id' => $itemTypeId,
                'name' => 'Test Stock Item Updated',
                'sku' => 'ITM-TST-001',
                'status' => 'ACTIVE',
                'type' => 'inventory_product',
                'is_stockable' => true,
                'is_purchasable' => true,
                'is_sellable' => true,
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Test Stock Item Updated');

        $this->withHeaders($headers)
            ->patchJson('/api/item/items/'.$itemId.'/deactivate')
            ->assertOk()
            ->assertJsonPath('data.is_active', false);

        $this->withHeaders($headers)
            ->patchJson('/api/item/items/'.$itemId.'/activate')
            ->assertOk()
            ->assertJsonPath('data.is_active', true);

        $this->withHeaders($headers)
            ->getJson('/api/item/item-categories')
            ->assertOk()
            ->assertJsonFragment(['code' => 'GENERAL']);

        $this->withHeaders($headers)
            ->getJson('/api/item/item-brands')
            ->assertOk()
            ->assertJsonFragment(['code' => 'GENERIC']);

        $this->withHeaders($headers)
            ->getJson('/api/item/item-attributes')
            ->assertOk()
            ->assertJsonFragment(['name' => 'Size']);

        $this->withHeaders($headers)
            ->getJson('/api/item/item-variants')
            ->assertOk()
            ->assertJsonFragment(['sku' => 'ITM-FILTER-001-STD']);

        $comboItemId = (int) DB::table('items')
            ->where('tenant_id', $tenantId)
            ->where('sku', 'ITM-BUNDLE-001')
            ->value('id');

        $this->withHeaders($headers)
            ->getJson('/api/item/combo-items?combo_item_id='.$comboItemId)
            ->assertOk()
            ->assertJsonPath('data.0.combo_item_id', $comboItemId);

        $this->withHeaders($headers)
            ->getJson('/api/item/item-identifiers')
            ->assertOk()
            ->assertJsonFragment(['value' => '890000000001']);
    }

    public function test_item_create_returns_validation_errors(): void
    {
        [, , $headers] = $this->authenticatedHeaders();

        $this->withHeaders($headers)
            ->postJson('/api/item/items', ['sku' => 'ITM-TST-VAL'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'base_uom_id']);
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
