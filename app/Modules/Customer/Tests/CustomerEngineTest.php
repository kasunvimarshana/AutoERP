<?php

declare(strict_types=1);

namespace Modules\Customer\Tests;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Modules\Customer\DTOs\CreateCustomerData;
use Modules\Customer\DTOs\CustomerAddressData;
use Modules\Customer\DTOs\CustomerBankAccountData;
use Modules\Customer\DTOs\CustomerCategoryData;
use Modules\Customer\DTOs\CustomerContactData;
use Modules\Customer\DTOs\CustomerCreditProfileData;
use Modules\Customer\DTOs\CustomerDocumentData;
use Modules\Customer\DTOs\CustomerStatusChangeData;
use Modules\Customer\Enums\CustomerAddressType;
use Modules\Customer\Enums\CustomerDocumentStatus;
use Modules\Customer\Enums\CustomerDocumentType;
use Modules\Customer\Enums\CustomerStatus;
use Modules\Customer\Enums\CustomerType;
use Modules\Customer\Enums\PreferredCommunicationChannel;
use Modules\Customer\Models\Customer;
use Modules\Customer\Models\CustomerCategory;
use Modules\Customer\Services\CustomerAddressService;
use Modules\Customer\Services\CustomerAuthorizationService;
use Modules\Customer\Services\CustomerBankAccountService;
use Modules\Customer\Services\CustomerCategoryService;
use Modules\Customer\Services\CustomerContactService;
use Modules\Customer\Services\CustomerCreationService;
use Modules\Customer\Services\CustomerCreditProfileService;
use Modules\Customer\Services\CustomerDocumentService;
use Modules\Customer\Services\CustomerLookupService;
use Modules\Customer\Services\CustomerStatusService;
use Modules\User\Models\UserModel;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Tests\TestCase;

final class CustomerEngineTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_creation_builds_reference_graph_and_credit_profile(): void
    {
        [$tenantId, $organizationUnitId, $currencyId] = $this->scopeContext();
        $category = $this->createCategory($tenantId, $organizationUnitId, 'RETAIL');

        $customer = $this->withTenantExecutionContext($tenantId, fn (): Customer => app(CustomerCreationService::class)->create(new CreateCustomerData(
            tenantId: $tenantId,
            organizationUnitId: $organizationUnitId,
            code: 'CUS-ACME',
            name: 'Acme Customer',
            customerType: CustomerType::Corporate,
            status: CustomerStatus::Active,
            email: 'orders@acme.test',
            defaultCurrencyId: $currencyId,
            creditLimit: '50000.000000',
            openingBalance: '250.000000',
            isTaxExempt: true,
            marketingConsent: true,
            preferredCommunicationChannel: PreferredCommunicationChannel::Email,
            creditProfile: new CustomerCreditProfileData(
                creditLimit: '50000.000000',
                creditPeriodDays: 30,
                warningThresholdPercent: '75.000000',
                allowPartialPayment: true,
            ),
            contacts: [
                new CustomerContactData('Jane Buyer', email: 'jane@acme.test', isPrimary: true),
            ],
            addresses: [
                new CustomerAddressData(CustomerAddressType::Billing, '10 Main Street', city: 'Colombo', isPrimary: true),
            ],
            bankAccounts: [
                new CustomerBankAccountData('Test Bank', 'Acme Customer', '001234', currencyId: $currencyId, isPrimary: true),
            ],
            categoryIds: [(int) $category->getKey()],
            documents: [
                new CustomerDocumentData(CustomerDocumentType::BusinessRegistration, 'BR-001', status: CustomerDocumentStatus::Active),
            ],
        )));

        $this->assertSame(CustomerStatus::Active, $customer->status);
        $this->assertSame('50000.000000', (string) $customer->credit_limit);
        $this->assertSame('250.000000', (string) $customer->opening_balance);
        $this->assertTrue((bool) $customer->is_tax_exempt);
        $this->assertCount(1, $customer->contacts);
        $this->assertCount(1, $customer->addresses);
        $this->assertCount(1, $customer->bankAccounts);
        $this->assertCount(1, $customer->categories);
        $this->assertCount(1, $customer->documents);
        $this->assertCount(1, $customer->statusHistories);

        $profile = $this->withTenantExecutionContext($tenantId, fn () => app(CustomerCreditProfileService::class)->get($customer));
        $this->assertNotNull($profile);
        $this->assertSame('50000.000000', (string) $profile->credit_limit);
        $this->assertSame(30, $profile->credit_period_days);
        $this->assertSame('75.000000', (string) $profile->warning_threshold_percent);

        $result = $this->withTenantExecutionContext($tenantId, fn () => app(CustomerLookupService::class)->result($customer));
        $this->assertSame('50000.000000', $result->creditLimit);
        $this->assertSame('250.000000', $result->openingBalance);
    }

    public function test_duplicate_code_and_customer_number_are_rejected_per_tenant(): void
    {
        [$tenantId] = $this->scopeContext();
        $this->createCustomer($tenantId, 'DUP', 'CUS-CUSTOM-1');

        try {
            $this->createCustomer($tenantId, 'DUP');
            $this->fail('Expected duplicate customer code validation to fail.');
        } catch (ConflictHttpException $exception) {
            $this->assertSame('Customer code already exists for this tenant.', $exception->getMessage());
        }

        $this->expectException(ConflictHttpException::class);
        $this->expectExceptionMessage('Customer number already exists for this tenant.');
        $this->createCustomer($tenantId, 'UNIQUE', 'CUS-CUSTOM-1');
    }

    public function test_primary_contact_address_and_bank_account_constraints_are_enforced(): void
    {
        [$tenantId, $organizationUnitId, $currencyId] = $this->scopeContext();
        $customer = $this->withTenantExecutionContext($tenantId, fn (): Customer => app(CustomerCreationService::class)->create(new CreateCustomerData(
            tenantId: $tenantId,
            organizationUnitId: $organizationUnitId,
            code: 'PRIMARY',
            name: 'Primary Test',
            customerType: CustomerType::Company,
            bankAccounts: [
                new CustomerBankAccountData('Bank A', 'Primary Test', '100', currencyId: $currencyId, isPrimary: true),
            ],
            contacts: [
                new CustomerContactData('Contact A', isPrimary: true),
            ],
            addresses: [
                new CustomerAddressData(CustomerAddressType::Billing, 'Address A', isPrimary: true),
            ],
        )));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Customer can have only one primary contact.');
        $this->withTenantExecutionContext($tenantId, fn () => app(CustomerContactService::class)
            ->create($customer, new CustomerContactData('Contact B', isPrimary: true)));
    }

    public function test_relation_services_cover_address_bank_category_and_document_crud(): void
    {
        [$tenantId, $organizationUnitId, $currencyId] = $this->scopeContext();
        $customer = $this->createCustomer($tenantId, 'REL', organizationUnitId: $organizationUnitId);
        $category = $this->createCategory($tenantId, $organizationUnitId, 'REL-CAT');

        [$address, $account, $document, $assigned] = $this->withTenantExecutionContext(
            $tenantId,
            fn (): array => [
                app(CustomerAddressService::class)->create($customer, new CustomerAddressData(CustomerAddressType::Shipping, 'Ship line', isPrimary: true)),
                app(CustomerBankAccountService::class)->create($customer, new CustomerBankAccountData('Bank', 'Rel Customer', '200', currencyId: $currencyId, isPrimary: true)),
                app(CustomerDocumentService::class)->create($customer, new CustomerDocumentData(CustomerDocumentType::IdDocument, 'ID-1')),
                app(CustomerCategoryService::class)->attach($customer, (int) $category->getKey()),
            ],
        );

        $this->assertSame((int) $category->getKey(), (int) $assigned->getKey());
        $this->assertDatabaseHas('customer_addresses', ['id' => $address->getKey(), 'address_type' => CustomerAddressType::Shipping->value]);
        $this->assertDatabaseHas('customer_bank_accounts', ['id' => $account->getKey(), 'account_number' => '200']);
        $this->assertDatabaseHas('customer_documents', ['id' => $document->getKey(), 'document_type' => CustomerDocumentType::IdDocument->value]);

        $this->withTenantExecutionContext($tenantId, fn () => app(CustomerCategoryService::class)->detach($customer, (int) $category->getKey()));
        $this->assertDatabaseMissing('customer_category_assignments', [
            'customer_id' => $customer->getKey(),
            'customer_category_id' => $category->getKey(),
        ]);
    }

    public function test_status_transition_records_history_and_blacklist_excludes_active_lookup(): void
    {
        [$tenantId] = $this->scopeContext();
        $customer = $this->createCustomer($tenantId, 'STATUS', status: CustomerStatus::Active);

        $this->withTenantExecutionContext($tenantId, function () use ($customer, $tenantId): void {
            app(CustomerStatusService::class)->change($customer, new CustomerStatusChangeData(
                CustomerStatus::Blacklisted,
                reason: 'Compliance failure',
                changedBy: 42,
            ));

            $this->assertSame(CustomerStatus::Blacklisted, $customer->refresh()->status);
            $this->assertCount(2, $customer->statusHistories()->get());
            $this->assertFalse(app(CustomerLookupService::class)->activeCustomers($tenantId)->contains($customer));
            $this->assertTrue(app(CustomerLookupService::class)->restrictedCustomers($tenantId)->contains($customer));
            $this->assertTrue(app(CustomerLookupService::class)->blacklistedCustomers($tenantId)->contains($customer));
        });
    }

    public function test_credit_profile_validation_and_on_hold_lookup(): void
    {
        [$tenantId] = $this->scopeContext();
        $customer = $this->createCustomer($tenantId, 'CREDIT', status: CustomerStatus::Active);

        $this->withTenantExecutionContext($tenantId, function () use ($customer, $tenantId): void {
            app(CustomerCreditProfileService::class)->set($customer, new CustomerCreditProfileData(
                creditLimit: '10000.000000',
                creditPeriodDays: 45,
                warningThresholdPercent: '90.000000',
                allowOverCredit: false,
            ));
            app(CustomerStatusService::class)->change($customer, new CustomerStatusChangeData(CustomerStatus::OnHold));

            $this->assertTrue(app(CustomerLookupService::class)->customersOnHold($tenantId)->contains($customer));
        });

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Customer credit warning threshold must be between 0 and 100.');
        $this->withTenantExecutionContext($tenantId, fn () => app(CustomerCreditProfileService::class)->set($customer, new CustomerCreditProfileData(
            warningThresholdPercent: '101.000000',
        )));
    }

    public function test_cross_tenant_and_organization_references_are_rejected(): void
    {
        [$tenantId, $organizationUnitId] = $this->scopeContext();
        [$otherTenantId, $otherOrganizationUnitId] = $this->scopeContext('OTHER');
        $otherCategory = $this->createCategory($otherTenantId, $otherOrganizationUnitId, 'OTHER-CAT');
        $customer = $this->createCustomer($tenantId, 'SCOPED', organizationUnitId: $organizationUnitId);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Customer reference belongs to a different tenant.');
        $this->withTenantExecutionContext($tenantId, fn () => app(CustomerCategoryService::class)->assign($customer, [(int) $otherCategory->getKey()]));
    }

    public function test_customer_api_crud_lookup_and_readable_resource_response(): void
    {
        $this->withoutMiddleware();
        $this->trustTenantScopedRequestContextFromPayload();
        $this->mock(CustomerAuthorizationService::class, fn ($mock) => $mock->shouldReceive('assert')->zeroOrMoreTimes());
        [$tenantId, $organizationUnitId] = $this->scopeContext();
        $this->actingAsTenantUser($tenantId);

        $create = $this->tenantPostJson($tenantId, '/api/v1/customers/with-relations', [
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'customer' => [
                'code' => 'API-CUS',
                'name' => 'API Customer',
                'customer_type' => 'retail',
                'status' => 'active',
                'opening_balance' => '10.000000',
                'marketing_consent' => true,
            ],
            'contacts' => [['contact_name' => 'API Contact', 'is_primary' => true]],
            'addresses' => [['address_type' => 'billing', 'address_line_1' => 'API Address', 'is_primary' => true]],
            'bank_accounts' => [],
            'categories' => [],
            'documents' => [['document_type' => 'id_document', 'document_number' => 'ID-API']],
            'credit_profile' => ['credit_limit' => '100.000000', 'warning_threshold_percent' => '80.000000'],
        ]);

        $create->assertCreated()
            ->assertJsonPath('data.code', 'API-CUS')
            ->assertJsonPath('data.contacts.0.contact_name', 'API Contact')
            ->assertJsonPath('data.documents.0.document_type', 'id_document')
            ->assertJsonStructure(['data' => ['id', 'customer_number', 'code', 'name', 'contacts', 'addresses', 'documents', 'credit_profile']]);

        $id = (int) $create->json('data.id');
        $this->tenantGetJson($tenantId, "/api/v1/customers/lookup/active?tenant_id={$tenantId}&organization_unit_id={$organizationUnitId}")
            ->assertOk()
            ->assertJsonFragment(['code' => 'API-CUS']);

        $this->withTenantExecutionContext($tenantId, fn () => $this->putJson("/api/v1/customers/{$id}", [
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'name' => 'API Customer Updated',
        ]))->assertOk()->assertJsonPath('data.name', 'API Customer Updated');
    }

    public function test_customer_validation_error_response(): void
    {
        $this->withoutMiddleware();
        $this->trustTenantScopedRequestContextFromPayload();
        [$tenantId] = $this->scopeContext();
        $this->actingAsTenantUser($tenantId);

        $this->tenantPostJson($tenantId, '/api/v1/customers', [
            'tenant_id' => $tenantId,
            'code' => '',
            'name' => '',
            'customer_type' => 'invalid',
            'credit_limit' => '-1.000000',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['code', 'name', 'customer_type', 'credit_limit']);
    }

    public function test_database_seeder_adds_customer_master_data(): void
    {
        $this->seed(DatabaseSeeder::class);
        $tenantId = (int) DB::table('tenants')->where('code', 'AUTOERP')->value('id');

        $this->assertDatabaseHas('customer_categories', ['tenant_id' => $tenantId, 'code' => 'GENERAL']);
        $this->assertDatabaseHas('customers', ['tenant_id' => $tenantId, 'customer_number' => 'CUS-000001']);
        $this->assertSame(1, $this->withTenantExecutionContext(
            $tenantId,
            fn (): int => Customer::query()->where('tenant_id', $tenantId)->count(),
        ));
    }

    private function createCustomer(
        int $tenantId,
        string $code,
        ?string $number = null,
        CustomerStatus $status = CustomerStatus::PendingApproval,
        ?int $organizationUnitId = null,
    ): Customer {
        return $this->withTenantExecutionContext($tenantId, fn (): Customer => app(CustomerCreationService::class)->create(new CreateCustomerData(
            tenantId: $tenantId,
            organizationUnitId: $organizationUnitId,
            customerNumber: $number,
            code: $code,
            name: 'Customer '.$code,
            customerType: CustomerType::Company,
            status: $status,
        )));
    }

    private function createCategory(int $tenantId, ?int $organizationUnitId, string $code): CustomerCategory
    {
        return $this->withTenantExecutionContext($tenantId, fn (): CustomerCategory => app(CustomerCategoryService::class)->create(new CustomerCategoryData(
            tenantId: $tenantId,
            organizationUnitId: $organizationUnitId,
            code: $code,
            name: 'Category '.$code,
        )));
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
            'code' => 'TEN-CUS-'.$suffix,
            'name' => 'Customer Tenant '.$suffix,
            'slug' => 'customer-tenant-'.Str::lower($suffix).'-'.Str::lower(Str::random(3)),
            'status' => 'active',
            'status_changed_at' => now(),
            'base_currency_id' => $currencyId,
            'created_at' => now(),
            'updated_at' => now()]);
        $organizationUnitId = (int) \Tests\Support\OrganizationUnitFixture::create([
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

    private function actingAsTenantUser(int $tenantId): void
    {
        $userId = \Tests\Support\TenantUserFixture::create([
            'tenant_id' => $tenantId,
            'email' => 'customer-test-'.Str::lower(Str::random(8)).'@example.test',
        ]);

        $user = $this->withTenantExecutionContext(
            $tenantId,
            fn (): UserModel => UserModel::query()->findOrFail($userId),
        );

        $this->actingAs($user, (string) config('module-auth.protected_route_guard', 'auth-api'));
    }
}
