<?php

declare(strict_types=1);

namespace Modules\Customer\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Database\Seeders\Concerns\ResolvesSeedContext;
use Modules\Customer\Models\Customer;
use Modules\Customer\Models\CustomerCategory;
use Modules\Customer\Models\CustomerCategoryAssignment;
use Modules\Customer\Services\CustomerAuthorizationService;

final class CustomerSeeder extends Seeder
{
    use ResolvesSeedContext;

    public function run(): void
    {
        if (! Schema::hasTable('customers')) {
            return;
        }

        $this->seedPermissions();

        $tenant = $this->defaultTenant();
        $organizationUnit = $this->defaultOrganizationUnit($tenant);
        if ($tenant === null) {
            return;
        }

        DB::transaction(function () use ($tenant, $organizationUnit): void {
            $category = CustomerCategory::query()->updateOrCreate(
                ['tenant_id' => $tenant->getKey(), 'code' => 'GENERAL'],
                [
                    'organization_unit_id' => $organizationUnit?->getKey(),
                    'parent_id' => null,
                    'name' => 'General Customers',
                    'description' => 'Default customer category.',
                    'is_active' => true,
                    'sort_order' => 1,
                ],
            );

            $customer = Customer::query()->updateOrCreate(
                ['tenant_id' => $tenant->getKey(), 'customer_number' => 'CUS-000001'],
                [
                    'organization_unit_id' => $organizationUnit?->getKey(),
                    'code' => 'CUS-WALKIN',
                    'name' => 'Walk-in Customer',
                    'display_name' => 'Walk-in Customer',
                    'customer_type' => 'retail',
                    'status' => 'active',
                    'email' => 'customer@example.com',
                    'default_currency_id' => $this->defaultCurrency()?->getKey(),
                    'credit_limit' => '0.000000',
                    'is_credit_allowed' => false,
                    'is_advance_allowed' => true,
                    'is_tax_exempt' => false,
                    'marketing_consent' => false,
                    'preferred_communication_channel' => 'email',
                    'notes' => 'Default customer for local development and testing.',
                    'metadata' => ['seed_source' => 'customer_module'],
                ],
            );

            if (Schema::hasTable('customer_category_assignments')) {
                CustomerCategoryAssignment::query()->updateOrCreate(
                    [
                        'customer_id' => $customer->getKey(),
                        'customer_category_id' => $category->getKey(),
                    ],
                    [
                        'tenant_id' => $tenant->getKey(),
                        'organization_unit_id' => $organizationUnit?->getKey(),
                    ],
                );
            }
        }, 3);
    }

    private function seedPermissions(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }
        $guard = (string) config('auth.defaults.guard', 'web');
        foreach (DB::table('tenants')->pluck('id') as $tenantId) {
            foreach (CustomerAuthorizationService::descriptions() as $name => $description) {
                DB::table('permissions')->updateOrInsert(['tenant_id' => $tenantId, 'name' => $name, 'guard_name' => $guard], ['organization_unit_id' => null, 'module' => 'Customer', 'description' => $description, 'row_version' => 1, 'created_at' => now(), 'updated_at' => now()]);
            }
        }
    }
}
