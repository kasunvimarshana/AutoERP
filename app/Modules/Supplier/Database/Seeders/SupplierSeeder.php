<?php

declare(strict_types=1);

namespace Modules\Supplier\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Core\Database\Seeders\Concerns\ResolvesSeedContext;
use Modules\Supplier\Models\Supplier;
use Modules\Supplier\Models\SupplierCategory;
use Modules\Supplier\Models\SupplierCategoryAssignment;
use Modules\Supplier\Services\SupplierAuthorizationService;

final class SupplierSeeder extends Seeder
{
    use ResolvesSeedContext;

    public function run(): void
    {
        if (! Schema::hasTable('suppliers')) {
            return;
        }

        $tenant = $this->defaultTenant();
        $organizationUnit = $this->defaultOrganizationUnit($tenant);
        if ($tenant === null) {
            return;
        }

        DB::transaction(function () use ($tenant, $organizationUnit): void {
            $this->seedPermissions((int) $tenant->getKey());
            $category = SupplierCategory::query()->updateOrCreate(
                ['tenant_id' => $tenant->getKey(), 'code' => 'GENERAL'],
                [
                    'organization_unit_id' => $organizationUnit?->getKey(),
                    'parent_id' => null,
                    'name' => 'General Suppliers',
                    'description' => 'Default supplier category.',
                    'is_active' => true,
                    'sort_order' => 1,
                ],
            );

            $supplier = Supplier::query()->updateOrCreate(
                ['tenant_id' => $tenant->getKey(), 'supplier_number' => 'SUP-000001'],
                [
                    'organization_unit_id' => $organizationUnit?->getKey(),
                    'code' => 'SUP-GENERIC',
                    'name' => 'Generic Parts Supplier',
                    'legal_name' => 'Generic Parts Supplier',
                    'display_name' => 'Generic Parts Supplier',
                    'supplier_type' => 'company',
                    'status' => 'active',
                    'email' => 'supplier@example.com',
                    'default_currency_id' => $this->defaultCurrency()?->getKey(),
                    'credit_limit' => '0.000000',
                    'is_credit_allowed' => true,
                    'is_advance_allowed' => true,
                    'notes' => 'Default supplier for local development and testing.',
                    'metadata' => ['seed_source' => 'supplier_module'],
                ],
            );

            if (Schema::hasTable('supplier_category_assignments')) {
                SupplierCategoryAssignment::query()->updateOrCreate(
                    [
                        'supplier_id' => $supplier->getKey(),
                        'supplier_category_id' => $category->getKey(),
                    ],
                    [
                        'tenant_id' => $tenant->getKey(),
                        'organization_unit_id' => $organizationUnit?->getKey(),
                    ],
                );
            }
        }, 3);
    }

    private function seedPermissions(int $tenantId): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }
        $guard = (string) config('auth.defaults.guard', 'web');
        foreach (SupplierAuthorizationService::descriptions() as $name => $description) {
            DB::table('permissions')->updateOrInsert(['tenant_id' => $tenantId, 'name' => $name, 'guard_name' => $guard], ['organization_unit_id' => null, 'module' => 'Supplier', 'description' => $description, 'row_version' => 1, 'created_at' => now(), 'updated_at' => now()]);
        }
    }
}
