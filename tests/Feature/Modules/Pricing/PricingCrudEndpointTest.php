<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Pricing;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class PricingCrudEndpointTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_pricing_crud_and_resolver_endpoints_work_with_backend_context(): void
    {
        [$tenantId, $organizationUnitId, $headers] = $this->authenticatedHeaders();
        $currencyId = DB::table('currencies')->where('code', 'LKR')->value('id')
            ?? DB::table('currencies')->orderBy('id')->value('id');
        $itemId = (int) DB::table('items')->where('tenant_id', $tenantId)->orderBy('id')->value('id');
        $uomId = (int) DB::table('unit_of_measures')->where('tenant_id', $tenantId)->where('code', 'PCS')->value('id');

        $createList = $this->withHeaders($headers)->postJson('/api/pricing/price-lists', [
            'code' => 'PL-TST',
            'name' => 'Test Price List',
            'type' => 'sales',
            'scope_type' => 'generic',
            'currency_id' => $currencyId,
            'priority' => 10,
            'is_active' => true,
        ]);

        $createList
            ->assertCreated()
            ->assertJsonPath('data.code', 'PL-TST')
            ->assertJsonPath('data.tenant_id', $tenantId)
            ->assertJsonPath('data.organization_unit_id', $organizationUnitId);

        $priceListId = (int) $createList->json('data.id');

        $this->withHeaders($headers)
            ->putJson('/api/pricing/price-lists/'.$priceListId, [
                'name' => 'Test Price List Updated',
                'type' => 'sales',
                'scope_type' => 'generic',
                'currency_id' => $currencyId,
                'priority' => 20,
                'is_active' => true,
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Test Price List Updated');

        $this->withHeaders($headers)
            ->patchJson('/api/pricing/price-lists/'.$priceListId.'/deactivate')
            ->assertOk()
            ->assertJsonPath('data.is_active', false);

        $this->withHeaders($headers)
            ->patchJson('/api/pricing/price-lists/'.$priceListId.'/activate')
            ->assertOk()
            ->assertJsonPath('data.is_active', true);

        $createItem = $this->withHeaders($headers)->postJson('/api/pricing/price-list-items', [
            'price_list_id' => $priceListId,
            'item_id' => $itemId,
            'uom_id' => $uomId,
            'currency_id' => $currencyId,
            'min_quantity' => 1,
            'price' => '123.45',
            'is_active' => true,
        ]);

        $createItem
            ->assertCreated()
            ->assertJsonPath('data.price_list_id', $priceListId)
            ->assertJsonPath('data.item_id', $itemId);

        $priceListItemId = (int) $createItem->json('data.id');

        $this->withHeaders($headers)
            ->putJson('/api/pricing/price-list-items/'.$priceListItemId, [
                'price_list_id' => $priceListId,
                'item_id' => $itemId,
                'uom_id' => $uomId,
                'currency_id' => $currencyId,
                'min_quantity' => 1,
                'price' => '150.00',
                'is_active' => true,
            ])
            ->assertOk()
            ->assertJsonPath('data.price', '150.0000');

        $this->withHeaders($headers)
            ->postJson('/api/pricing/pricing-tiers', [
                'price_list_item_id' => $priceListItemId,
                'min_quantity' => 5,
                'max_quantity' => 10,
                'price' => '140.00',
                'is_active' => true,
            ])
            ->assertCreated()
            ->assertJsonPath('data.price_list_item_id', $priceListItemId);

        $createRule = $this->withHeaders($headers)->postJson('/api/pricing/pricing-rules', [
            'code' => 'RULE-TST',
            'name' => 'Test Rule',
            'applies_to_type' => 'price_resolve',
            'priority' => 10,
            'is_active' => true,
        ]);

        $createRule->assertCreated()->assertJsonPath('data.code', 'RULE-TST');
        $ruleId = (int) $createRule->json('data.id');

        $this->withHeaders($headers)
            ->getJson('/api/pricing/pricing-rules/'.$ruleId.'/usage')
            ->assertOk()
            ->assertJsonStructure(['data' => ['counts' => ['conditions', 'tiers', 'history_entries']]]);

        $this->withHeaders($headers)
            ->postJson('/api/pricing/discounts', [
                'code' => 'DISC-TST',
                'name' => 'Test Discount',
                'discount_type' => 'percentage',
                'discount_value' => 5,
                'is_active' => true,
            ])
            ->assertCreated()
            ->assertJsonPath('data.code', 'DISC-TST');

        $this->withHeaders($headers)
            ->postJson('/api/pricing/resolve-price', [
                'item_id' => $itemId,
                'uom_id' => $uomId,
                'currency_id' => $currencyId,
                'price_list_id' => $priceListId,
                'quantity' => 1,
                'source_type' => 'sales',
            ])
            ->assertOk()
            ->assertJsonPath('calculated.resolved_unit_price', 150);

        $this->withHeaders($headers)
            ->getJson('/api/pricing/price-lists/'.$priceListId.'/usage')
            ->assertOk()
            ->assertJsonPath('data.price_list_id', $priceListId)
            ->assertJsonStructure(['data' => ['counts' => ['price_list_items', 'customer_links', 'supplier_links', 'tiers', 'purchase_references', 'sales_references', 'service_references', 'rental_references', 'history_entries']]]);
    }

    public function test_pricing_validation_errors_are_returned(): void
    {
        [, , $headers] = $this->authenticatedHeaders();

        $this->withHeaders($headers)
            ->postJson('/api/pricing/price-lists', ['name' => ''])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
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
