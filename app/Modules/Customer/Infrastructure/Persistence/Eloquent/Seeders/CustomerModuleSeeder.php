<?php

declare(strict_types=1);

namespace Modules\Customer\Infrastructure\Persistence\Eloquent\Seeders;

use Database\Seeders\Concerns\SeedsAutoErpData;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class CustomerModuleSeeder extends Seeder
{
    use SeedsAutoErpData;

    public function run(): void
    {
        if (! Schema::hasTable('customers')) {
            return;
        }

        $tenantId = $this->defaultTenantId();
        if ($tenantId === null) {
            return;
        }

        DB::transaction(function () use ($tenantId): void {
            $organizationUnitId = $this->defaultOrganizationUnitId($tenantId);
            $userId = $this->defaultUserId($tenantId);
            $businessCategoryId = $this->ensureCategory($tenantId, $organizationUnitId, 'BUSINESS', 'Business Accounts');
            $fleetCategoryId = $this->ensureCategory($tenantId, $organizationUnitId, 'FLEET', 'Fleet Customers', $businessCategoryId);
            $retailCategoryId = $this->ensureCategory($tenantId, $organizationUnitId, 'RETAIL', 'Retail Customers');
            $inactiveCategoryId = $this->ensureCategory($tenantId, $organizationUnitId, 'INACTIVE_SEGMENT', 'Inactive Segment', null, false);

            $this->seedCustomer($tenantId, $organizationUnitId, $userId, [
                'customer_code' => 'CUST-DEMO-FLEET',
                'customer_name' => 'Northwind Fleet Services',
                'legal_name' => 'Northwind Fleet Services Pvt Ltd',
                'display_name' => 'Northwind Fleet',
                'customer_type' => 'business',
                'category_id' => $fleetCategoryId,
                'registration_number' => 'PV-DEMO-1001',
                'tax_number' => 'TIN-CUST-1001',
                'vat_number' => 'VAT-CUST-1001',
                'email' => 'accounts@northwindfleet.example',
                'phone' => '+94 11 555 0101',
                'mobile' => '+94 77 555 0101',
                'website' => 'https://northwindfleet.example',
                'credit_limit' => '250000.0000',
                'credit_days' => 30,
                'credit_hold' => false,
                'status' => 'active',
                'is_active' => true,
                'notes' => 'Demo customer with approved credit, service, sales, rental, and invoice history.',
            ], [
                'contact_name' => 'Maya Fernando',
                'designation' => 'Fleet Operations Manager',
                'department' => 'Operations',
                'email' => 'maya.fernando@northwindfleet.example',
                'phone' => '+94 11 555 0102',
                'mobile' => '+94 77 555 0102',
            ]);

            $this->seedCustomer($tenantId, $organizationUnitId, $userId, [
                'customer_code' => 'CUST-DEMO-RETAIL',
                'customer_name' => 'Avery Perera',
                'legal_name' => null,
                'display_name' => 'Avery Perera',
                'customer_type' => 'individual',
                'category_id' => $retailCategoryId,
                'registration_number' => 'NIC-DEMO-2001',
                'tax_number' => null,
                'vat_number' => null,
                'email' => 'avery.perera@example.test',
                'phone' => null,
                'mobile' => '+94 77 555 0201',
                'website' => null,
                'credit_limit' => '0.0000',
                'credit_days' => 0,
                'credit_hold' => false,
                'status' => 'active',
                'is_active' => true,
                'notes' => 'Cash customer for retail sales and walk-in workflows.',
            ], [
                'contact_name' => 'Avery Perera',
                'designation' => null,
                'department' => null,
                'email' => 'avery.perera@example.test',
                'phone' => null,
                'mobile' => '+94 77 555 0201',
            ]);

            $this->seedCustomer($tenantId, $organizationUnitId, $userId, [
                'customer_code' => 'CUST-DEMO-HOLD',
                'customer_name' => 'Contoso Credit Hold',
                'legal_name' => 'Contoso Credit Hold Ltd',
                'display_name' => 'Contoso Hold',
                'customer_type' => 'business',
                'category_id' => $businessCategoryId,
                'registration_number' => 'PV-DEMO-1002',
                'tax_number' => 'TIN-CUST-1002',
                'vat_number' => 'VAT-CUST-1002',
                'email' => 'ap@contosohold.example',
                'phone' => '+94 11 555 0301',
                'mobile' => '+94 77 555 0301',
                'website' => null,
                'credit_limit' => '50000.0000',
                'credit_days' => 15,
                'credit_hold' => true,
                'credit_hold_by' => $userId,
                'credit_hold_at' => '2026-03-16 10:00:00',
                'blocked_by' => $userId,
                'blocked_at' => '2026-03-16 10:00:00',
                'status' => 'blocked',
                'is_active' => false,
                'notes' => 'Negative test customer for credit hold and blocked account workflows.',
            ], [
                'contact_name' => 'Jordan Silva',
                'designation' => 'Accounts Payable Lead',
                'department' => 'Finance',
                'email' => 'jordan.silva@contosohold.example',
                'phone' => '+94 11 555 0302',
                'mobile' => '+94 77 555 0302',
            ]);

            $this->seedCustomer($tenantId, $organizationUnitId, $userId, [
                'customer_code' => 'CUST-DEMO-INACTIVE',
                'customer_name' => 'Legacy Archived Customer',
                'legal_name' => 'Legacy Archived Customer Ltd',
                'display_name' => 'Legacy Archived',
                'customer_type' => 'business',
                'category_id' => $inactiveCategoryId,
                'registration_number' => 'PV-DEMO-9001',
                'tax_number' => null,
                'vat_number' => null,
                'email' => 'legacy.customer@example.test',
                'phone' => '+94 11 555 0901',
                'mobile' => null,
                'website' => null,
                'credit_limit' => '0.0000',
                'credit_days' => 0,
                'credit_hold' => false,
                'deactivated_by' => $userId,
                'deactivated_at' => '2026-01-31 17:00:00',
                'status' => 'inactive',
                'is_active' => false,
                'notes' => 'Inactive customer retained for list filters, restore, and historical references.',
            ], [
                'contact_name' => 'Legacy Contact',
                'designation' => 'Former Buyer',
                'department' => 'Procurement',
                'email' => 'legacy.contact@example.test',
                'phone' => '+94 11 555 0902',
                'mobile' => null,
            ]);
        }, 3);
    }

    private function ensureCategory(
        int $tenantId,
        ?int $organizationUnitId,
        string $code,
        string $name,
        ?int $parentId = null,
        bool $active = true,
    ): int {
        $this->upsert('customer_categories', [
            'tenant_id' => $tenantId,
            'category_code' => $code,
        ], [
            'organization_unit_id' => $organizationUnitId,
            'metadata' => $this->seedMetadata('customer_module'),
            'category_name' => $name,
            'parent_id' => $parentId,
            'description' => $active ? 'Seeded customer segment.' : 'Inactive customer segment for filter testing.',
            'is_active' => $active,
        ]);

        return (int) DB::table('customer_categories')
            ->where('tenant_id', $tenantId)
            ->where('category_code', $code)
            ->value('id');
    }

    /**
     * @param  array<string, mixed>  $customer
     * @param  array<string, mixed>  $contact
     */
    private function seedCustomer(int $tenantId, ?int $organizationUnitId, ?int $userId, array $customer, array $contact): void
    {
        $this->upsert('customers', [
            'tenant_id' => $tenantId,
            'customer_code' => $customer['customer_code'],
        ], $customer + [
            'organization_unit_id' => $organizationUnitId,
            'metadata' => $this->seedMetadata('customer_module', (string) $customer['status']),
            'default_currency_id' => $this->currencyId(),
            'default_payment_term_id' => $this->paymentTermId($tenantId),
            'default_receivable_account_id' => $this->accountId($tenantId, '1100'),
            'default_income_account_id' => $this->accountId($tenantId, '4000'),
            'created_by' => $userId,
            'updated_by' => $userId,
            'activated_by' => $customer['status'] === 'active' ? $userId : null,
            'activated_at' => $customer['status'] === 'active' ? '2026-01-15 09:00:00' : null,
        ]);

        $customerId = (int) DB::table('customers')
            ->where('tenant_id', $tenantId)
            ->where('customer_code', $customer['customer_code'])
            ->value('id');

        if ($customerId < 1) {
            return;
        }

        $this->seedContact($tenantId, $organizationUnitId, $customerId, $userId, $contact, (bool) $customer['is_active']);
        $this->seedAddress($tenantId, $organizationUnitId, $customerId, $userId, $contact, (bool) $customer['is_active']);
        $this->seedCreditProfile($tenantId, $organizationUnitId, $customerId, $userId, $customer);
        $this->seedTaxProfile($tenantId, $organizationUnitId, $customerId, $userId, $customer);
        $this->seedStatusHistory($tenantId, $organizationUnitId, $customerId, $userId, $customer);

        if ($userId !== null && $customer['customer_code'] === 'CUST-DEMO-FLEET') {
            $this->upsert('customer_user_accounts', [
                'tenant_id' => $tenantId,
                'customer_id' => $customerId,
                'user_id' => $userId,
            ], [
                'organization_unit_id' => $organizationUnitId,
                'metadata' => $this->seedMetadata('customer_module', 'portal_link'),
                'access_role' => 'customer_portal_admin',
                'is_primary' => true,
                'access_status' => 'active',
                'invited_at' => '2026-01-15 09:00:00',
                'activated_at' => '2026-01-15 10:00:00',
                'linked_user_by' => $userId,
                'invited_by' => $userId,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $contact
     */
    private function seedContact(
        int $tenantId,
        ?int $organizationUnitId,
        int $customerId,
        ?int $userId,
        array $contact,
        bool $active,
    ): void {
        $this->upsert('customer_contacts', [
            'tenant_id' => $tenantId,
            'customer_id' => $customerId,
            'contact_name' => $contact['contact_name'],
        ], $contact + [
            'organization_unit_id' => $organizationUnitId,
            'metadata' => $this->seedMetadata('customer_module', 'contact'),
            'is_primary' => true,
            'is_active' => $active,
            'notes' => 'Seeded customer contact.',
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);
    }

    /**
     * @param  array<string, mixed>  $contact
     */
    private function seedAddress(
        int $tenantId,
        ?int $organizationUnitId,
        int $customerId,
        ?int $userId,
        array $contact,
        bool $active,
    ): void {
        $this->upsert('customer_addresses', [
            'tenant_id' => $tenantId,
            'customer_id' => $customerId,
            'address_type' => 'billing',
            'label' => 'Primary Billing',
        ], [
            'organization_unit_id' => $organizationUnitId,
            'metadata' => $this->seedMetadata('customer_module', 'billing_address'),
            'contact_person' => $contact['contact_name'],
            'contact_phone' => $contact['mobile'] ?? $contact['phone'],
            'address_line_1' => '42 Demo Avenue',
            'address_line_2' => 'Business District',
            'city' => 'Colombo',
            'state_province' => 'Western',
            'postal_code' => '00100',
            'country_id' => null,
            'country_name' => 'Sri Lanka',
            'is_primary' => true,
            'is_primary_billing' => true,
            'is_primary_shipping' => false,
            'is_active' => $active,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);
    }

    /**
     * @param  array<string, mixed>  $customer
     */
    private function seedCreditProfile(
        int $tenantId,
        ?int $organizationUnitId,
        int $customerId,
        ?int $userId,
        array $customer,
    ): void {
        $this->upsert('customer_credit_profiles', [
            'tenant_id' => $tenantId,
            'customer_id' => $customerId,
        ], [
            'organization_unit_id' => $organizationUnitId,
            'metadata' => $this->seedMetadata('customer_module', 'credit_profile'),
            'credit_limit' => $customer['credit_limit'],
            'credit_days' => $customer['credit_days'],
            'credit_hold' => $customer['credit_hold'],
            'credit_hold_reason' => $customer['credit_hold'] ? 'Overdue balance exceeds credit policy threshold.' : null,
            'allow_credit_override' => ! (bool) $customer['credit_hold'],
            'credit_hold_by' => $customer['credit_hold'] ? $userId : null,
            'credit_hold_at' => $customer['credit_hold'] ? '2026-03-16 10:00:00' : null,
            'is_active' => (bool) $customer['is_active'],
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);
    }

    /**
     * @param  array<string, mixed>  $customer
     */
    private function seedTaxProfile(
        int $tenantId,
        ?int $organizationUnitId,
        int $customerId,
        ?int $userId,
        array $customer,
    ): void {
        $this->upsert('customer_tax_profiles', [
            'tenant_id' => $tenantId,
            'customer_id' => $customerId,
        ], [
            'organization_unit_id' => $organizationUnitId,
            'metadata' => $this->seedMetadata('customer_module', 'tax_profile'),
            'tax_registration_number' => $customer['tax_number'],
            'vat_number' => $customer['vat_number'],
            'tax_group_id' => $this->taxGroupId($tenantId),
            'tax_exempt' => $customer['customer_type'] === 'individual',
            'exemption_certificate_reference' => $customer['customer_type'] === 'individual' ? 'PERSONAL-CASH-CUSTOMER' : null,
            'valid_from' => '2026-01-01',
            'valid_to' => null,
            'is_active' => (bool) $customer['is_active'],
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);
    }

    /**
     * @param  array<string, mixed>  $customer
     */
    private function seedStatusHistory(
        int $tenantId,
        ?int $organizationUnitId,
        int $customerId,
        ?int $userId,
        array $customer,
    ): void {
        $this->upsert('customer_status_histories', [
            'tenant_id' => $tenantId,
            'customer_id' => $customerId,
            'to_status' => 'draft',
            'reason' => 'Seeded onboarding draft.',
        ], [
            'organization_unit_id' => $organizationUnitId,
            'metadata' => $this->seedMetadata('customer_module', 'status_history'),
            'from_status' => null,
            'changed_by' => $userId,
            'changed_at' => '2026-01-10 09:00:00',
        ]);

        $this->upsert('customer_status_histories', [
            'tenant_id' => $tenantId,
            'customer_id' => $customerId,
            'to_status' => $customer['status'],
            'reason' => 'Seeded target lifecycle state.',
        ], [
            'organization_unit_id' => $organizationUnitId,
            'metadata' => $this->seedMetadata('customer_module', 'status_history'),
            'from_status' => 'draft',
            'changed_by' => $userId,
            'changed_at' => match ($customer['status']) {
                'blocked' => '2026-03-16 10:00:00',
                'inactive' => '2026-01-31 17:00:00',
                default => '2026-01-15 09:00:00',
            },
        ]);
    }
}
