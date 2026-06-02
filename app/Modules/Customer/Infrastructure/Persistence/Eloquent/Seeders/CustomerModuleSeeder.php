<?php

declare(strict_types=1);

namespace Modules\Customer\Infrastructure\Persistence\Eloquent\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

final class CustomerModuleSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('customers')) {
            return;
        }

        DB::transaction(function (): void {
            $tenantId = $this->tenantId();
            $organizationUnitId = $this->organizationUnitId($tenantId);

            $fleetCategoryId = $this->seedCategory($tenantId, $organizationUnitId, 'FLEET', 'Fleet Customers');
            $retailCategoryId = $this->seedCategory($tenantId, $organizationUnitId, 'RETAIL', 'Retail Customers');

            $fleetCustomerId = $this->seedCustomer($tenantId, $organizationUnitId, [
                'category_id' => $fleetCategoryId,
                'customer_code' => 'CUS-DEMO-001',
                'customer_name' => 'Colombo Fleet Services',
                'customer_type' => 'business',
                'display_name' => 'Colombo Fleet Services',
                'email' => 'billing@colombofleet.example',
                'legal_name' => 'Colombo Fleet Services (Pvt) Ltd',
                'phone' => '+94 11 555 0101',
                'status' => 'active',
                'tax_number' => 'TIN-CUS-DEMO-001',
            ]);

            $retailCustomerId = $this->seedCustomer($tenantId, $organizationUnitId, [
                'category_id' => $retailCategoryId,
                'customer_code' => 'CUS-DEMO-002',
                'customer_name' => 'Nimal Perera',
                'customer_type' => 'individual',
                'display_name' => 'Nimal Perera',
                'email' => 'nimal.perera@example.com',
                'legal_name' => null,
                'phone' => '+94 77 555 0102',
                'status' => 'active',
                'tax_number' => null,
            ]);

            $this->seedContact($tenantId, $organizationUnitId, $fleetCustomerId, 'Anjali Fernando', 'Accounts Manager', 'accounts@colombofleet.example', '+94 77 555 1101');
            $this->seedAddress($tenantId, $organizationUnitId, $fleetCustomerId, 'billing', 'Head Office', '22 Galle Road', 'Colombo', '00300');

            $this->seedContact($tenantId, $organizationUnitId, $retailCustomerId, 'Nimal Perera', 'Owner', 'nimal.perera@example.com', '+94 77 555 0102');
            $this->seedAddress($tenantId, $organizationUnitId, $retailCustomerId, 'service', 'Service Address', '14 Lake Drive', 'Kandy', '20000');
        }, 3);
    }

    private function seedCategory(int $tenantId, int $organizationUnitId, string $code, string $name): int
    {
        DB::table('customer_categories')->updateOrInsert(
            ['tenant_id' => $tenantId, 'category_code' => $code],
            [
                'category_name' => $name,
                'description' => $name . ' used by demo customer records.',
                'is_active' => true,
                'metadata' => json_encode(['seed_source' => 'customer_module'], JSON_THROW_ON_ERROR),
                'organization_unit_id' => $organizationUnitId,
                'row_version' => 1,
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );

        return $this->requiredIdBy('customer_categories', ['tenant_id' => $tenantId, 'category_code' => $code]);
    }

    /**
     * @param array<string,mixed> $customer
     */
    private function seedCustomer(int $tenantId, int $organizationUnitId, array $customer): int
    {
        DB::table('customers')->updateOrInsert(
            ['tenant_id' => $tenantId, 'customer_code' => $customer['customer_code']],
            [
                ...$customer,
                'credit_hold' => false,
                'is_active' => $customer['status'] === 'active',
                'metadata' => json_encode(['contact_person' => $customer['display_name'], 'seed_source' => 'customer_module'], JSON_THROW_ON_ERROR),
                'organization_unit_id' => $organizationUnitId,
                'row_version' => 1,
                'tenant_id' => $tenantId,
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );

        return $this->requiredIdBy('customers', ['tenant_id' => $tenantId, 'customer_code' => $customer['customer_code']]);
    }

    private function seedContact(int $tenantId, int $organizationUnitId, int $customerId, string $name, string $designation, string $email, string $phone): void
    {
        DB::table('customer_contacts')->updateOrInsert(
            ['tenant_id' => $tenantId, 'customer_id' => $customerId, 'email' => $email],
            [
                'contact_name' => $name,
                'designation' => $designation,
                'is_active' => true,
                'is_primary' => true,
                'metadata' => json_encode(['seed_source' => 'customer_module'], JSON_THROW_ON_ERROR),
                'organization_unit_id' => $organizationUnitId,
                'phone' => $phone,
                'row_version' => 1,
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );
    }

    private function seedAddress(int $tenantId, int $organizationUnitId, int $customerId, string $type, string $label, string $line1, string $city, string $postalCode): void
    {
        DB::table('customer_addresses')->updateOrInsert(
            ['tenant_id' => $tenantId, 'customer_id' => $customerId, 'address_type' => $type, 'label' => $label],
            [
                'address_line_1' => $line1,
                'city' => $city,
                'country_name' => 'Sri Lanka',
                'is_active' => true,
                'is_primary' => true,
                'is_primary_billing' => $type === 'billing',
                'is_primary_shipping' => $type !== 'billing',
                'metadata' => json_encode(['seed_source' => 'customer_module'], JSON_THROW_ON_ERROR),
                'organization_unit_id' => $organizationUnitId,
                'postal_code' => $postalCode,
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
            throw new RuntimeException('Default tenant must be seeded before customer module data.');
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
            throw new RuntimeException('Default organization unit must be seeded before customer module data.');
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
