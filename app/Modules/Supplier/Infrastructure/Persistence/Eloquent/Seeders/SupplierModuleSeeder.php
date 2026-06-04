<?php

declare(strict_types=1);

namespace Modules\Supplier\Infrastructure\Persistence\Eloquent\Seeders;

use Database\Seeders\Concerns\SeedsAutoErpData;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class SupplierModuleSeeder extends Seeder
{
    use SeedsAutoErpData;

    public function run(): void
    {
        if (! Schema::hasTable('suppliers')) {
            return;
        }

        $tenantId = $this->defaultTenantId();
        if ($tenantId === null) {
            return;
        }

        DB::transaction(function () use ($tenantId): void {
            $organizationUnitId = $this->defaultOrganizationUnitId($tenantId);
            $userId = $this->defaultUserId($tenantId);
            $partsCategoryId = $this->ensureCategory($tenantId, $organizationUnitId, 'PARTS', 'Parts Suppliers');
            $serviceCategoryId = $this->ensureCategory($tenantId, $organizationUnitId, 'SERVICE', 'Service Providers');
            $inactiveCategoryId = $this->ensureCategory($tenantId, $organizationUnitId, 'LEGACY', 'Legacy Suppliers', false);

            $this->seedSupplier($tenantId, $organizationUnitId, $userId, [
                'supplier_code' => 'SUP-DEMO-PARTS',
                'supplier_name' => 'Global Parts Lanka',
                'legal_name' => 'Global Parts Lanka Pvt Ltd',
                'display_name' => 'Global Parts',
                'supplier_type' => 'business',
                'category_id' => $partsCategoryId,
                'registration_number' => 'PV-SUP-1001',
                'tax_number' => 'TIN-SUP-1001',
                'vat_number' => 'VAT-SUP-1001',
                'email' => 'sales@globalpartslanka.example',
                'phone' => '+94 11 555 1101',
                'mobile' => '+94 77 555 1101',
                'website' => 'https://globalpartslanka.example',
                'credit_limit' => '150000.0000',
                'status' => 'active',
                'is_active' => true,
                'notes' => 'Preferred parts supplier for purchase, GRN, AP, and invoice workflows.',
            ], [
                'name' => 'Nuwan Jayasinghe',
                'designation' => 'Account Manager',
                'department' => 'Sales',
                'email' => 'nuwan.jayasinghe@globalpartslanka.example',
                'phone' => '+94 11 555 1102',
                'mobile' => '+94 77 555 1102',
                'whatsapp' => '+94 77 555 1102',
            ], true);

            $this->seedSupplier($tenantId, $organizationUnitId, $userId, [
                'supplier_code' => 'SUP-DEMO-SERVICE',
                'supplier_name' => 'City Diagnostic Partners',
                'legal_name' => 'City Diagnostic Partners Ltd',
                'display_name' => 'City Diagnostics',
                'supplier_type' => 'business',
                'category_id' => $serviceCategoryId,
                'registration_number' => 'PV-SUP-2001',
                'tax_number' => 'TIN-SUP-2001',
                'vat_number' => 'VAT-SUP-2001',
                'email' => 'operations@citydiagnostics.example',
                'phone' => '+94 11 555 1201',
                'mobile' => '+94 77 555 1201',
                'website' => null,
                'credit_limit' => '75000.0000',
                'status' => 'active',
                'is_active' => true,
                'notes' => 'External service provider for vehicle service outsourcing.',
            ], [
                'name' => 'Samira Gamage',
                'designation' => 'Service Coordinator',
                'department' => 'Operations',
                'email' => 'samira.gamage@citydiagnostics.example',
                'phone' => '+94 11 555 1202',
                'mobile' => '+94 77 555 1202',
                'whatsapp' => '+94 77 555 1202',
            ], false);

            $this->seedSupplier($tenantId, $organizationUnitId, $userId, [
                'supplier_code' => 'SUP-DEMO-BLOCKED',
                'supplier_name' => 'Blocked Supplier Example',
                'legal_name' => 'Blocked Supplier Example Ltd',
                'display_name' => 'Blocked Supplier',
                'supplier_type' => 'business',
                'category_id' => $partsCategoryId,
                'registration_number' => 'PV-SUP-3001',
                'tax_number' => null,
                'vat_number' => null,
                'email' => 'blocked.supplier@example.test',
                'phone' => '+94 11 555 1301',
                'mobile' => null,
                'website' => null,
                'credit_limit' => '0.0000',
                'blocked_by' => $userId,
                'blocked_at' => '2026-03-18 11:00:00',
                'status' => 'blocked',
                'is_active' => false,
                'notes' => 'Negative test supplier for blocked procurement workflows.',
            ], [
                'name' => 'Blocked Contact',
                'designation' => 'Former Account Manager',
                'department' => 'Sales',
                'email' => 'blocked.contact@example.test',
                'phone' => '+94 11 555 1302',
                'mobile' => null,
                'whatsapp' => null,
            ], false);

            $this->seedSupplier($tenantId, $organizationUnitId, $userId, [
                'supplier_code' => 'SUP-DEMO-INACTIVE',
                'supplier_name' => 'Legacy Supplier Archive',
                'legal_name' => 'Legacy Supplier Archive Ltd',
                'display_name' => 'Legacy Supplier',
                'supplier_type' => 'business',
                'category_id' => $inactiveCategoryId,
                'registration_number' => 'PV-SUP-9001',
                'tax_number' => null,
                'vat_number' => null,
                'email' => 'legacy.supplier@example.test',
                'phone' => '+94 11 555 1901',
                'mobile' => null,
                'website' => null,
                'credit_limit' => '0.0000',
                'deactivated_by' => $userId,
                'deactivated_at' => '2026-01-31 17:30:00',
                'status' => 'inactive',
                'is_active' => false,
                'notes' => 'Inactive supplier retained for historical filters and relationship tests.',
            ], [
                'name' => 'Legacy Supplier Contact',
                'designation' => 'Former Coordinator',
                'department' => 'Procurement',
                'email' => 'legacy.supplier.contact@example.test',
                'phone' => '+94 11 555 1902',
                'mobile' => null,
                'whatsapp' => null,
            ], false);
        }, 3);
    }

    private function ensureCategory(int $tenantId, ?int $organizationUnitId, string $code, string $name, bool $active = true): int
    {
        $this->upsert('supplier_categories', [
            'tenant_id' => $tenantId,
            'code' => $code,
        ], [
            'organization_unit_id' => $organizationUnitId,
            'metadata' => $this->seedMetadata('supplier_module'),
            'name' => $name,
            'description' => $active ? 'Seeded supplier category.' : 'Inactive supplier category for filter testing.',
            'is_active' => $active,
        ]);

        return (int) DB::table('supplier_categories')
            ->where('tenant_id', $tenantId)
            ->where('code', $code)
            ->value('id');
    }

    /**
     * @param  array<string, mixed>  $supplier
     * @param  array<string, mixed>  $contact
     */
    private function seedSupplier(
        int $tenantId,
        ?int $organizationUnitId,
        ?int $userId,
        array $supplier,
        array $contact,
        bool $preferredForItems,
    ): void {
        $this->upsert('suppliers', [
            'tenant_id' => $tenantId,
            'supplier_code' => $supplier['supplier_code'],
        ], $supplier + [
            'organization_unit_id' => $organizationUnitId,
            'metadata' => $this->seedMetadata('supplier_module', (string) $supplier['status']),
            'default_currency_id' => $this->currencyId(),
            'default_payment_term_id' => $this->paymentTermId($tenantId),
            'default_payable_account_id' => $this->accountId($tenantId, '2000'),
            'default_expense_account_id' => $this->accountId($tenantId, '5000'),
            'created_by' => $userId,
            'updated_by' => $userId,
            'activated_by' => $supplier['status'] === 'active' ? $userId : null,
            'activated_at' => $supplier['status'] === 'active' ? '2026-01-16 09:00:00' : null,
        ]);

        $supplierId = (int) DB::table('suppliers')
            ->where('tenant_id', $tenantId)
            ->where('supplier_code', $supplier['supplier_code'])
            ->value('id');

        if ($supplierId < 1) {
            return;
        }

        $active = (bool) $supplier['is_active'];
        $this->seedContact($tenantId, $organizationUnitId, $supplierId, $userId, $contact, $active);
        $this->seedAddress($tenantId, $organizationUnitId, $supplierId, $userId, $contact, $active);
        $this->seedTaxProfile($tenantId, $organizationUnitId, $supplierId, $userId, $supplier);
        $this->seedBankAccount($tenantId, $organizationUnitId, $supplierId, $userId, $supplier);
        $this->seedSupplierItem($tenantId, $organizationUnitId, $supplierId, $preferredForItems);
        $this->seedStatusHistory($tenantId, $organizationUnitId, $supplierId, $userId, $supplier);

        if ($userId !== null && $supplier['supplier_code'] === 'SUP-DEMO-PARTS') {
            $this->upsert('supplier_user_accounts', [
                'tenant_id' => $tenantId,
                'supplier_id' => $supplierId,
                'user_id' => $userId,
            ], [
                'organization_unit_id' => $organizationUnitId,
                'metadata' => $this->seedMetadata('supplier_module', 'portal_link'),
                'access_type' => 'portal',
                'status' => 'active',
                'is_primary' => true,
                'linked_at' => '2026-01-16 09:30:00',
                'linked_by' => $userId,
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $contact
     */
    private function seedContact(
        int $tenantId,
        ?int $organizationUnitId,
        int $supplierId,
        ?int $userId,
        array $contact,
        bool $active,
    ): void {
        $this->upsert('supplier_contacts', [
            'supplier_id' => $supplierId,
            'email' => $contact['email'],
        ], $contact + [
            'tenant_id' => $tenantId,
            'organization_unit_id' => $organizationUnitId,
            'metadata' => $this->seedMetadata('supplier_module', 'contact'),
            'is_billing_contact' => true,
            'is_procurement_contact' => true,
            'is_primary' => true,
            'is_active' => $active,
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
        int $supplierId,
        ?int $userId,
        array $contact,
        bool $active,
    ): void {
        $this->upsert('supplier_addresses', [
            'tenant_id' => $tenantId,
            'supplier_id' => $supplierId,
            'type' => 'billing',
            'label' => 'Primary Billing',
        ], [
            'organization_unit_id' => $organizationUnitId,
            'metadata' => $this->seedMetadata('supplier_module', 'billing_address'),
            'contact_person' => $contact['name'],
            'contact_phone' => $contact['mobile'] ?? $contact['phone'],
            'address_line1' => '77 Supplier Road',
            'address_line2' => 'Industrial Zone',
            'city' => 'Colombo',
            'state' => 'Western',
            'postal_code' => '01000',
            'country_id' => null,
            'is_default' => true,
            'is_default_billing' => true,
            'is_default_shipping' => false,
            'is_active' => $active,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);
    }

    /**
     * @param  array<string, mixed>  $supplier
     */
    private function seedTaxProfile(
        int $tenantId,
        ?int $organizationUnitId,
        int $supplierId,
        ?int $userId,
        array $supplier,
    ): void {
        $this->upsert('supplier_tax_profiles', [
            'tenant_id' => $tenantId,
            'supplier_id' => $supplierId,
        ], [
            'organization_unit_id' => $organizationUnitId,
            'metadata' => $this->seedMetadata('supplier_module', 'tax_profile'),
            'tax_identifier' => $supplier['tax_number'],
            'vat_identifier' => $supplier['vat_number'],
            'tax_type' => $supplier['vat_number'] === null ? 'non_registered' : 'vat_registered',
            'withholding_rate' => $supplier['status'] === 'active' ? '5.0000' : null,
            'is_tax_exempt' => false,
            'tax_exempt_until' => null,
            'is_active' => (bool) $supplier['is_active'],
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);
    }

    /**
     * @param  array<string, mixed>  $supplier
     */
    private function seedBankAccount(
        int $tenantId,
        ?int $organizationUnitId,
        int $supplierId,
        ?int $userId,
        array $supplier,
    ): void {
        $accountNumber = 'SUP-'.str_replace('SUP-DEMO-', '', (string) $supplier['supplier_code']).'-001';
        $this->upsert('supplier_bank_accounts', [
            'tenant_id' => $tenantId,
            'supplier_id' => $supplierId,
            'account_number' => $accountNumber,
        ], [
            'organization_unit_id' => $organizationUnitId,
            'metadata' => $this->seedMetadata('supplier_module', 'bank_account'),
            'account_name' => $supplier['legal_name'] ?? $supplier['supplier_name'],
            'iban' => null,
            'swift_code' => 'DEMOCLKX',
            'bank_name' => 'Demo Commercial Bank',
            'branch_name' => 'Colombo',
            'bank_code' => '9999',
            'branch_code' => '001',
            'currency_id' => $this->currencyId(),
            'is_primary' => true,
            'is_active' => (bool) $supplier['is_active'],
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);
    }

    private function seedSupplierItem(int $tenantId, ?int $organizationUnitId, int $supplierId, bool $preferred): void
    {
        if (! Schema::hasTable('items') || ! Schema::hasTable('supplier_items')) {
            return;
        }

        $itemId = $this->idBy('items', ['tenant_id' => $tenantId, 'sku' => 'ITM-FILTER-001'])
            ?? DB::table('items')->where('tenant_id', $tenantId)->where('is_purchasable', true)->orderBy('id')->value('id');

        if ($itemId === null) {
            return;
        }

        $this->upsert('supplier_items', [
            'tenant_id' => $tenantId,
            'supplier_id' => $supplierId,
            'item_id' => (int) $itemId,
            'variant_id' => null,
        ], [
            'organization_unit_id' => $organizationUnitId,
            'metadata' => $this->seedMetadata('supplier_module', 'supplier_item'),
            'supplier_sku' => $preferred ? 'GP-OIL-FILTER-STD' : 'SUP-SVC-REF',
            'lead_time_days' => $preferred ? 5 : 10,
            'min_order_qty' => $preferred ? '6.0000' : '1.0000',
            'is_preferred' => $preferred,
            'last_observed_unit_cost' => $preferred ? '12500.0000' : null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $supplier
     */
    private function seedStatusHistory(
        int $tenantId,
        ?int $organizationUnitId,
        int $supplierId,
        ?int $userId,
        array $supplier,
    ): void {
        $this->upsert('supplier_status_histories', [
            'tenant_id' => $tenantId,
            'supplier_id' => $supplierId,
            'to_status' => 'draft',
            'reason' => 'Seeded onboarding draft.',
        ], [
            'organization_unit_id' => $organizationUnitId,
            'metadata' => $this->seedMetadata('supplier_module', 'status_history'),
            'from_status' => null,
            'changed_by' => $userId,
            'changed_at' => '2026-01-11 09:00:00',
        ]);

        $this->upsert('supplier_status_histories', [
            'tenant_id' => $tenantId,
            'supplier_id' => $supplierId,
            'to_status' => $supplier['status'],
            'reason' => 'Seeded target lifecycle state.',
        ], [
            'organization_unit_id' => $organizationUnitId,
            'metadata' => $this->seedMetadata('supplier_module', 'status_history'),
            'from_status' => 'draft',
            'changed_by' => $userId,
            'changed_at' => match ($supplier['status']) {
                'blocked' => '2026-03-18 11:00:00',
                'inactive' => '2026-01-31 17:30:00',
                default => '2026-01-16 09:00:00',
            },
        ]);
    }
}
