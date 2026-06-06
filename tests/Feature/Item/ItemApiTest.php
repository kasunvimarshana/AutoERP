<?php

declare(strict_types=1);

namespace Tests\Feature\Item;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Core\Contracts\PasswordHasherInterface;
use Tests\TestCase;

final class ItemApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_item_create_update_lookup_and_readable_resource(): void
    {
        $context = $this->createAuthContext();
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
            ->assertJsonMissingPath('data.item_category_id')
            ->assertJsonMissingPath('data.item_brand_id')
            ->assertJsonMissingPath('data.base_uom_id');

        $itemId = (int) $response->json('data.id');
        $this->withAuth($context)->putJson('/api/v1/items/'.$itemId, ['name' => 'Updated Item'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Updated Item');

        $this->withAuth($context)->getJson('/api/v1/items/lookup?search=ITM-001')
            ->assertOk()
            ->assertJsonPath('data.0.code', 'ITM-001');
    }

    public function test_item_with_relations_is_created_transactionally(): void
    {
        $context = $this->createAuthContext();
        $uomId = $this->createUom($context, 'PCS');
        $childId = $this->createItem($context, $this->itemPayload(['code' => 'CHILD', 'name' => 'Child', 'base_uom_id' => $uomId]));

        $this->withAuth($context)->postJson('/api/v1/items/with-relations', [
            'item' => $this->itemPayload([
                'code' => 'KIT-001',
                'name' => 'Starter Kit',
                'item_type' => 'package',
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
            'prices' => [['price_type' => 'standard', 'amount' => '125.500000', 'uom_id' => $uomId]],
            'codes' => [['code_type' => 'internal_code', 'code' => 'KIT-CODE', 'is_primary' => true]],
            'usage_rules' => [['module_code' => 'inventory', 'is_enabled' => true]],
        ])
            ->assertCreated()
            ->assertJsonPath('data.units.0.uom.code', 'PCS')
            ->assertJsonPath('data.bundles.0.child_item.code', 'CHILD')
            ->assertJsonPath('data.bundles.0.quantity', '2.500000')
            ->assertJsonPath('data.prices.0.amount', '125.500000')
            ->assertJsonPath('data.codes.0.code', 'KIT-CODE')
            ->assertJsonPath('data.usage_rules.0.module_code', 'inventory');
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
        $itemId = $this->createItem($context, $this->itemPayload(['base_uom_id' => $uomId]));

        $unitId = (int) $this->withAuth($context)->postJson("/api/v1/items/{$itemId}/units", [
            'uom_id' => $uomId, 'unit_role' => 'base', 'conversion_factor' => '1', 'is_default' => true,
        ])->assertCreated()->assertJsonPath('data.uom.code', 'PCS')->json('data.id');
        $this->withAuth($context)->putJson("/api/v1/items/{$itemId}/units/{$unitId}", [
            'uom_id' => $uomId, 'unit_role' => 'base', 'conversion_factor' => '1.000000', 'is_default' => true,
        ])->assertOk()->assertJsonPath('data.conversion_factor', '1.000000');

        $variantId = (int) $this->withAuth($context)->postJson("/api/v1/items/{$itemId}/variants", [
            'code' => 'ITM-RED', 'name' => 'Red',
        ])->assertCreated()->json('data.id');
        $this->withAuth($context)->putJson("/api/v1/items/{$itemId}/variants/{$variantId}", [
            'code' => 'ITM-RED', 'name' => 'Red Updated',
        ])->assertOk()->assertJsonPath('data.name', 'Red Updated');

        $this->withAuth($context)->deleteJson("/api/v1/items/{$itemId}/variants/{$variantId}")->assertNoContent();
        $this->withAuth($context)->deleteJson("/api/v1/items/{$itemId}/units/{$unitId}")->assertNoContent();
    }

    public function test_bundle_crud_and_circular_bundle_prevention(): void
    {
        $context = $this->createAuthContext();
        $parentA = $this->createItem($context, $this->itemPayload(['code' => 'KIT-A', 'item_type' => 'package', 'is_stockable' => false, 'is_combo' => true]));
        $parentB = $this->createItem($context, $this->itemPayload(['code' => 'KIT-B', 'item_type' => 'package', 'is_stockable' => false, 'is_combo' => true]));

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
        $uomId = $this->createUom($context, 'PCS');
        $itemId = $this->createItem($context, $this->itemPayload(['base_uom_id' => $uomId]));

        $priceId = (int) $this->withAuth($context)->postJson("/api/v1/items/{$itemId}/prices", [
            'price_type' => 'standard', 'amount' => '10.500000', 'uom_id' => $uomId,
        ])->assertCreated()->assertJsonPath('data.uom.code', 'PCS')->json('data.id');
        $this->withAuth($context)->putJson("/api/v1/items/{$itemId}/prices/{$priceId}", [
            'price_type' => 'standard', 'amount' => '11.750000', 'uom_id' => $uomId,
        ])->assertOk()->assertJsonPath('data.amount', '11.750000');

        $codeId = (int) $this->withAuth($context)->postJson("/api/v1/items/{$itemId}/codes", [
            'code_type' => 'oem_code', 'code' => 'OEM-1',
        ])->assertCreated()->json('data.id');
        $this->withAuth($context)->putJson("/api/v1/items/{$itemId}/codes/{$codeId}", [
            'code_type' => 'oem_code', 'code' => 'OEM-2',
        ])->assertOk()->assertJsonPath('data.code', 'OEM-2');

        $ruleId = (int) $this->withAuth($context)->postJson("/api/v1/items/{$itemId}/usage-rules", [
            'module_code' => 'purchase', 'is_enabled' => true,
        ])->assertCreated()->json('data.id');
        $this->withAuth($context)->postJson("/api/v1/items/{$itemId}/usage-rules", [
            'module_code' => 'purchase', 'is_enabled' => true,
        ])->assertUnprocessable();
        $this->withAuth($context)->putJson("/api/v1/items/{$itemId}/usage-rules/{$ruleId}", [
            'module_code' => 'purchase', 'is_enabled' => false,
        ])->assertOk()->assertJsonPath('data.is_enabled', false);

        $this->withAuth($context)->deleteJson("/api/v1/items/{$itemId}/prices/{$priceId}")->assertNoContent();
        $this->withAuth($context)->deleteJson("/api/v1/items/{$itemId}/codes/{$codeId}")->assertNoContent();
        $this->withAuth($context)->deleteJson("/api/v1/items/{$itemId}/usage-rules/{$ruleId}")->assertNoContent();
    }

    public function test_category_and_brand_crud(): void
    {
        $context = $this->createAuthContext();
        $categoryId = (int) $this->withAuth($context)->postJson('/api/v1/item-categories', [
            'code' => 'LUB', 'name' => 'Lubricants', 'is_active' => true,
        ])->assertCreated()->json('data.id');
        $this->withAuth($context)->putJson('/api/v1/item-categories/'.$categoryId, [
            'code' => 'LUB', 'name' => 'Lubricants Updated', 'is_active' => true,
        ])->assertOk()->assertJsonPath('data.name', 'Lubricants Updated');
        $this->withAuth($context)->getJson('/api/v1/item-categories/lookup?search=LUB')
            ->assertOk()->assertJsonPath('data.0.code', 'LUB');

        $brandId = (int) $this->withAuth($context)->postJson('/api/v1/item-brands', [
            'code' => 'CAST', 'name' => 'Castrol', 'is_active' => true,
        ])->assertCreated()->json('data.id');
        $this->withAuth($context)->putJson('/api/v1/item-brands/'.$brandId, [
            'code' => 'CAST', 'name' => 'Castrol Updated', 'is_active' => true,
        ])->assertOk()->assertJsonPath('data.name', 'Castrol Updated');

        $this->withAuth($context)->deleteJson('/api/v1/item-categories/'.$categoryId)->assertNoContent();
        $this->withAuth($context)->deleteJson('/api/v1/item-brands/'.$brandId)->assertNoContent();
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
            'name' => 'Pieces',
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

    private function withAuth(array $context): self
    {
        return $this->withToken($context['token'])->withHeaders([
            'X-Tenant-Id' => (string) $context['tenant_id'],
            'X-Organization-Unit-Id' => (string) $context['organization_unit_id'],
        ]);
    }

    private function createAuthContext(array $overrides = []): array
    {
        $now = now();
        $code = strtoupper((string) ($overrides['code'] ?? 'ITEM'));
        $tenantId = (int) DB::table('tenants')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'code' => $code,
            'name' => $code.' Tenant',
            'slug' => strtolower($code).'-tenant',
            'status' => 'active',
            'is_active' => true,
            'is_isolated' => true,
            'row_version' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $organizationUnitId = (int) DB::table('organization_units')->insertGetId([
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
        $userId = (int) DB::table('users')->insertGetId([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'first_name' => 'Item',
            'last_name' => 'Tester',
            'email' => $email,
            'password' => app(PasswordHasherInterface::class)->hash('secret-password'),
            'status' => 'active',
            'row_version' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('user_tenants')->insert([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'user_id' => $userId,
            'is_default' => true,
            'row_version' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('auth_providers')->insert([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'provider_key' => 'internal',
            'name' => 'Internal password login',
            'guard_name' => 'auth-api',
            'provider_name' => 'users',
            'driver' => 'internal',
            'status' => 'active',
            'is_sso' => false,
            'row_version' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $token = (string) $this->postJson('/api/v1/auth/login', [
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'login_identifier' => $email,
            'password' => 'secret-password',
        ])->assertOk()->json('token');

        return [
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'token' => $token,
        ];
    }
}
