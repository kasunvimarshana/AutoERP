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
        ]);

        $this->withHeaders($headers)
            ->getJson('/api/item/items?search=ITM-TST-001')
            ->assertOk()
            ->assertJsonPath('data.0.id', $itemId);

        $this->withHeaders($headers)
            ->getJson('/api/item/items/'.$itemId)
            ->assertOk()
            ->assertJsonPath('data.id', $itemId);

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
