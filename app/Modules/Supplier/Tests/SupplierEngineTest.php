<?php

declare(strict_types=1);

namespace Modules\Supplier\Tests;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Modules\Item\DTOs\CreateItemData;
use Modules\Item\Enums\ItemType;
use Modules\Item\Models\Item;
use Modules\Item\Services\ItemCreationService;
use Modules\Supplier\DTOs\CreateSupplierData;
use Modules\Supplier\DTOs\SupplierAddressData;
use Modules\Supplier\DTOs\SupplierBankAccountData;
use Modules\Supplier\DTOs\SupplierCategoryData;
use Modules\Supplier\DTOs\SupplierContactData;
use Modules\Supplier\DTOs\SupplierCreditProfileData;
use Modules\Supplier\DTOs\SupplierDocumentData;
use Modules\Supplier\DTOs\SupplierItemMappingData;
use Modules\Supplier\DTOs\SupplierStatusChangeData;
use Modules\Supplier\Enums\SupplierAddressType;
use Modules\Supplier\Enums\SupplierDocumentStatus;
use Modules\Supplier\Enums\SupplierDocumentType;
use Modules\Supplier\Enums\SupplierStatus;
use Modules\Supplier\Enums\SupplierType;
use Modules\Supplier\Models\Supplier;
use Modules\Supplier\Models\SupplierCategory;
use Modules\Supplier\Services\SupplierAddressService;
use Modules\Supplier\Services\SupplierBankAccountService;
use Modules\Supplier\Services\SupplierCategoryService;
use Modules\Supplier\Services\SupplierContactService;
use Modules\Supplier\Services\SupplierCreationService;
use Modules\Supplier\Services\SupplierCreditProfileService;
use Modules\Supplier\Services\SupplierItemMappingService;
use Modules\Supplier\Services\SupplierLookupService;
use Modules\Supplier\Services\SupplierStatusService;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Tests\TestCase;

final class SupplierEngineTest extends TestCase
{
    use RefreshDatabase;

    public function test_supplier_creation_builds_reference_graph_and_credit_profile(): void
    {
        [$tenantId, $organizationUnitId, $currencyId] = $this->scopeContext();
        $uomId = $this->createUom($tenantId, $organizationUnitId);
        $item = $this->createItem($tenantId, $organizationUnitId, 'PART-1', $uomId);
        $category = $this->createCategory($tenantId, $organizationUnitId, 'PARTS');

        $supplier = app(SupplierCreationService::class)->create(new CreateSupplierData(
            tenantId: $tenantId,
            organizationUnitId: $organizationUnitId,
            code: 'SUP-ACME',
            name: 'Acme Supplies',
            supplierType: SupplierType::Company,
            status: SupplierStatus::Active,
            email: 'orders@acme.test',
            defaultCurrencyId: $currencyId,
            creditLimit: '50000.000000',
            creditProfile: new SupplierCreditProfileData(
                creditLimit: '50000.000000',
                creditPeriodDays: 30,
                warningThresholdPercent: '75.000000',
                allowPartialPayment: true,
            ),
            contacts: [
                new SupplierContactData('Jane Buyer', email: 'jane@acme.test', isPrimary: true),
            ],
            addresses: [
                new SupplierAddressData(SupplierAddressType::Billing, '10 Main Street', city: 'Colombo', isPrimary: true),
            ],
            bankAccounts: [
                new SupplierBankAccountData('Test Bank', 'Acme Supplies', '001234', currencyId: $currencyId, isPrimary: true),
            ],
            categoryIds: [(int) $category->getKey()],
            documents: [
                new SupplierDocumentData(SupplierDocumentType::BusinessRegistration, 'BR-001', status: SupplierDocumentStatus::Active),
            ],
            itemMappings: [
                new SupplierItemMappingData(
                    itemId: (int) $item->getKey(),
                    supplierItemCode: 'ACME-PART-1',
                    defaultPurchaseUomId: $uomId,
                    minimumOrderQuantity: '5.000000',
                    leadTimeDays: 7,
                    isPreferred: true,
                ),
            ],
        ));

        $this->assertSame(SupplierStatus::Active, $supplier->status);
        $this->assertSame('50000.000000', (string) $supplier->credit_limit);
        $this->assertCount(1, $supplier->contacts);
        $this->assertCount(1, $supplier->addresses);
        $this->assertCount(1, $supplier->bankAccounts);
        $this->assertCount(1, $supplier->categories);
        $this->assertCount(1, $supplier->documents);
        $this->assertCount(1, $supplier->itemMappings);
        $this->assertCount(1, $supplier->statusHistories);

        $profile = app(SupplierCreditProfileService::class)->get($supplier);
        $this->assertNotNull($profile);
        $this->assertSame('50000.000000', (string) $profile->credit_limit);
        $this->assertSame(30, $profile->credit_period_days);
        $this->assertSame('75.000000', (string) $profile->warning_threshold_percent);

        $result = app(SupplierLookupService::class)->result($supplier);
        $this->assertSame('50000.000000', $result->creditLimit);
    }

    public function test_duplicate_code_and_supplier_number_are_rejected_per_tenant(): void
    {
        [$tenantId] = $this->scopeContext();
        $this->createSupplier($tenantId, 'DUP', 'SUP-CUSTOM-1');

        try {
            $this->createSupplier($tenantId, 'DUP');
            $this->fail('Expected duplicate supplier code validation to fail.');
        } catch (ConflictHttpException $exception) {
            $this->assertSame('Supplier code already exists for this tenant.', $exception->getMessage());
        }

        $this->expectException(ConflictHttpException::class);
        $this->expectExceptionMessage('Supplier number already exists for this tenant.');
        $this->createSupplier($tenantId, 'UNIQUE', 'SUP-CUSTOM-1');
    }

    public function test_primary_contact_address_and_bank_account_constraints_are_enforced(): void
    {
        [$tenantId, $organizationUnitId, $currencyId] = $this->scopeContext();
        $supplier = app(SupplierCreationService::class)->create(new CreateSupplierData(
            tenantId: $tenantId,
            organizationUnitId: $organizationUnitId,
            code: 'PRIMARY',
            name: 'Primary Test',
            supplierType: SupplierType::Company,
            bankAccounts: [
                new SupplierBankAccountData('Bank A', 'Primary Test', '100', currencyId: $currencyId, isPrimary: true),
            ],
            contacts: [
                new SupplierContactData('Contact A', isPrimary: true),
            ],
            addresses: [
                new SupplierAddressData(SupplierAddressType::Billing, 'Address A', isPrimary: true),
            ],
        ));

        try {
            app(SupplierContactService::class)->create($supplier, new SupplierContactData('Contact B', isPrimary: true));
            $this->fail('Expected duplicate primary contact validation to fail.');
        } catch (InvalidArgumentException $exception) {
            $this->assertSame('Supplier can have only one primary contact.', $exception->getMessage());
        }

        try {
            app(SupplierAddressService::class)->create(
                $supplier,
                new SupplierAddressData(SupplierAddressType::Billing, 'Address B', isPrimary: true),
            );
            $this->fail('Expected duplicate primary address validation to fail.');
        } catch (InvalidArgumentException $exception) {
            $this->assertSame('Supplier can have only one primary address per address type.', $exception->getMessage());
        }

        try {
            app(SupplierBankAccountService::class)->create(
                $supplier,
                new SupplierBankAccountData('Bank A', 'Primary Test', '100', currencyId: $currencyId),
            );
            $this->fail('Expected duplicate bank account validation to fail.');
        } catch (InvalidArgumentException $exception) {
            $this->assertSame('Supplier bank account number already exists.', $exception->getMessage());
        }

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Supplier can have only one primary bank account.');
        app(SupplierBankAccountService::class)->create(
            $supplier,
            new SupplierBankAccountData('Bank B', 'Primary Test', '200', currencyId: $currencyId, isPrimary: true),
        );
    }

    public function test_category_assignment_and_item_mapping_drive_lookups(): void
    {
        [$tenantId, $organizationUnitId] = $this->scopeContext();
        $uomId = $this->createUom($tenantId, $organizationUnitId);
        $item = $this->createItem($tenantId, $organizationUnitId, 'FILTER-ITEM', $uomId);
        $category = $this->createCategory($tenantId, $organizationUnitId, 'FILTER-CAT');

        $supplier = app(SupplierCreationService::class)->create(new CreateSupplierData(
            tenantId: $tenantId,
            organizationUnitId: $organizationUnitId,
            code: 'FILTER-SUP',
            name: 'Filter Supplier',
            supplierType: SupplierType::Company,
            status: SupplierStatus::Active,
            categoryIds: [(int) $category->getKey()],
            itemMappings: [
                new SupplierItemMappingData((int) $item->getKey(), isPreferred: true),
            ],
        ));

        $lookup = app(SupplierLookupService::class);
        $this->assertTrue($lookup->suppliersByCategory($tenantId, (int) $category->getKey(), $organizationUnitId)->contains($supplier));
        $this->assertTrue($lookup->suppliersByItem($tenantId, (int) $item->getKey(), $organizationUnitId)->contains($supplier));
        $this->assertTrue($lookup->preferredSuppliersForItem($tenantId, (int) $item->getKey(), $organizationUnitId)->contains($supplier));
        $this->assertTrue($lookup->suppliersAllowedForCredit($tenantId, $organizationUnitId)->contains($supplier));
    }

    public function test_status_transition_records_history_and_blacklist_excludes_active_lookup(): void
    {
        [$tenantId] = $this->scopeContext();
        $supplier = $this->createSupplier($tenantId, 'STATUS', status: SupplierStatus::Active);

        app(SupplierStatusService::class)->change($supplier, new SupplierStatusChangeData(
            SupplierStatus::Blacklisted,
            reason: 'Compliance failure',
            changedBy: 42,
        ));

        $this->assertSame(SupplierStatus::Blacklisted, $supplier->refresh()->status);
        $this->assertCount(2, $supplier->statusHistories()->get());
        $this->assertFalse(app(SupplierLookupService::class)->activeSuppliers($tenantId)->contains($supplier));
        $this->assertTrue(app(SupplierLookupService::class)->restrictedSuppliers($tenantId)->contains($supplier));
        $this->assertTrue(app(SupplierLookupService::class)->blacklistedSuppliers($tenantId)->contains($supplier));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid supplier status transition.');
        app(SupplierStatusService::class)->change($supplier, new SupplierStatusChangeData(SupplierStatus::Active));
    }

    public function test_credit_profile_validation_and_on_hold_lookup(): void
    {
        [$tenantId] = $this->scopeContext();
        $supplier = $this->createSupplier($tenantId, 'CREDIT', status: SupplierStatus::Active);

        app(SupplierCreditProfileService::class)->set($supplier, new SupplierCreditProfileData(
            creditLimit: '10000.000000',
            creditPeriodDays: 45,
            warningThresholdPercent: '90.000000',
            allowOverCredit: false,
        ));
        app(SupplierStatusService::class)->change($supplier, new SupplierStatusChangeData(SupplierStatus::OnHold));

        $this->assertTrue(app(SupplierLookupService::class)->suppliersOnHold($tenantId)->contains($supplier));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Supplier credit warning threshold must be between 0 and 100.');
        app(SupplierCreditProfileService::class)->set($supplier, new SupplierCreditProfileData(
            warningThresholdPercent: '101.000000',
        ));
    }

    public function test_cross_tenant_and_organization_references_are_rejected(): void
    {
        [$tenantId, $organizationUnitId] = $this->scopeContext();
        [$otherTenantId, $otherOrganizationUnitId] = $this->scopeContext('OTHER');
        $otherCategory = $this->createCategory($otherTenantId, $otherOrganizationUnitId, 'OTHER-CAT');
        $supplier = $this->createSupplier($tenantId, 'SCOPED', organizationUnitId: $organizationUnitId);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Supplier reference belongs to a different tenant.');
        app(SupplierCategoryService::class)->assign($supplier, [(int) $otherCategory->getKey()]);
    }

    public function test_supplier_organization_unit_must_belong_to_tenant(): void
    {
        [$tenantId] = $this->scopeContext();
        [, $otherOrganizationUnitId] = $this->scopeContext('ORG-OTHER');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Supplier organization unit belongs to a different tenant.');

        $this->createSupplier($tenantId, 'ORG-MISMATCH', organizationUnitId: $otherOrganizationUnitId);
    }

    public function test_same_tenant_cross_organization_category_assignment_is_rejected(): void
    {
        [$tenantId, $organizationUnitId] = $this->scopeContext();
        $otherOrganizationUnitId = (int) DB::table('organization_units')->insertGetId([
            'tenant_id' => $tenantId,
            'name' => 'Other Organization',
            'code' => 'ORG-OTHER-'.Str::upper(Str::random(4)),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $category = $this->createCategory($tenantId, $otherOrganizationUnitId, 'ORG-CAT');
        $supplier = $this->createSupplier($tenantId, 'ORG-SUP', organizationUnitId: $organizationUnitId);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Supplier reference belongs to a different organization unit.');
        app(SupplierCategoryService::class)->assign($supplier, [(int) $category->getKey()]);
    }

    public function test_cross_tenant_item_variant_reference_is_rejected(): void
    {
        [$tenantId, $organizationUnitId] = $this->scopeContext();
        [$otherTenantId, $otherOrganizationUnitId] = $this->scopeContext('VARIANT-OTHER');
        $item = $this->createItem($tenantId, $organizationUnitId, 'VARIANT-ITEM');
        $supplier = $this->createSupplier($tenantId, 'VARIANT-SUP', organizationUnitId: $organizationUnitId);
        $variantId = (int) DB::table('item_variants')->insertGetId([
            'tenant_id' => $otherTenantId,
            'organization_unit_id' => $otherOrganizationUnitId,
            'item_id' => $item->getKey(),
            'code' => 'CROSS-TENANT-VARIANT',
            'name' => 'Cross tenant variant',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Supplier reference belongs to a different tenant.');
        app(SupplierItemMappingService::class)->create(
            $supplier,
            new SupplierItemMappingData((int) $item->getKey(), itemVariantId: $variantId),
        );
    }

    public function test_database_seeder_adds_supplier_master_data(): void
    {
        $this->seed(DatabaseSeeder::class);
        $tenantId = (int) DB::table('tenants')->where('code', 'AUTOERP')->value('id');

        $this->assertDatabaseHas('supplier_categories', ['tenant_id' => $tenantId, 'code' => 'GENERAL']);
        $this->assertDatabaseHas('suppliers', ['tenant_id' => $tenantId, 'supplier_number' => 'SUP-000001']);
        $this->assertSame(1, Supplier::query()->where('tenant_id', $tenantId)->count());
    }

    private function createSupplier(
        int $tenantId,
        string $code,
        ?string $number = null,
        SupplierStatus $status = SupplierStatus::PendingApproval,
        ?int $organizationUnitId = null,
    ): Supplier {
        return app(SupplierCreationService::class)->create(new CreateSupplierData(
            tenantId: $tenantId,
            organizationUnitId: $organizationUnitId,
            supplierNumber: $number,
            code: $code,
            name: 'Supplier '.$code,
            supplierType: SupplierType::Company,
            status: $status,
        ));
    }

    private function createCategory(int $tenantId, ?int $organizationUnitId, string $code): SupplierCategory
    {
        return app(SupplierCategoryService::class)->create(new SupplierCategoryData(
            tenantId: $tenantId,
            organizationUnitId: $organizationUnitId,
            code: $code,
            name: 'Category '.$code,
        ));
    }

    private function createItem(int $tenantId, ?int $organizationUnitId, string $code, ?int $uomId = null): Item
    {
        return app(ItemCreationService::class)->create(new CreateItemData(
            tenantId: $tenantId,
            organizationUnitId: $organizationUnitId,
            code: $code,
            name: 'Item '.$code,
            itemType: ItemType::Stock,
            baseUomId: $uomId,
            isStockable: true,
        ));
    }

    /**
     * @return array{int, int, int}
     */
    private function scopeContext(string $suffix = ''): array
    {
        $suffix = $suffix !== '' ? $suffix : Str::upper(Str::random(5));
        $currencyId = $this->createCurrency('C'.Str::upper(Str::random(4)));
        $tenantId = (int) DB::table('tenants')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'code' => 'TEN-SUP-'.$suffix,
            'name' => 'Supplier Tenant '.$suffix,
            'slug' => 'supplier-tenant-'.Str::lower($suffix).'-'.Str::lower(Str::random(3)),
            'status' => 'active',
            'currency_id' => $currencyId,
            'is_active' => true,
            'is_isolated' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $organizationUnitId = (int) DB::table('organization_units')->insertGetId([
            'tenant_id' => $tenantId,
            'name' => 'Organization '.$suffix,
            'code' => 'ORG-'.$suffix,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$tenantId, $organizationUnitId, $currencyId];
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

    private function createUom(int $tenantId, ?int $organizationUnitId): int
    {
        return (int) DB::table('unit_of_measures')->insertGetId([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'row_version' => 1,
            'code' => 'UOM-'.Str::upper(Str::random(5)),
            'name' => 'Piece',
            'symbol' => 'pc',
            'category' => 'UNIT',
            'type' => 'UNIT',
            'decimal_precision' => 0,
            'allow_fractional_quantity' => false,
            'is_base' => true,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
