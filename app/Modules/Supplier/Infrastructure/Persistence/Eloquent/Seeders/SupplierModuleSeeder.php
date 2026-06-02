<?php

declare(strict_types=1);

namespace Modules\Supplier\Infrastructure\Persistence\Eloquent\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

final class SupplierModuleSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('suppliers')) {
            return;
        }

        DB::transaction(function (): void {
            $tenantId = $this->tenantId();
            $organizationUnitId = $this->organizationUnitId($tenantId);

            $fleetProviderCategoryId = $this->seedCategory($tenantId, $organizationUnitId, 'FLEET_PROVIDER', 'Fleet Providers');
            $partsCategoryId = $this->seedCategory($tenantId, $organizationUnitId, 'PARTS', 'Parts Suppliers');

            $providerId = $this->seedSupplier($tenantId, $organizationUnitId, [
                'category_id' => $fleetProviderCategoryId,
                'display_name' => 'Lanka Fleet Providers',
                'email' => 'operations@lankafleet.example',
                'legal_name' => 'Lanka Fleet Providers (Pvt) Ltd',
                'phone' => '+94 11 555 0201',
                'registration_number' => 'SUP-DEMO-REG-001',
                'status' => 'active',
                'supplier_code' => 'SUP-DEMO-001',
                'supplier_name' => 'Lanka Fleet Providers',
                'supplier_type' => 'fleet_provider',
                'tax_number' => 'TIN-SUP-DEMO-001',
                'vat_number' => 'VAT-SUP-DEMO-001',
            ]);

            $partsSupplierId = $this->seedSupplier($tenantId, $organizationUnitId, [
                'category_id' => $partsCategoryId,
                'display_name' => 'Auto Parts Lanka',
                'email' => 'sales@autopartslanka.example',
                'legal_name' => 'Auto Parts Lanka (Pvt) Ltd',
                'phone' => '+94 11 555 0202',
                'registration_number' => 'SUP-DEMO-REG-002',
                'status' => 'active',
                'supplier_code' => 'SUP-DEMO-002',
                'supplier_name' => 'Auto Parts Lanka',
                'supplier_type' => 'business',
                'tax_number' => 'TIN-SUP-DEMO-002',
                'vat_number' => null,
            ]);

            $this->seedContact($tenantId, $organizationUnitId, $providerId, 'Sajith Jayawardena', 'Fleet Coordinator', 'sajith@lankafleet.example', '+94 77 555 2201');
            $this->seedAddress($tenantId, $organizationUnitId, $providerId, 'office', 'Provider Office', '44 Marine Drive', 'Colombo', '00400');
            $this->seedBankAccount($tenantId, $organizationUnitId, $providerId, 'Lanka Fleet Providers', '1002003001', 'Commercial Bank', 'Colombo');

            $this->seedContact($tenantId, $organizationUnitId, $partsSupplierId, 'Malini Silva', 'Sales Manager', 'malini@autopartslanka.example', '+94 77 555 2202');
            $this->seedAddress($tenantId, $organizationUnitId, $partsSupplierId, 'warehouse', 'Main Warehouse', '8 Industrial Zone', 'Kelaniya', '11600');
        }, 3);
    }

    private function seedCategory(int $tenantId, int $organizationUnitId, string $code, string $name): int
    {
        DB::table('supplier_categories')->updateOrInsert(
            ['tenant_id' => $tenantId, 'code' => $code],
            [
                'description' => $name . ' used by demo supplier records.',
                'is_active' => true,
                'metadata' => json_encode(['seed_source' => 'supplier_module'], JSON_THROW_ON_ERROR),
                'name' => $name,
                'organization_unit_id' => $organizationUnitId,
                'row_version' => 1,
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );

        return $this->requiredIdBy('supplier_categories', ['tenant_id' => $tenantId, 'code' => $code]);
    }

    /**
     * @param array<string,mixed> $supplier
     */
    private function seedSupplier(int $tenantId, int $organizationUnitId, array $supplier): int
    {
        DB::table('suppliers')->updateOrInsert(
            ['tenant_id' => $tenantId, 'supplier_code' => $supplier['supplier_code']],
            [
                ...$supplier,
                'is_active' => $supplier['status'] === 'active',
                'metadata' => json_encode(['seed_source' => 'supplier_module'], JSON_THROW_ON_ERROR),
                'organization_unit_id' => $organizationUnitId,
                'row_version' => 1,
                'tenant_id' => $tenantId,
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );

        return $this->requiredIdBy('suppliers', ['tenant_id' => $tenantId, 'supplier_code' => $supplier['supplier_code']]);
    }

    private function seedContact(int $tenantId, int $organizationUnitId, int $supplierId, string $name, string $designation, string $email, string $phone): void
    {
        DB::table('supplier_contacts')->updateOrInsert(
            ['supplier_id' => $supplierId, 'email' => $email],
            [
                'designation' => $designation,
                'is_active' => true,
                'is_primary' => true,
                'metadata' => json_encode(['seed_source' => 'supplier_module'], JSON_THROW_ON_ERROR),
                'name' => $name,
                'organization_unit_id' => $organizationUnitId,
                'phone' => $phone,
                'row_version' => 1,
                'tenant_id' => $tenantId,
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );
    }

    private function seedAddress(int $tenantId, int $organizationUnitId, int $supplierId, string $type, string $label, string $line1, string $city, string $postalCode): void
    {
        DB::table('supplier_addresses')->updateOrInsert(
            ['tenant_id' => $tenantId, 'supplier_id' => $supplierId, 'type' => $type, 'label' => $label],
            [
                'address_line1' => $line1,
                'city' => $city,
                'is_active' => true,
                'is_default' => true,
                'is_default_billing' => $type !== 'warehouse',
                'is_default_shipping' => $type === 'warehouse',
                'metadata' => json_encode(['seed_source' => 'supplier_module'], JSON_THROW_ON_ERROR),
                'organization_unit_id' => $organizationUnitId,
                'postal_code' => $postalCode,
                'row_version' => 1,
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );
    }

    private function seedBankAccount(int $tenantId, int $organizationUnitId, int $supplierId, string $accountName, string $accountNumber, string $bankName, string $branchName): void
    {
        if (! Schema::hasTable('supplier_bank_accounts')) {
            return;
        }

        DB::table('supplier_bank_accounts')->updateOrInsert(
            ['tenant_id' => $tenantId, 'supplier_id' => $supplierId, 'account_number' => $accountNumber],
            [
                'account_name' => $accountName,
                'bank_name' => $bankName,
                'branch_name' => $branchName,
                'is_active' => true,
                'is_primary' => true,
                'metadata' => json_encode(['seed_source' => 'supplier_module'], JSON_THROW_ON_ERROR),
                'organization_unit_id' => $organizationUnitId,
                'row_version' => 1,
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );
    }

    private function tenantId(): int
    {
        $id = DB::table('tenants')->where('code', strtoupper((string) env('AUTH_LOCAL_TENANT_CODE', 'AUTOERP')))->value('id');

        if ($id === null) {
            throw new RuntimeException('Default tenant must be seeded before supplier module data.');
        }

        return (int) $id;
    }

    private function organizationUnitId(int $tenantId): int
    {
        $id = DB::table('organization_units')
            ->where('tenant_id', $tenantId)
            ->where('code', strtoupper((string) env('AUTH_LOCAL_ORGANIZATION_UNIT_CODE', 'MAIN')))
            ->value('id');

        if ($id === null) {
            throw new RuntimeException('Default organization unit must be seeded before supplier module data.');
        }

        return (int) $id;
    }

    /**
     * @param array<string,mixed> $criteria
     */
    private function requiredIdBy(string $table, array $criteria): int
    {
        $query = DB::table($table);
        foreach ($criteria as $column => $value) {
            $query->where($column, $value);
        }

        $id = $query->value('id');
        if ($id === null) {
            throw new RuntimeException('Failed to resolve seeded id from [' . $table . '].');
        }

        return (int) $id;
    }
}
