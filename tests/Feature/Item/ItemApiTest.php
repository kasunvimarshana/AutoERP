<?php

declare(strict_types=1);

namespace Tests\Feature\Item;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Modules\Item\Models\Item;
use Modules\Item\Services\ItemAuthorizationService;
use Modules\Item\Services\ItemPriceResolutionService;
use Tests\TestCase;

final class ItemApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_item_create_update_lookup_and_readable_resource(): void
    {
        $context = $this->createAuthContext();
        $currencyId = $this->createCurrency('LKR');
        $uomId = $this->createUom($context, 'PCS');
        $categoryId = $this->createCategory($context, 'PARTS');
        $brandId = $this->createBrand($context, 'GEN');

        $response = $this->withAuth($context)->postJson('/api/v1/items', $this->itemPayload([
            'item_category_id' => $categoryId,
            'item_brand_id' => $brandId,
            'base_uom_id' => $uomId,
        ]))->assertCreated()
            ->assertJsonPath('data.category.code', 'PARTS')
            ->assertJsonPath('data.brand.code', 'GEN')
            ->assertJsonPath('data.base_uom.code', 'PCS')
            ->assertJsonMissingPath('data.standard_price')
            ->assertJsonMissingPath('data.item_category_id')
            ->assertJsonMissingPath('data.item_brand_id')
            ->assertJsonMissingPath('data.base_uom_id');

        $itemId = (int) $response->json('data.id');
        $this->withAuth($context)->putJson('/api/v1/items/'.$itemId, ['name' => 'Updated Item'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Updated Item');

        $this->withAuth($context)->putJson('/api/v1/items/'.$itemId, ['standard_price' => '12.340000'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['standard_price']);

        $this->withAuth($context)->getJson('/api/v1/items/lookup?search=ITM-001')
            ->assertOk()
            ->assertJsonPath('data.0.code', 'ITM-001');
    }

    public function test_item_price_revisions_are_effective_dated_immutable_and_conflict_aware(): void
    {
        $context = $this->createAuthContext(['code' => 'ITEM-PRICE', 'email' => 'item-price@example.test']);
        $currencyId = $this->createCurrency('LKR');
        DB::table('tenants')->where('id', $context['tenant_id'])->update(['base_currency_id' => $currencyId]);
        $otherCurrencyId = $this->createCurrency('USD');
        $pcsUomId = $this->createUom($context, 'PCS');
        $itemId = $this->createItem($context, $this->itemPayload(['base_uom_id' => $pcsUomId]));

        $created = $this->withAuth($context)->postJson("/api/v1/items/{$itemId}/prices", [
            'price_type' => 'sales',
            'amount' => '100.000000',
            'currency_id' => $currencyId,
            'uom_id' => $pcsUomId,
            'effective_from' => '2026-01-01',
        ])->assertCreated()
            ->assertJsonPath('data.revision_no', 1)
            ->assertJsonPath('data.row_version', 1)
            ->assertJsonPath('data.is_current_revision', true);

        $priceId = (int) $created->json('data.id');
        $lineageKey = (string) DB::table('item_prices')->where('id', $priceId)->value('lineage_key');
        $created->assertJsonMissingPath('data.scope_key')
            ->assertJsonMissingPath('data.lineage_key')
            ->assertJsonMissingPath('data.supersedes_price_id');

        $this->withAuth($context)->postJson("/api/v1/items/{$itemId}/prices", [
            'price_type' => 'sales',
            'amount' => '120.000000',
            'currency_id' => $currencyId,
            'uom_id' => $pcsUomId,
            'effective_from' => '2026-06-01',
        ])->assertUnprocessable()->assertJsonValidationErrors(['effective_from', 'effective_to']);

        $superseded = $this->withAuth($context)->postJson("/api/v1/items/{$itemId}/prices/{$priceId}/supersede", [
            'price_type' => 'sales',
            'amount' => '125.000000',
            'currency_id' => $currencyId,
            'uom_id' => $pcsUomId,
            'effective_from' => '2026-01-01',
            'expected_version' => 1,
            'correction_reason' => 'Approved annual price correction.',
        ])->assertCreated()
            ->assertJsonPath('data.revision_no', 2)
            ->assertJsonPath('data.amount', '125.000000')
            ->assertJsonMissingPath('data.scope_key')
            ->assertJsonMissingPath('data.lineage_key')
            ->assertJsonMissingPath('data.supersedes_price_id');

        $newPriceId = (int) $superseded->json('data.id');
        $this->assertNotNull(DB::table('item_prices')->where('id', $priceId)->value('recorded_to'));
        $this->assertDatabaseHas('item_prices', [
            'id' => $newPriceId,
            'recorded_to' => null,
            'revision_no' => 2,
            'lineage_key' => $lineageKey,
            'supersedes_price_id' => $priceId,
        ]);

        $this->withAuth($context)->postJson("/api/v1/items/{$itemId}/prices/{$newPriceId}/supersede", [
            'price_type' => 'sales',
            'amount' => '130.000000',
            'currency_id' => $currencyId,
            'uom_id' => $pcsUomId,
            'effective_from' => '2026-01-01',
            'expected_version' => 99,
            'correction_reason' => 'Stale correction attempt.',
        ])->assertUnprocessable()->assertJsonValidationErrors(['expected_version']);

        [$resolved, $missingCurrency] = $this->withTenantExecutionContext(
            $context['tenant_id'],
            function () use ($itemId, $pcsUomId, $context, $currencyId, $otherCurrencyId): array {
                $item = Item::query()->findOrFail($itemId);
                $resolver = app(ItemPriceResolutionService::class);

                return [
                    $resolver->resolvePrice(
                        item: $item,
                        context: ItemPriceResolutionService::CONTEXT_SALES,
                        uomId: $pcsUomId,
                        organizationUnitId: $context['organization_unit_id'],
                        currencyId: $currencyId,
                        date: '2026-06-18',
                    ),
                    $resolver->resolvePrice(
                        item: $item,
                        context: ItemPriceResolutionService::CONTEXT_SALES,
                        uomId: $pcsUomId,
                        organizationUnitId: $context['organization_unit_id'],
                        currencyId: $otherCurrencyId,
                        date: '2026-06-18',
                    ),
                ];
            },
        );
        $this->assertSame('item_price_revision', $resolved->source);
        $this->assertSame('125.000000', $resolved->amount);
        $this->assertSame(2, $resolved->metadata['revision_no']);

        $this->assertSame('manual', $missingCurrency->source);
        $this->assertNull($missingCurrency->amount);

        $this->withAuth($context)->deleteJson("/api/v1/items/{$itemId}/prices/{$newPriceId}")->assertNotFound();
    }

    public function test_item_with_relations_is_created_transactionally(): void
    {
        $context = $this->createAuthContext();
        $currencyId = $this->createCurrency('LKR');
        $uomId = $this->createUom($context, 'PCS');
        $childId = $this->createItem($context, $this->itemPayload(['code' => 'CHILD', 'name' => 'Child', 'base_uom_id' => $uomId]));

        $this->withAuth($context)->postJson('/api/v1/items/with-relations', [
            'item' => $this->itemPayload([
                'code' => 'KIT-001',
                'name' => 'Starter Kit',
                'item_type' => 'package',
                'tracking_type' => 'none',
                'costing_method' => 'none',
                'is_stockable' => false,
                'is_combo' => true,
                'base_uom_id' => $uomId,
            ]),
            'units' => [[
                'uom_id' => $uomId,
                'unit_role' => 'base',
                'conversion_factor' => '1.000000',
                'is_default' => true,
            ]],
            'variants' => [['code' => 'KIT-RED', 'name' => 'Red Kit']],
            'bundles' => [[
                'child_item_id' => $childId,
                'quantity' => '2.500000',
                'uom_id' => $uomId,
                'line_type' => 'stock',
            ]],
            'prices' => [[
                'price_type' => 'sales',
                'amount' => '125.500000',
                'currency_id' => $currencyId,
                'uom_id' => $uomId,
                'effective_from' => '2026-01-01',
            ]],
            'codes' => [['code_type' => 'internal_code', 'code' => 'KIT-CODE', 'is_primary' => true]],
            'usage_rules' => [['module_code' => 'invoice', 'is_enabled' => true]],
        ])
            ->assertCreated()
            ->assertJsonPath('data.units.0.uom.code', 'PCS')
            ->assertJsonPath('data.bundles.0.child_item.code', 'CHILD')
            ->assertJsonPath('data.bundles.0.quantity', '2.500000')
            ->assertJsonPath('data.prices.0.amount', '125.500000')
            ->assertJsonPath('data.codes.0.code', 'KIT-CODE')
            ->assertJsonPath('data.usage_rules.0.module_code', 'invoice');
    }

    public function test_one_shot_relation_failure_rolls_back_item(): void
    {
        $context = $this->createAuthContext();
        $uomId = $this->createUom($context, 'OLD');
        DB::table('unit_of_measures')->where('id', $uomId)->update(['is_active' => false]);

        $this->withAuth($context)->postJson('/api/v1/items/with-relations', [
            'item' => $this->itemPayload(['code' => 'ROLLBACK', 'name' => 'Rollback Item']),
            'units' => [[
                'uom_id' => $uomId,
                'unit_role' => 'purchase',
                'conversion_factor' => '1.000000',
            ]],
            'variants' => [],
            'bundles' => [],
            'prices' => [],
            'codes' => [],
            'usage_rules' => [],
        ])->assertUnprocessable();

        $this->assertDatabaseMissing('items', [
            'tenant_id' => $context['tenant_id'],
            'code' => 'ROLLBACK',
        ]);
    }

    public function test_unit_and_variant_relation_crud(): void
    {
        $context = $this->createAuthContext();
        $uomId = $this->createUom($context, 'PCS');
        $boxUomId = $this->createUom($context, 'BOX');
        $itemId = $this->createItem($context, $this->itemPayload(['base_uom_id' => $uomId]));

        $unitId = (int) $this->withAuth($context)->postJson("/api/v1/items/{$itemId}/units", [
            'uom_id' => $boxUomId, 'unit_role' => 'purchase', 'conversion_factor' => '10', 'is_default' => true,
        ])->assertCreated()->assertJsonPath('data.uom.code', 'BOX')->json('data.id');
        $this->withAuth($context)->putJson("/api/v1/items/{$itemId}/units/{$unitId}", [
            'uom_id' => $boxUomId, 'unit_role' => 'purchase', 'conversion_factor' => '10.000000', 'is_default' => true,
        ])->assertOk()->assertJsonPath('data.conversion_factor', '10.000000');

        $variantId = (int) $this->withAuth($context)->postJson("/api/v1/items/{$itemId}/variants", [
            'code' => 'ITM-RED', 'name' => 'Red',
        ])->assertCreated()->json('data.id');
        $this->withAuth($context)->putJson("/api/v1/items/{$itemId}/variants/{$variantId}", [
            'code' => 'ITM-RED', 'name' => 'Red Updated',
        ])->assertOk()->assertJsonPath('data.name', 'Red Updated');

        $this->withAuth($context)->deleteJson("/api/v1/items/{$itemId}/variants/{$variantId}")->assertNoContent();
        $this->withAuth($context)->deleteJson("/api/v1/items/{$itemId}/units/{$unitId}")->assertNoContent();
    }

    public function test_default_unit_is_global_active_and_base_row_is_protected(): void
    {
        $context = $this->createAuthContext();
        $uomId = $this->createUom($context, 'PCS');
        $boxUomId = $this->createUom($context, 'BOX');
        $itemId = $this->createItem($context, $this->itemPayload(['base_uom_id' => $uomId]));

        $baseUnitId = (int) DB::table('item_units')
            ->where('item_id', $itemId)
            ->where('unit_role', 'base')
            ->value('id');

        $purchaseId = (int) $this->withAuth($context)->postJson("/api/v1/items/{$itemId}/units", [
            'uom_id' => $boxUomId,
            'unit_role' => 'purchase',
            'conversion_factor' => '10.000000',
            'is_default' => true,
        ])->assertCreated()->json('data.id');

        $salesId = (int) $this->withAuth($context)->postJson("/api/v1/items/{$itemId}/units", [
            'uom_id' => $boxUomId,
            'unit_role' => 'sales',
            'conversion_factor' => '10.000000',
            'is_default' => true,
        ])->assertCreated()->json('data.id');

        $this->assertSame(1, DB::table('item_units')->where('item_id', $itemId)->where('is_default', true)->count());
        $this->assertDatabaseHas('item_units', ['id' => $purchaseId, 'is_default' => false]);
        $this->assertDatabaseHas('item_units', ['id' => $salesId, 'is_default' => true]);

        $this->withAuth($context)->postJson("/api/v1/items/{$itemId}/units", [
            'uom_id' => $uomId,
            'unit_role' => 'service',
            'conversion_factor' => '1.000000',
            'is_default' => true,
            'is_active' => false,
        ])->assertUnprocessable()->assertJsonValidationErrors(['is_default']);

        $this->withAuth($context)->deleteJson("/api/v1/items/{$itemId}/units/{$baseUnitId}")
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['unit_role']);
    }

    public function test_base_uom_change_preserves_existing_valid_default_unit_and_rejects_conflicting_factors(): void
    {
        $context = $this->createAuthContext();
        $pcsUomId = $this->createUom($context, 'PCS');
        $boxUomId = $this->createUom($context, 'BOX');
        $itemId = $this->createItem($context, $this->itemPayload(['base_uom_id' => $pcsUomId]));

        $defaultUnitId = (int) $this->withAuth($context)->postJson("/api/v1/items/{$itemId}/units", [
            'uom_id' => $boxUomId,
            'unit_role' => 'purchase',
            'conversion_factor' => '10.000000',
            'is_default' => true,
        ])->assertCreated()->json('data.id');

        $this->withAuth($context)->postJson("/api/v1/items/{$itemId}/units", [
            'uom_id' => $boxUomId,
            'unit_role' => 'sales',
            'conversion_factor' => '12.000000',
        ])->assertUnprocessable()->assertJsonValidationErrors(['conversion_factor']);

        $this->withAuth($context)->putJson("/api/v1/items/{$itemId}", ['base_uom_id' => $boxUomId])
            ->assertOk()
            ->assertJsonPath('data.base_uom.code', 'BOX');

        $this->assertSame(1, DB::table('item_units')->where('item_id', $itemId)->where('is_default', true)->count());
        $this->assertDatabaseHas('item_units', ['id' => $defaultUnitId, 'is_default' => true, 'unit_role' => 'purchase']);
        $this->assertDatabaseHas('item_units', [
            'item_id' => $itemId,
            'unit_role' => 'base',
            'uom_id' => $boxUomId,
            'conversion_factor' => '1.000000',
            'is_active' => true,
        ]);
    }

    public function test_bundle_crud_and_circular_bundle_prevention(): void
    {
        $context = $this->createAuthContext();
        $parentA = $this->createItem($context, $this->itemPayload(['code' => 'KIT-A', 'item_type' => 'package', 'tracking_type' => 'none', 'costing_method' => 'none', 'is_stockable' => false, 'is_combo' => true]));
        $parentB = $this->createItem($context, $this->itemPayload(['code' => 'KIT-B', 'item_type' => 'package', 'tracking_type' => 'none', 'costing_method' => 'none', 'is_stockable' => false, 'is_combo' => true]));

        $bundleId = (int) $this->withAuth($context)->postJson("/api/v1/items/{$parentA}/bundles", [
            'child_item_id' => $parentB, 'quantity' => '1.000000', 'line_type' => 'non_stock',
        ])->assertCreated()->assertJsonPath('data.child_item.code', 'KIT-B')->json('data.id');

        $this->withAuth($context)->postJson("/api/v1/items/{$parentB}/bundles", [
            'child_item_id' => $parentA, 'quantity' => '1.000000', 'line_type' => 'non_stock',
        ])->assertUnprocessable()->assertJsonPath('success', false);

        $this->withAuth($context)->putJson("/api/v1/items/{$parentA}/bundles/{$bundleId}", [
            'child_item_id' => $parentB, 'quantity' => '2.000000', 'line_type' => 'non_stock',
        ])->assertOk()->assertJsonPath('data.quantity', '2.000000');
        $this->withAuth($context)->deleteJson("/api/v1/items/{$parentA}/bundles/{$bundleId}")->assertNoContent();
    }

    public function test_price_code_and_usage_rule_crud(): void
    {
        $context = $this->createAuthContext();
        $currencyId = $this->createCurrency('LKR');
        $uomId = $this->createUom($context, 'PCS');
        $foreignUomId = $this->createUom($context, 'BOX');
        $itemId = $this->createItem($context, $this->itemPayload(['base_uom_id' => $uomId]));

        $priceId = (int) $this->withAuth($context)->postJson("/api/v1/items/{$itemId}/prices", [
            'price_type' => 'sales', 'amount' => '10.500000', 'currency_id' => $currencyId,
            'uom_id' => $uomId, 'effective_from' => '2026-01-01',
        ])->assertCreated()->assertJsonPath('data.uom.code', 'PCS')->json('data.id');
        $this->withAuth($context)->postJson("/api/v1/items/{$itemId}/prices", [
            'price_type' => 'sales', 'amount' => '12.000000', 'currency_id' => $currencyId,
            'uom_id' => $foreignUomId, 'effective_from' => '2027-01-01',
        ])->assertUnprocessable()->assertJsonValidationErrors(['uom_id']);
        $this->withAuth($context)->postJson("/api/v1/items/{$itemId}/prices", [
            'price_type' => 'standard', 'amount' => '12.000000', 'currency_id' => $currencyId,
            'uom_id' => $uomId, 'effective_from' => '2027-01-01',
        ])->assertUnprocessable()->assertJsonValidationErrors(['price_type']);
        $this->withAuth($context)->postJson("/api/v1/items/{$itemId}/prices", [
            'price_type' => 'cost', 'amount' => '12.000000', 'currency_id' => $currencyId,
            'uom_id' => $uomId, 'effective_from' => '2027-01-01',
        ])->assertUnprocessable()->assertJsonValidationErrors(['price_type']);

        $codeId = (int) $this->withAuth($context)->postJson("/api/v1/items/{$itemId}/codes", [
            'code_type' => 'oem_code', 'code' => 'OEM-1', 'is_primary' => true,
        ])->assertCreated()->json('data.id');
        $secondCodeId = (int) $this->withAuth($context)->postJson("/api/v1/items/{$itemId}/codes", [
            'code_type' => 'internal_code', 'code' => 'INT-PRIMARY', 'is_primary' => true,
        ])->assertCreated()->json('data.id');
        $this->assertSame(1, DB::table('item_codes')->where('item_id', $itemId)->where('is_primary', true)->count());
        $this->assertDatabaseHas('item_codes', ['id' => $codeId, 'is_primary' => false]);
        $this->assertDatabaseHas('item_codes', ['id' => $secondCodeId, 'is_primary' => true]);
        $this->withAuth($context)->putJson("/api/v1/items/{$itemId}/codes/{$codeId}", [
            'code_type' => 'oem_code', 'code' => 'OEM-2',
        ])->assertOk()->assertJsonPath('data.code', 'OEM-2');

        $ruleId = (int) $this->withAuth($context)->postJson("/api/v1/items/{$itemId}/usage-rules", [
            'module_code' => 'purchase', 'is_enabled' => true,
        ])->assertCreated()->json('data.id');
        $this->withAuth($context)->postJson("/api/v1/items/{$itemId}/usage-rules", [
            'module_code' => 'purchase', 'is_enabled' => true,
        ])->assertUnprocessable();
        $this->withAuth($context)->postJson("/api/v1/items/{$itemId}/usage-rules", [
            'module_code' => 'not_a_real_module', 'is_enabled' => true,
        ])->assertUnprocessable()->assertJsonValidationErrors(['module_code']);
        $this->withAuth($context)->putJson("/api/v1/items/{$itemId}/usage-rules/{$ruleId}", [
            'module_code' => 'purchase', 'is_enabled' => false,
        ])->assertOk()->assertJsonPath('data.is_enabled', false);

        $this->withAuth($context)->deleteJson("/api/v1/items/{$itemId}/prices/{$priceId}")->assertNotFound();
        $this->withAuth($context)->deleteJson("/api/v1/items/{$itemId}/codes/{$codeId}")->assertNoContent();
        $this->withAuth($context)->deleteJson("/api/v1/items/{$itemId}/codes/{$secondCodeId}")->assertNoContent();
        $this->withAuth($context)->deleteJson("/api/v1/items/{$itemId}/usage-rules/{$ruleId}")->assertNoContent();
    }

    public function test_category_and_brand_crud(): void
    {
        $context = $this->createAuthContext();
        $categoryId = (int) $this->withAuth($context)->postJson('/api/v1/item-categories', [
            'code' => 'LUB', 'name' => 'Lubricants', 'is_active' => true,
        ])->assertCreated()->json('data.id');
        $childCategoryId = (int) $this->withAuth($context)->postJson('/api/v1/item-categories', [
            'code' => 'LUB-CHILD', 'name' => 'Child Lubricants', 'parent_id' => $categoryId, 'is_active' => true,
        ])->assertCreated()->json('data.id');
        $this->withAuth($context)->putJson('/api/v1/item-categories/'.$categoryId, [
            'code' => 'LUB', 'name' => 'Lubricants', 'parent_id' => $childCategoryId, 'is_active' => true,
        ])->assertUnprocessable();
        $this->withAuth($context)->putJson('/api/v1/item-categories/'.$categoryId, [
            'code' => 'LUB', 'name' => 'Lubricants Updated', 'is_active' => true,
        ])->assertOk()->assertJsonPath('data.name', 'Lubricants Updated');
        $this->withAuth($context)->getJson('/api/v1/item-categories/lookup?search=LUB')
            ->assertOk()->assertJsonFragment(['code' => 'LUB']);

        $brandId = (int) $this->withAuth($context)->postJson('/api/v1/item-brands', [
            'code' => 'CAST', 'name' => 'Castrol', 'is_active' => true,
        ])->assertCreated()->json('data.id');
        $this->withAuth($context)->putJson('/api/v1/item-brands/'.$brandId, [
            'code' => 'CAST', 'name' => 'Castrol Updated', 'is_active' => true,
        ])->assertOk()->assertJsonPath('data.name', 'Castrol Updated');

        $this->withAuth($context)->deleteJson('/api/v1/item-categories/'.$categoryId)->assertUnprocessable();
        $this->withAuth($context)->deleteJson('/api/v1/item-categories/'.$childCategoryId)->assertNoContent();
        $this->withAuth($context)->deleteJson('/api/v1/item-categories/'.$categoryId)->assertNoContent();
        $this->withAuth($context)->deleteJson('/api/v1/item-brands/'.$brandId)->assertNoContent();
    }

    public function test_exact_item_permissions_are_enforced(): void
    {
        $viewer = $this->createAuthContext(
            ['code' => 'ITEM-VIEW', 'email' => 'item-viewer@example.test'],
            [ItemAuthorizationService::VIEW],
        );

        $this->withAuth($viewer)->getJson('/api/v1/items')->assertOk();
        $this->withAuth($viewer)->postJson('/api/v1/items', $this->itemPayload())
            ->assertForbidden();
    }

    public function test_protected_organization_field_is_rejected_on_update(): void
    {
        $admin = $this->createAuthContext(['code' => 'ITEM-ORG', 'email' => 'item-org@example.test']);
        $otherOrgId = $this->createOrganizationUnit($admin['tenant_id'], 'Other', 'OTHER');
        $itemId = $this->createItem($admin, $this->itemPayload());

        $this->withAuth($admin)->putJson('/api/v1/items/'.$itemId, [
            'name' => 'Still Same Org',
            'organization_unit_id' => $otherOrgId,
        ])->assertUnprocessable();

        $this->assertDatabaseHas('items', [
            'id' => $itemId,
            'organization_unit_id' => $admin['organization_unit_id'],
            'name' => 'Test Item',
        ]);
    }

    public function test_tenant_isolation_and_validation_error_format(): void
    {
        $tenantA = $this->createAuthContext(['code' => 'ITEM-A', 'email' => 'a-item@example.test']);
        $tenantB = $this->createAuthContext(['code' => 'ITEM-B', 'email' => 'b-item@example.test']);
        $itemId = $this->createItem($tenantA, $this->itemPayload());

        $this->withAuth($tenantB)->getJson('/api/v1/items/'.$itemId)->assertForbidden();
        $this->withAuth($tenantA)->postJson('/api/v1/items', [])
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Validation failed.')
            ->assertJsonStructure(['errors' => ['code', 'name', 'item_type']]);
    }

    private function itemPayload(array $overrides = []): array
    {
        return [
            'code' => 'ITM-001',
            'name' => 'Test Item',
            'item_type' => 'stock',
            'tracking_type' => 'none',
            'costing_method' => 'fifo',
            'is_stockable' => true,
            'is_combo' => false,
            'is_active' => true,
            ...$overrides,
        ];
    }

    private function createItem(array $context, array $payload): int
    {
        return (int) $this->withAuth($context)->postJson('/api/v1/items', $payload)->assertCreated()->json('data.id');
    }

    private function createCategory(array $context, string $code): int
    {
        return (int) DB::table('item_categories')->insertGetId([
            'tenant_id' => $context['tenant_id'],
            'organization_unit_id' => $context['organization_unit_id'],
            'code' => $code,
            'name' => 'Category '.$code,
            'is_active' => true,
            'sort_order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createBrand(array $context, string $code): int
    {
        return (int) DB::table('item_brands')->insertGetId([
            'tenant_id' => $context['tenant_id'],
            'organization_unit_id' => $context['organization_unit_id'],
            'code' => $code,
            'name' => 'Brand '.$code,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createUom(array $context, string $code): int
    {
        return (int) DB::table('unit_of_measures')->insertGetId([
            'tenant_id' => $context['tenant_id'],
            'organization_unit_id' => $context['organization_unit_id'],
            'code' => $code,
            'name' => $code.' Pieces',
            'symbol' => strtolower($code),
            'type' => 'unit',
            'category' => 'quantity',
            'decimal_precision' => 6,
            'allow_fractional_quantity' => true,
            'is_base' => true,
            'is_active' => true,
            'row_version' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createCurrency(string $code): int
    {
        return (int) DB::table('currencies')->insertGetId([
            'code' => $code.'-'.Str::upper(Str::random(4)),
            'name' => $code.' Currency',
            'symbol' => $code,
            'decimal_places' => 2,
            'is_active' => true,
            'row_version' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createOrganizationUnit(int $tenantId, string $name, string $code): int
    {
        return (int) \Tests\Support\OrganizationUnitFixture::create([
            'tenant_id' => $tenantId,
            'name' => $name,
            'code' => $code,
            'path' => '/'.strtolower($code),
            'is_active' => true,
            'row_version' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function withAuth(array $context): self
    {
        return $this->withToken($context['token'])->withHeaders([
            'X-Tenant-Id' => (string) $context['tenant_id'],
        ]);
    }

    private function createAuthContext(array $overrides = [], ?array $permissions = null): array
    {
        $now = now();
        $code = strtoupper((string) ($overrides['code'] ?? 'ITEM'));
        $tenantId = (int) DB::table('tenants')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'code' => $code,
            'name' => $code.' Tenant',
            'slug' => strtolower($code).'-tenant',
            'status' => 'active',
            'row_version' => 1,
            'status_reason' => 'Integration test tenant.',
            'status_changed_at' => $now,
            'activated_at' => $now,
            'created_at' => $now,
            'updated_at' => $now]);
        \Tests\Support\ActiveTenantSubscriptionFixture::create($tenantId);
        $organizationUnitId = (int) \Tests\Support\OrganizationUnitFixture::create([
            'tenant_id' => $tenantId,
            'name' => 'Main',
            'code' => 'MAIN',
            'path' => '/main',
            'is_active' => true,
            'row_version' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $email = (string) ($overrides['email'] ?? 'item-admin@example.test');
        $userId = (int) \Tests\Support\TenantUserFixture::create([
            'tenant_id' => $tenantId,
            'first_name' => 'Item',
            'last_name' => 'Tester',
            'email' => $email,
            'password' => 'secret-password',
            'status' => 'active',
            'row_version' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('user_organization_units')->insert([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'user_id' => $userId,
            'status' => 'active',
            'is_default' => true,
            'default_marker' => 'default',
            'row_version' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $roleId = (int) DB::table('roles')->insertGetId([
            'tenant_id' => $tenantId,
            'name' => 'Item Test Role',
            'guard_name' => 'auth-api',
            'description' => 'Item test role',
            'row_version' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        foreach ($this->seedItemPermissions($tenantId, $permissions ?? array_keys(ItemAuthorizationService::descriptions())) as $permissionId) {
            DB::table('role_permissions')->insert([
                'tenant_id' => $tenantId,
                'role_id' => $roleId,
                'permission_id' => $permissionId,
                'row_version' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
        DB::table('user_roles')->insert([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'role_id' => $roleId,
            'row_version' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        \Tests\Support\TenantAuthenticationFixture::provision($tenantId, $userId, $email);

        $token = (string) $this->withHeader('X-Tenant-Id', (string) $tenantId)->postJson('/api/v1/auth/login', [
            'organization_unit_id' => $organizationUnitId,
            'identifier' => $email,
            'password' => 'secret-password',
        ])->assertOk()->json('token');

        return [
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'token' => $token,
        ];
    }

    private function seedItemPermissions(int $tenantId, array $names): array
    {
        $ids = [];
        foreach ($names as $name) {
            $ids[] = (int) DB::table('permissions')->insertGetId([
                'tenant_id' => $tenantId,
                'name' => $name,
                'guard_name' => 'auth-api',
                'module' => 'Item',
                'description' => ItemAuthorizationService::descriptions()[$name] ?? 'Item test permission',
                'row_version' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $ids;
    }
}
