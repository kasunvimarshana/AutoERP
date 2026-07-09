<?php

declare(strict_types=1);

namespace Tests\Feature\Supplier;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Item\Services\ItemAuthorizationService;
use Modules\Supplier\Services\SupplierAuthorizationService;
use Modules\User\Constants\UserGuard;
use Tests\TestCase;

final class SupplierApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_supplier_create_update_lookup_and_readable_resource(): void
    {
        $context = $this->createAuthContext();

        $response = $this->withAuth($context)->postJson('/api/v1/suppliers', $this->supplierPayload())
            ->assertCreated()
            ->assertJsonPath('data.code', 'SUP-001')
            ->assertJsonPath('data.status', 'active')
            ->assertJsonPath('data.row_version', 1);

        $supplierId = (int) $response->json('data.id');
        $rowVersion = (int) $response->json('data.row_version');
        $this->withAuth($context)->putJson('/api/v1/suppliers/'.$supplierId, [
            'row_version' => $rowVersion,
            'name' => 'Updated Supplier',
            'phone' => '+94 11 555 1111',
        ])->assertOk()
            ->assertJsonPath('data.name', 'Updated Supplier')
            ->assertJsonPath('data.phone', '+94 11 555 1111')
            ->assertJsonPath('data.row_version', $rowVersion + 1);

        $this->withAuth($context)->putJson('/api/v1/suppliers/'.$supplierId, [
            'row_version' => $rowVersion,
            'name' => 'Stale Supplier',
        ])->assertStatus(409)
            ->assertJsonPath('message', 'Supplier was changed by someone else. Reload before saving.');

        $this->withAuth($context)->getJson('/api/v1/suppliers/lookup?search=SUP-001')
            ->assertOk()
            ->assertJsonPath('data.0.code', 'SUP-001');
    }

    public function test_supplier_with_relations_is_created_transactionally_and_readably(): void
    {
        $context = $this->createAuthContext();
        $currencyId = $this->createCurrency('LKR');
        $uomId = $this->createUom($context, 'PCS');
        $itemId = $this->createItem($context, $uomId, 'ITM-SUP');
        $categoryId = $this->createSupplierCategory($context, 'LOCAL');

        $response = $this->withAuth($context)->postJson('/api/v1/suppliers/with-relations', [
            'supplier' => $this->supplierPayload([
                'code' => 'SUP-TREE',
                'name' => 'Supplier Tree',
                'default_currency_id' => $currencyId,
                'credit_limit' => '5000.000000',
            ]),
            'contacts' => [[
                'contact_name' => 'Jane Buyer',
                'email' => 'jane@example.test',
                'is_primary' => true,
            ]],
            'addresses' => [[
                'address_type' => 'billing',
                'address_line_1' => '10 Main Street',
                'city' => 'Colombo',
                'is_primary' => true,
            ]],
            'bank_accounts' => [[
                'bank_name' => 'Test Bank',
                'account_name' => 'Supplier Tree',
                'account_number' => '001234',
                'currency_id' => $currencyId,
                'is_primary' => true,
            ]],
            'categories' => [$categoryId],
            'documents' => [[
                'document_type' => 'business_registration',
                'document_number' => 'BR-100',
                'status' => 'active',
            ]],
            'item_mappings' => [[
                'item_id' => $itemId,
                'default_purchase_uom_id' => $uomId,
                'minimum_order_quantity' => '5.500000',
                'is_preferred' => true,
            ]],
            'credit_profile' => [
                'credit_limit' => '5000.000000',
                'credit_period_days' => 30,
                'warning_threshold_percent' => '75.000000',
            ],
        ])->assertCreated()
            ->assertJsonPath('data.default_currency.code', 'LKR')
            ->assertJsonPath('data.contacts.0.contact_name', 'Jane Buyer')
            ->assertJsonPath('data.bank_accounts.0.currency.code', 'LKR')
            ->assertJsonPath('data.categories.0.code', 'LOCAL')
            ->assertJsonPath('data.item_mappings.0.item.code', 'ITM-SUP')
            ->assertJsonPath('data.item_mappings.0.default_purchase_uom.code', 'PCS')
            ->assertJsonPath('data.item_mappings.0.minimum_order_quantity', '5.500000')
            ->assertJsonPath('data.credit_profile.credit_limit', '5000.000000')
            ->assertJsonMissingPath('data.item_mappings.0.item_id')
            ->assertJsonMissingPath('data.item_mappings.0.default_purchase_uom_id');

        $supplierId = (int) $response->json('data.id');
        $this->withAuth($context)->getJson("/api/v1/suppliers/lookup/by-item?item_id={$itemId}")
            ->assertOk()
            ->assertJsonPath('data.0.id', $supplierId);
        $this->withAuth($context)->getJson('/api/v1/suppliers/lookup/credit-allowed?search=SUP-TREE')
            ->assertOk()
            ->assertJsonPath('data.0.id', $supplierId);
        $this->withAuth($context)->getJson("/api/v1/suppliers?category_id={$categoryId}")
            ->assertOk()
            ->assertJsonPath('data.0.id', $supplierId)
            ->assertJsonPath('data.0.categories.0.code', 'LOCAL');
    }

    public function test_one_shot_relation_failure_rolls_back_supplier_tree(): void
    {
        $context = $this->createAuthContext();

        $this->withAuth($context)->postJson('/api/v1/suppliers/with-relations', [
            'supplier' => $this->supplierPayload(['code' => 'ROLLBACK', 'name' => 'Rollback Supplier']),
            'contacts' => [
                ['contact_name' => 'Primary One', 'is_primary' => true],
                ['contact_name' => 'Primary Two', 'is_primary' => true],
            ],
            'addresses' => [],
            'bank_accounts' => [],
            'categories' => [],
            'documents' => [],
            'item_mappings' => [],
        ])->assertUnprocessable()->assertJsonPath('success', false);

        $this->assertDatabaseMissing('suppliers', [
            'tenant_id' => $context['tenant_id'],
            'code' => 'ROLLBACK',
        ]);
    }

    public function test_contact_address_and_bank_account_relation_crud(): void
    {
        $context = $this->createAuthContext();
        $currencyId = $this->createCurrency('USD');
        $supplierId = $this->createSupplier($context);

        $contactId = (int) $this->withAuth($context)->postJson("/api/v1/suppliers/{$supplierId}/contacts", [
            'contact_name' => 'Contact One', 'is_primary' => true,
        ])->assertCreated()->json('data.id');
        $this->withAuth($context)->putJson("/api/v1/suppliers/{$supplierId}/contacts/{$contactId}", [
            'contact_name' => 'Contact Updated', 'is_primary' => true, 'is_active' => true,
        ])->assertOk()->assertJsonPath('data.contact_name', 'Contact Updated');

        $addressId = (int) $this->withAuth($context)->postJson("/api/v1/suppliers/{$supplierId}/addresses", [
            'address_type' => 'billing', 'address_line_1' => 'Main Road', 'is_primary' => true,
        ])->assertCreated()->json('data.id');
        $this->withAuth($context)->putJson("/api/v1/suppliers/{$supplierId}/addresses/{$addressId}", [
            'address_type' => 'billing', 'address_line_1' => 'Updated Road', 'is_primary' => true,
        ])->assertOk()->assertJsonPath('data.address_line_1', 'Updated Road');

        $bankId = (int) $this->withAuth($context)->postJson("/api/v1/suppliers/{$supplierId}/bank-accounts", [
            'bank_name' => 'Bank', 'account_name' => 'Supplier', 'account_number' => '100', 'currency_id' => $currencyId,
        ])->assertCreated()->assertJsonPath('data.currency.code', 'USD')->json('data.id');
        $this->withAuth($context)->putJson("/api/v1/suppliers/{$supplierId}/bank-accounts/{$bankId}", [
            'bank_name' => 'Bank', 'account_name' => 'Supplier', 'account_number' => '101', 'currency_id' => $currencyId,
        ])->assertOk()->assertJsonPath('data.account_number', '101');

        $this->withAuth($context)->deleteJson("/api/v1/suppliers/{$supplierId}/contacts/{$contactId}")->assertNoContent();
        $this->withAuth($context)->deleteJson("/api/v1/suppliers/{$supplierId}/addresses/{$addressId}")->assertNoContent();
        $this->withAuth($context)->deleteJson("/api/v1/suppliers/{$supplierId}/bank-accounts/{$bankId}")->assertNoContent();
    }

    public function test_category_crud_assignment_and_lookup(): void
    {
        $context = $this->createAuthContext();
        $supplierId = $this->createSupplier($context);

        $categoryId = (int) $this->withAuth($context)->postJson('/api/v1/supplier-categories', [
            'code' => 'SERV', 'name' => 'Service Suppliers', 'is_active' => true,
        ])->assertCreated()->json('data.id');
        $this->withAuth($context)->putJson('/api/v1/supplier-categories/'.$categoryId, [
            'code' => 'SERV', 'name' => 'Services Updated', 'is_active' => true,
        ])->assertOk()->assertJsonPath('data.name', 'Services Updated');
        $this->withAuth($context)->getJson('/api/v1/supplier-categories/lookup?search=SERV')
            ->assertOk()->assertJsonPath('data.0.code', 'SERV');

        $this->withAuth($context)->postJson("/api/v1/suppliers/{$supplierId}/categories", [
            'category_id' => $categoryId,
        ])->assertCreated()->assertJsonPath('data.code', 'SERV');
        $this->withAuth($context)->getJson("/api/v1/suppliers/{$supplierId}/categories")
            ->assertOk()->assertJsonPath('data.0.name', 'Services Updated');
        $this->withAuth($context)->deleteJson("/api/v1/suppliers/{$supplierId}/categories/{$categoryId}")->assertNoContent();
        $this->withAuth($context)->putJson('/api/v1/supplier-categories/'.$categoryId, [
            'code' => 'SERV', 'name' => 'Services Updated', 'is_active' => false,
        ])->assertOk()->assertJsonPath('data.is_active', false);
        $this->withAuth($context)->postJson("/api/v1/suppliers/{$supplierId}/categories", [
            'category_id' => $categoryId,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['category_id'])
            ->assertJsonPath('errors.category_id.0', 'Inactive supplier category cannot be assigned.');
        $this->withAuth($context)->deleteJson('/api/v1/supplier-categories/'.$categoryId)->assertNoContent();
    }

    public function test_document_item_mapping_and_credit_profile_crud(): void
    {
        $context = $this->createAuthContext();
        $supplierId = $this->createSupplier($context);
        $uomId = $this->createUom($context, 'BOX');
        $itemId = $this->createItem($context, $uomId, 'FILTER');

        $documentId = (int) $this->withAuth($context)->postJson("/api/v1/suppliers/{$supplierId}/documents", [
            'document_type' => 'license', 'document_number' => 'LIC-1', 'status' => 'active',
        ])->assertCreated()->json('data.id');
        $this->withAuth($context)->putJson("/api/v1/suppliers/{$supplierId}/documents/{$documentId}", [
            'document_type' => 'license', 'document_number' => 'LIC-2', 'status' => 'active',
        ])->assertOk()->assertJsonPath('data.document_number', 'LIC-2');

        $mappingId = (int) $this->withAuth($context)->postJson("/api/v1/suppliers/{$supplierId}/item-mappings", [
            'item_id' => $itemId,
            'default_purchase_uom_id' => $uomId,
            'minimum_order_quantity' => '2.250000',
            'is_preferred' => true,
        ])->assertCreated()
            ->assertJsonPath('data.item.code', 'FILTER')
            ->assertJsonPath('data.default_purchase_uom.code', 'BOX')
            ->json('data.id');
        $this->withAuth($context)->postJson("/api/v1/suppliers/{$supplierId}/item-mappings", [
            'item_id' => $itemId,
            'default_purchase_uom_id' => $uomId,
            'minimum_order_quantity' => '1.000000',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['item_id'])
            ->assertJsonPath('errors.item_id.0', 'Supplier item mapping already exists.');
        $this->withAuth($context)->putJson("/api/v1/suppliers/{$supplierId}/item-mappings/{$mappingId}", [
            'item_id' => $itemId,
            'default_purchase_uom_id' => $uomId,
            'minimum_order_quantity' => '3.500000',
        ])->assertOk()->assertJsonPath('data.minimum_order_quantity', '3.500000');

        $this->withAuth($context)->putJson("/api/v1/suppliers/{$supplierId}/credit-profile", [
            'credit_limit' => '10000.000000',
            'credit_period_days' => 45,
            'warning_threshold_percent' => '80.000000',
        ])->assertOk()->assertJsonPath('data.credit_limit', '10000.000000');
        $this->withAuth($context)->getJson("/api/v1/suppliers/{$supplierId}/credit-profile")
            ->assertOk()->assertJsonPath('data.credit_period_days', 45);

        $this->withAuth($context)->deleteJson("/api/v1/suppliers/{$supplierId}/documents/{$documentId}")->assertNoContent();
        $this->withAuth($context)->deleteJson("/api/v1/suppliers/{$supplierId}/item-mappings/{$mappingId}")->assertNoContent();
    }

    public function test_status_change_records_history_and_blacklist_is_excluded_from_lookup(): void
    {
        $context = $this->createAuthContext();
        $supplierId = $this->createSupplier($context);

        $this->withAuth($context)->patchJson("/api/v1/suppliers/{$supplierId}/status", [
            'status' => 'blacklisted', 'reason' => 'Compliance failure',
        ])->assertOk()->assertJsonPath('data.status', 'blacklisted');
        $this->withAuth($context)->getJson("/api/v1/suppliers/{$supplierId}/status-history")
            ->assertOk()
            ->assertJsonPath('data.0.new_status', 'blacklisted')
            ->assertJsonPath('data.0.reason', 'Compliance failure');
        $this->withAuth($context)->getJson('/api/v1/suppliers/lookup/active?search=SUP-001')
            ->assertOk()->assertJsonCount(0, 'data');
        $this->withAuth($context)->getJson('/api/v1/suppliers/lookup?status=blacklisted&search=SUP-001')
            ->assertOk()
            ->assertJsonPath('data.0.id', $supplierId)
            ->assertJsonPath('data.0.status', 'blacklisted');
        $this->withAuth($context)->patchJson("/api/v1/suppliers/{$supplierId}/status", [
            'status' => 'active',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['status'])
            ->assertJsonPath('errors.status.0', 'Invalid supplier status transition.');
    }

    public function test_duplicates_tenant_isolation_and_validation_error_format(): void
    {
        $tenantA = $this->createAuthContext(['code' => 'SUP-A', 'email' => 'sup-a@example.test']);
        $tenantB = $this->createAuthContext(['code' => 'SUP-B', 'email' => 'sup-b@example.test']);
        $supplierId = $this->createSupplier($tenantA);

        $this->withAuth($tenantA)->postJson('/api/v1/suppliers', $this->supplierPayload())
            ->assertStatus(409)
            ->assertJsonPath('message', 'Supplier code already exists for this tenant.');
        $this->withAuth($tenantA)->postJson('/api/v1/suppliers', $this->supplierPayload([
            'code' => 'SUP-OTHER',
            'supplier_number' => 'SUP-NO-1',
        ]))->assertStatus(409)
            ->assertJsonPath('message', 'Supplier number already exists for this tenant.');

        $this->withAuth($tenantB)->getJson('/api/v1/suppliers/'.$supplierId)->assertForbidden();
        $this->withAuth($tenantA)->postJson('/api/v1/suppliers', [])
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Validation failed.')
            ->assertJsonStructure(['errors' => ['code', 'name', 'supplier_type']]);
    }

    public function test_supplier_rejects_finance_owned_opening_balance(): void
    {
        $context = $this->createAuthContext();

        $this->withAuth($context)->postJson('/api/v1/suppliers', $this->supplierPayload([
            'opening_balance' => '125.000000',
        ]))->assertUnprocessable()
            ->assertJsonPath('message', 'Validation failed.')
            ->assertJsonStructure(['errors' => ['opening_balance']]);
    }

    private function supplierPayload(array $overrides = []): array
    {
        return [
            'supplier_number' => 'SUP-NO-1',
            'code' => 'SUP-001',
            'name' => 'Test Supplier',
            'supplier_type' => 'company',
            'status' => 'active',
            'email' => 'supplier@example.test',
            'is_credit_allowed' => true,
            'is_advance_allowed' => true,
            ...$overrides,
        ];
    }

    private function createSupplier(array $context): int
    {
        return (int) $this->withAuth($context)->postJson('/api/v1/suppliers', $this->supplierPayload())
            ->assertCreated()->json('data.id');
    }

    private function createSupplierCategory(array $context, string $code): int
    {
        return (int) $this->withAuth($context)->postJson('/api/v1/supplier-categories', [
            'code' => $code, 'name' => 'Category '.$code, 'is_active' => true,
        ])->assertCreated()->json('data.id');
    }

    private function createItem(array $context, int $uomId, string $code): int
    {
        return (int) $this->withAuth($context)->postJson('/api/v1/items', [
            'code' => $code,
            'name' => 'Item '.$code,
            'item_type' => 'stock',
            'tracking_type' => 'none',
            'costing_method' => 'fifo',
            'base_uom_id' => $uomId,
            'is_stockable' => true,
            'is_combo' => false,
            'is_active' => true,
        ])->assertCreated()->json('data.id');
    }

    private function createCurrency(string $code): int
    {
        return (int) DB::table('currencies')->insertGetId([
            'row_version' => 1,
            'code' => $code,
            'name' => 'Currency '.$code,
            'symbol' => $code,
            'decimal_places' => 2,
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
        ]);
    }

    private function createAuthContext(array $overrides = []): array
    {
        $now = now();
        $code = strtoupper((string) ($overrides['code'] ?? 'SUPPLIER'));
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
        $email = (string) ($overrides['email'] ?? 'supplier-admin@example.test');
        $userId = (int) \Tests\Support\TenantUserFixture::create([
            'tenant_id' => $tenantId,
            'first_name' => 'Supplier',
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
        \Tests\Support\TenantAuthenticationFixture::provision($tenantId, $userId, $email);

        $permissions = SupplierAuthorizationService::descriptions();
        $permissions[ItemAuthorizationService::CREATE] = 'Create items used by Supplier API fixtures.';
        foreach ($permissions as $name => $description) {
            $permissionId = (int) DB::table('permissions')->insertGetId([
                'tenant_id' => $tenantId,
                'name' => $name,
                'guard_name' => UserGuard::TENANT_API,
                'module' => 'Supplier',
                'description' => $description,
                'row_version' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            DB::table('user_permissions')->insert([
                'tenant_id' => $tenantId,
                'user_id' => $userId,
                'permission_id' => $permissionId,
                'row_version' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

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
}
