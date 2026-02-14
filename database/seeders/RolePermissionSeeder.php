<?php

namespace Database\Seeders;

use App\Modules\Tenancy\Models\Tenant;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Get all tenants
        $tenants = Tenant::all();

        foreach ($tenants as $tenant) {
            $this->createRolesAndPermissionsForTenant($tenant);
        }

        $this->command->info('Roles and permissions created successfully for all tenants!');
    }

    private function createRolesAndPermissionsForTenant(Tenant $tenant): void
    {
        // Define modules and their entities
        $modules = [
            'users' => ['view', 'create', 'edit', 'delete'],
            'branches' => ['view', 'create', 'edit', 'delete'],
            'customers' => ['view', 'create', 'edit', 'delete'],
            'vendors' => ['view', 'create', 'edit', 'delete'],
            'products' => ['view', 'create', 'edit', 'delete'],
            'inventory' => ['view', 'create', 'edit', 'delete', 'transfer', 'adjust'],
            'warehouses' => ['view', 'create', 'edit', 'delete'],
            'pos' => ['view', 'create', 'refund', 'void'],
            'invoices' => ['view', 'create', 'edit', 'delete', 'approve', 'send'],
            'payments' => ['view', 'create', 'edit', 'delete'],
            'leads' => ['view', 'create', 'edit', 'delete', 'convert'],
            'opportunities' => ['view', 'create', 'edit', 'delete', 'close'],
            'campaigns' => ['view', 'create', 'edit', 'delete'],
            'fleet' => ['view', 'create', 'edit', 'delete'],
            'maintenance' => ['view', 'create', 'edit', 'delete', 'schedule'],
            'reports' => ['view', 'export'],
            'settings' => ['view', 'edit'],
            'audit-logs' => ['view', 'export'],
        ];

        // Create permissions for this tenant
        $permissions = [];
        foreach ($modules as $module => $actions) {
            foreach ($actions as $action) {
                $permissionName = "{$action}-{$module}";
                $permission = Permission::firstOrCreate([
                    'name' => $permissionName,
                    'guard_name' => 'web',
                    'tenant_id' => $tenant->id,
                ]);
                $permissions[$permissionName] = $permission;
            }
        }

        // Define roles and their permissions
        $rolePermissions = [
            'Super Admin' => array_keys($permissions),

            'Tenant Admin' => [
                'view-users', 'create-users', 'edit-users', 'delete-users',
                'view-branches', 'create-branches', 'edit-branches', 'delete-branches',
                'view-customers', 'create-customers', 'edit-customers', 'delete-customers',
                'view-vendors', 'create-vendors', 'edit-vendors', 'delete-vendors',
                'view-products', 'create-products', 'edit-products', 'delete-products',
                'view-inventory', 'create-inventory', 'edit-inventory', 'delete-inventory', 'transfer-inventory', 'adjust-inventory',
                'view-warehouses', 'create-warehouses', 'edit-warehouses', 'delete-warehouses',
                'view-pos', 'create-pos', 'refund-pos', 'void-pos',
                'view-invoices', 'create-invoices', 'edit-invoices', 'delete-invoices', 'approve-invoices', 'send-invoices',
                'view-payments', 'create-payments', 'edit-payments', 'delete-payments',
                'view-leads', 'create-leads', 'edit-leads', 'delete-leads', 'convert-leads',
                'view-opportunities', 'create-opportunities', 'edit-opportunities', 'delete-opportunities', 'close-opportunities',
                'view-campaigns', 'create-campaigns', 'edit-campaigns', 'delete-campaigns',
                'view-fleet', 'create-fleet', 'edit-fleet', 'delete-fleet',
                'view-maintenance', 'create-maintenance', 'edit-maintenance', 'delete-maintenance', 'schedule-maintenance',
                'view-reports', 'export-reports',
                'view-settings', 'edit-settings',
                'view-audit-logs', 'export-audit-logs',
            ],

            'Manager' => [
                'view-users', 'create-users', 'edit-users',
                'view-branches', 'create-branches', 'edit-branches',
                'view-customers', 'create-customers', 'edit-customers', 'delete-customers',
                'view-vendors', 'create-vendors', 'edit-vendors',
                'view-products', 'create-products', 'edit-products', 'delete-products',
                'view-inventory', 'create-inventory', 'edit-inventory', 'transfer-inventory', 'adjust-inventory',
                'view-warehouses',
                'view-pos', 'create-pos', 'refund-pos', 'void-pos',
                'view-invoices', 'create-invoices', 'edit-invoices', 'approve-invoices', 'send-invoices',
                'view-payments', 'create-payments', 'edit-payments',
                'view-leads', 'create-leads', 'edit-leads', 'delete-leads', 'convert-leads',
                'view-opportunities', 'create-opportunities', 'edit-opportunities', 'close-opportunities',
                'view-campaigns', 'create-campaigns', 'edit-campaigns',
                'view-fleet', 'create-fleet', 'edit-fleet',
                'view-maintenance', 'create-maintenance', 'edit-maintenance', 'schedule-maintenance',
                'view-reports', 'export-reports',
                'view-audit-logs',
            ],

            'Sales' => [
                'view-customers', 'create-customers', 'edit-customers',
                'view-products',
                'view-pos', 'create-pos',
                'view-invoices', 'create-invoices', 'send-invoices',
                'view-payments', 'create-payments',
                'view-leads', 'create-leads', 'edit-leads', 'convert-leads',
                'view-opportunities', 'create-opportunities', 'edit-opportunities', 'close-opportunities',
                'view-reports',
            ],

            'Inventory Manager' => [
                'view-products', 'create-products', 'edit-products',
                'view-inventory', 'create-inventory', 'edit-inventory', 'transfer-inventory', 'adjust-inventory',
                'view-warehouses', 'create-warehouses', 'edit-warehouses',
                'view-vendors', 'create-vendors', 'edit-vendors',
                'view-reports',
            ],

            'Cashier' => [
                'view-customers', 'create-customers',
                'view-products',
                'view-pos', 'create-pos', 'refund-pos',
                'view-payments', 'create-payments',
            ],

            'Viewer' => [
                'view-customers',
                'view-products',
                'view-inventory',
                'view-invoices',
                'view-payments',
                'view-reports',
            ],
        ];

        // Create roles and assign permissions for this tenant
        foreach ($rolePermissions as $roleName => $permissionNames) {
            $role = Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web',
                'tenant_id' => $tenant->id,
            ]);

            // Get permission IDs for this tenant
            $permissionIds = collect($permissionNames)
                ->map(fn ($name) => $permissions[$name] ?? null)
                ->filter()
                ->pluck('id')
                ->toArray();

            // Sync permissions to the role with tenant_id
            $pivotData = collect($permissionIds)->mapWithKeys(function ($permissionId) use ($tenant) {
                return [$permissionId => ['tenant_id' => $tenant->id]];
            })->toArray();

            $role->permissions()->sync($pivotData);
        }
    }
}
