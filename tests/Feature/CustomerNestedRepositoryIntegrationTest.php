<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Customer\Domain\Entities\CustomerAddress;
use Modules\Customer\Domain\Entities\CustomerContact;
use Modules\Customer\Domain\RepositoryInterfaces\CustomerAddressRepositoryInterface;
use Modules\Customer\Domain\RepositoryInterfaces\CustomerContactRepositoryInterface;
use Tests\TestCase;

class CustomerNestedRepositoryIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedReferenceData();
    }

    public function test_address_repository_keeps_single_default_per_customer_type(): void
    {
        /** @var CustomerAddressRepositoryInterface $repository */
        $repository = app(CustomerAddressRepositoryInterface::class);

        $first = $repository->save(new CustomerAddress(
            tenantId: 11,
            customerId: 1101,
            type: 'billing',
            addressLine1: '1 Main Street',
            city: 'Colombo',
            postalCode: '00100',
            countryId: 91,
            isDefault: true,
        ));

        $second = $repository->save(new CustomerAddress(
            tenantId: 11,
            customerId: 1101,
            type: 'billing',
            addressLine1: '2 Main Street',
            city: 'Colombo',
            postalCode: '00100',
            countryId: 91,
            isDefault: true,
        ));

        $firstRow = DB::table('customer_addresses')->where('id', $first->getId())->first();
        $secondRow = DB::table('customer_addresses')->where('id', $second->getId())->first();

        $this->assertNotNull($firstRow);
        $this->assertNotNull($secondRow);
        $this->assertSame(0, (int) $firstRow->is_default);
        $this->assertSame(1, (int) $secondRow->is_default);
    }

    public function test_contact_repository_keeps_single_primary_per_customer(): void
    {
        /** @var CustomerContactRepositoryInterface $repository */
        $repository = app(CustomerContactRepositoryInterface::class);

        $first = $repository->save(new CustomerContact(
            tenantId: 11,
            customerId: 1101,
            name: 'John Doe',
            email: 'john@example.com',
            isPrimary: true,
        ));

        $second = $repository->save(new CustomerContact(
            tenantId: 11,
            customerId: 1101,
            name: 'Jane Doe',
            email: 'jane@example.com',
            isPrimary: true,
        ));

        $firstRow = DB::table('customer_contacts')->where('id', $first->getId())->first();
        $secondRow = DB::table('customer_contacts')->where('id', $second->getId())->first();

        $this->assertNotNull($firstRow);
        $this->assertNotNull($secondRow);
        $this->assertSame(0, (int) $firstRow->is_primary);
        $this->assertSame(1, (int) $secondRow->is_primary);
    }

    public function test_clear_default_by_customer_and_type_is_tenant_scoped(): void
    {
        /** @var CustomerAddressRepositoryInterface $repository */
        $repository = app(CustomerAddressRepositoryInterface::class);

        $addressA = $repository->save(new CustomerAddress(
            tenantId: 11,
            customerId: 1101,
            type: 'billing',
            addressLine1: 'Tenant 11 Address',
            city: 'Colombo',
            postalCode: '00100',
            countryId: 91,
            isDefault: true,
        ));

        $addressB = $repository->save(new CustomerAddress(
            tenantId: 12,
            customerId: 1201,
            type: 'billing',
            addressLine1: 'Tenant 12 Address',
            city: 'Kandy',
            postalCode: '20000',
            countryId: 92,
            isDefault: true,
        ));

        $repository->clearDefaultByCustomerAndType(11, 1101, 'billing');

        $addressARow = DB::table('customer_addresses')->where('id', $addressA->getId())->first();
        $addressBRow = DB::table('customer_addresses')->where('id', $addressB->getId())->first();

        $this->assertNotNull($addressARow);
        $this->assertNotNull($addressBRow);
        $this->assertSame(0, (int) $addressARow->is_default);
        $this->assertSame(1, (int) $addressBRow->is_default);
    }

    public function test_clear_primary_by_customer_is_tenant_scoped(): void
    {
        /** @var CustomerContactRepositoryInterface $repository */
        $repository = app(CustomerContactRepositoryInterface::class);

        $contactA = $repository->save(new CustomerContact(
            tenantId: 11,
            customerId: 1101,
            name: 'Tenant 11 Primary',
            email: 'tenant11@example.com',
            isPrimary: true,
        ));

        $contactB = $repository->save(new CustomerContact(
            tenantId: 12,
            customerId: 1201,
            name: 'Tenant 12 Primary',
            email: 'tenant12@example.com',
            isPrimary: true,
        ));

        $repository->clearPrimaryByCustomer(11, 1101);

        $contactARow = DB::table('customer_contacts')->where('id', $contactA->getId())->first();
        $contactBRow = DB::table('customer_contacts')->where('id', $contactB->getId())->first();

        $this->assertNotNull($contactARow);
        $this->assertNotNull($contactBRow);
        $this->assertSame(0, (int) $contactARow->is_primary);
        $this->assertSame(1, (int) $contactBRow->is_primary);
    }

    private function seedReferenceData(): void
    {
        $this->insertTenant(11);
        $this->insertTenant(12);

        $this->insertCountry(91, 'LK', 'Sri Lanka');
        $this->insertCountry(92, 'IN', 'India');

        $this->insertCustomer(1101, 11, 'Customer 11');
        $this->insertCustomer(1201, 12, 'Customer 12');
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

    private function insertCountry(int $countryId, string $code, string $name): void
    {
        DB::table('countries')->insert([
            'id' => $countryId,
            'code' => $code,
            'name' => $name,
            'phone_code' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function insertCustomer(int $customerId, int $tenantId, string $name): void
    {
        DB::table('customers')->insert([
            'id' => $customerId,
            'tenant_id' => $tenantId,
            'user_id' => null,
            'org_unit_id' => null,
            'customer_code' => null,
            'name' => $name,
            'type' => 'company',
            'tax_number' => null,
            'registration_number' => null,
            'currency_id' => null,
            'credit_limit' => '0.000000',
            'payment_terms_days' => 30,
            'ar_account_id' => null,
            'status' => 'active',
            'notes' => null,
            'metadata' => null,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);
    }
}
