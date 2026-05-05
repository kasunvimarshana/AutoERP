<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Supplier\Domain\Entities\SupplierAddress;
use Modules\Supplier\Domain\Entities\SupplierContact;
use Modules\Supplier\Domain\Entities\SupplierProduct;
use Modules\Supplier\Domain\RepositoryInterfaces\SupplierAddressRepositoryInterface;
use Modules\Supplier\Domain\RepositoryInterfaces\SupplierContactRepositoryInterface;
use Modules\Supplier\Domain\RepositoryInterfaces\SupplierProductRepositoryInterface;
use Tests\TestCase;

class SupplierNestedRepositoryIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedReferenceData();
    }

    public function test_address_repository_keeps_single_default_per_supplier_type(): void
    {
        /** @var SupplierAddressRepositoryInterface $repository */
        $repository = app(SupplierAddressRepositoryInterface::class);

        $first = $repository->save(new SupplierAddress(
            tenantId: 11,
            supplierId: 1101,
            type: 'billing',
            addressLine1: '1 Main Street',
            city: 'Colombo',
            postalCode: '00100',
            countryId: 91,
            isDefault: true,
        ));

        $second = $repository->save(new SupplierAddress(
            tenantId: 11,
            supplierId: 1101,
            type: 'billing',
            addressLine1: '2 Main Street',
            city: 'Colombo',
            postalCode: '00200',
            countryId: 91,
            isDefault: true,
        ));

        // Simulate service behaviour: clear all defaults except the new one
        $repository->clearDefaultBySupplierAndType(11, 1101, 'billing', $second->getId());

        $firstRow = DB::table('supplier_addresses')->where('id', $first->getId())->first();
        $secondRow = DB::table('supplier_addresses')->where('id', $second->getId())->first();

        $this->assertNotNull($firstRow);
        $this->assertNotNull($secondRow);
        $this->assertSame(0, (int) $firstRow->is_default);
        $this->assertSame(1, (int) $secondRow->is_default);
    }

    public function test_address_clear_default_is_tenant_scoped(): void
    {
        /** @var SupplierAddressRepositoryInterface $repository */
        $repository = app(SupplierAddressRepositoryInterface::class);

        $addressA = $repository->save(new SupplierAddress(
            tenantId: 11,
            supplierId: 1101,
            type: 'billing',
            addressLine1: 'Tenant 11 Address',
            city: 'Colombo',
            postalCode: '00100',
            countryId: 91,
            isDefault: true,
        ));

        $addressB = $repository->save(new SupplierAddress(
            tenantId: 12,
            supplierId: 1201,
            type: 'billing',
            addressLine1: 'Tenant 12 Address',
            city: 'Kandy',
            postalCode: '20000',
            countryId: 91,
            isDefault: true,
        ));

        $repository->clearDefaultBySupplierAndType(11, 1101, 'billing');

        $addressARow = DB::table('supplier_addresses')->where('id', $addressA->getId())->first();
        $addressBRow = DB::table('supplier_addresses')->where('id', $addressB->getId())->first();

        $this->assertNotNull($addressARow);
        $this->assertNotNull($addressBRow);
        $this->assertSame(0, (int) $addressARow->is_default);
        $this->assertSame(1, (int) $addressBRow->is_default);
    }

    public function test_contact_repository_keeps_single_primary_per_supplier(): void
    {
        /** @var SupplierContactRepositoryInterface $repository */
        $repository = app(SupplierContactRepositoryInterface::class);

        $first = $repository->save(new SupplierContact(
            tenantId: 11,
            supplierId: 1101,
            name: 'Alice',
            isPrimary: true,
        ));

        $second = $repository->save(new SupplierContact(
            tenantId: 11,
            supplierId: 1101,
            name: 'Bob',
            isPrimary: true,
        ));

        // Simulate service behaviour: clear all primaries except the new one
        $repository->clearPrimaryBySupplier(11, 1101, $second->getId());

        $firstRow = DB::table('supplier_contacts')->where('id', $first->getId())->first();
        $secondRow = DB::table('supplier_contacts')->where('id', $second->getId())->first();

        $this->assertNotNull($firstRow);
        $this->assertNotNull($secondRow);
        $this->assertSame(0, (int) $firstRow->is_primary);
        $this->assertSame(1, (int) $secondRow->is_primary);
    }

    public function test_contact_clear_primary_is_tenant_scoped(): void
    {
        /** @var SupplierContactRepositoryInterface $repository */
        $repository = app(SupplierContactRepositoryInterface::class);

        $contactA = $repository->save(new SupplierContact(
            tenantId: 11,
            supplierId: 1101,
            name: 'Tenant 11 Primary',
            isPrimary: true,
        ));

        $contactB = $repository->save(new SupplierContact(
            tenantId: 12,
            supplierId: 1201,
            name: 'Tenant 12 Primary',
            isPrimary: true,
        ));

        $repository->clearPrimaryBySupplier(11, 1101);

        $contactARow = DB::table('supplier_contacts')->where('id', $contactA->getId())->first();
        $contactBRow = DB::table('supplier_contacts')->where('id', $contactB->getId())->first();

        $this->assertNotNull($contactARow);
        $this->assertNotNull($contactBRow);
        $this->assertSame(0, (int) $contactARow->is_primary);
        $this->assertSame(1, (int) $contactBRow->is_primary);
    }

    public function test_product_repository_keeps_single_preferred_per_product_variant(): void
    {
        /** @var SupplierProductRepositoryInterface $repository */
        $repository = app(SupplierProductRepositoryInterface::class);

        $first = $repository->save(new SupplierProduct(
            tenantId: 11,
            supplierId: 1101,
            productId: 2001,
            isPreferred: true,
        ));

        $second = $repository->save(new SupplierProduct(
            tenantId: 11,
            supplierId: 1102,
            productId: 2001,
            isPreferred: true,
        ));

        // Simulate service behaviour: clear all preferred except the new one
        $repository->clearPreferredByProductVariant(11, 2001, null, $second->getId());

        $firstRow = DB::table('supplier_products')->where('id', $first->getId())->first();
        $secondRow = DB::table('supplier_products')->where('id', $second->getId())->first();

        $this->assertNotNull($firstRow);
        $this->assertNotNull($secondRow);
        $this->assertSame(0, (int) $firstRow->is_preferred);
        $this->assertSame(1, (int) $secondRow->is_preferred);
    }

    public function test_product_clear_preferred_is_tenant_scoped(): void
    {
        /** @var SupplierProductRepositoryInterface $repository */
        $repository = app(SupplierProductRepositoryInterface::class);

        $spA = $repository->save(new SupplierProduct(
            tenantId: 11,
            supplierId: 1101,
            productId: 2001,
            isPreferred: true,
        ));

        $spB = $repository->save(new SupplierProduct(
            tenantId: 12,
            supplierId: 1201,
            productId: 2002,
            isPreferred: true,
        ));

        $repository->clearPreferredByProductVariant(11, 2001, null);

        $spARow = DB::table('supplier_products')->where('id', $spA->getId())->first();
        $spBRow = DB::table('supplier_products')->where('id', $spB->getId())->first();

        $this->assertNotNull($spARow);
        $this->assertNotNull($spBRow);
        $this->assertSame(0, (int) $spARow->is_preferred);
        $this->assertSame(1, (int) $spBRow->is_preferred);
    }

    private function seedReferenceData(): void
    {
        $this->insertTenant(11);
        $this->insertTenant(12);

        $this->insertCountry(91, 'LK', 'Sri Lanka');

        $this->insertSupplier(1101, 11, 'Supplier A');
        $this->insertSupplier(1102, 11, 'Supplier B');
        $this->insertSupplier(1201, 12, 'Supplier C');

        $this->insertUom(3001, 11, 'Each');
        $this->insertUom(3002, 12, 'Each');

        $this->insertProduct(2001, 11, 3001, 'Product 1');
        $this->insertProduct(2002, 12, 3002, 'Product 2');
    }

    private function insertTenant(int $tenantId): void
    {
        DB::table('tenants')->insert([
            'id' => $tenantId,
            'name' => 'Tenant '.$tenantId,
            'slug' => 'tenant-'.$tenantId,
            'domain' => null,
            'logo_path' => null,
            'database_config' => null,
            'mail_config' => null,
            'cache_config' => null,
            'queue_config' => null,
            'feature_flags' => null,
            'api_keys' => null,
            'settings' => null,
            'plan' => 'free',
            'tenant_plan_id' => null,
            'status' => 'active',
            'active' => true,
            'trial_ends_at' => null,
            'subscription_ends_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);
    }

    private function insertCountry(int $id, string $code, string $name): void
    {
        DB::table('countries')->insert([
            'id' => $id,
            'code' => $code,
            'name' => $name,
            'phone_code' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function insertSupplier(int $id, int $tenantId, string $name): void
    {
        DB::table('suppliers')->insert([
            'id' => $id,
            'tenant_id' => $tenantId,
            'org_unit_id' => null,
            'user_id' => null,
            'supplier_code' => null,
            'name' => $name,
            'type' => 'company',
            'tax_number' => null,
            'registration_number' => null,
            'currency_id' => null,
            'payment_terms_days' => 30,
            'ap_account_id' => null,
            'status' => 'active',
            'notes' => null,
            'metadata' => null,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);
    }

    private function insertUom(int $id, int $tenantId, string $name): void
    {
        DB::table('units_of_measure')->insert([
            'id' => $id,
            'tenant_id' => $tenantId,
            'name' => $name,
            'symbol' => 'ea',
            'type' => 'unit',
            'is_base' => true,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);
    }

    private function insertProduct(int $id, int $tenantId, int $uomId, string $name): void
    {
        DB::table('products')->insert([
            'id' => $id,
            'tenant_id' => $tenantId,
            'org_unit_id' => null,
            'category_id' => null,
            'brand_id' => null,
            'type' => 'physical',
            'name' => $name,
            'slug' => 'product-'.strtolower(str_replace(' ', '-', $name)).'-'.$id,
            'sku' => 'SKU-'.$id,
            'description' => null,
            'base_uom_id' => $uomId,
            'purchase_uom_id' => null,
            'sales_uom_id' => null,
            'tax_group_id' => null,
            'uom_conversion_factor' => '1.0000000000',
            'is_batch_tracked' => false,
            'is_lot_tracked' => false,
            'is_serial_tracked' => false,
            'valuation_method' => 'fifo',
            'standard_cost' => null,
            'income_account_id' => null,
            'cogs_account_id' => null,
            'inventory_account_id' => null,
            'expense_account_id' => null,
            'is_active' => true,
            'image_path' => null,
            'metadata' => null,
            'purchase_price' => null,
            'sales_price' => null,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);
    }
}
