<?php

declare(strict_types=1);

namespace Modules\IAM\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\IAM\Entities\Permission;
use Modules\IAM\Entities\Role;

/**
 * Role Seeder
 *
 * Seeds system roles with appropriate permissions.
 */
class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // System Administrator Role
        $admin = Role::create([
            'name' => 'System Administrator',
            'slug' => 'system-admin',
            'description' => 'Full system access with all permissions',
            'permissions' => ['*'],
            'is_system' => true,
            'is_active' => true,
            'level' => 0,
        ]);

        // Tenant Administrator Role
        $tenantAdmin = Role::create([
            'name' => 'Tenant Administrator',
            'slug' => 'tenant-admin',
            'description' => 'Full access within tenant boundaries',
            'permissions' => [],
            'is_system' => true,
            'is_active' => true,
            'level' => 0,
        ]);

        // Manager Role
        $manager = Role::create([
            'name' => 'Manager',
            'slug' => 'manager',
            'description' => 'Department or team manager with elevated permissions',
            'permissions' => [],
            'is_system' => true,
            'is_active' => true,
            'level' => 1,
        ]);

        // Employee Role
        $employee = Role::create([
            'name' => 'Employee',
            'slug' => 'employee',
            'description' => 'Standard employee with basic access',
            'permissions' => [],
            'is_system' => true,
            'is_active' => true,
            'level' => 2,
        ]);

        // Sales Representative Role
        $salesRep = Role::create([
            'name' => 'Sales Representative',
            'slug' => 'sales-rep',
            'description' => 'Sales team member with CRM access',
            'parent_id' => $employee->id,
            'permissions' => [],
            'is_system' => true,
            'is_active' => true,
        ]);

        // Accountant Role
        $accountant = Role::create([
            'name' => 'Accountant',
            'slug' => 'accountant',
            'description' => 'Accounting and finance team member',
            'parent_id' => $employee->id,
            'permissions' => [],
            'is_system' => true,
            'is_active' => true,
        ]);

        // Inventory Manager Role
        $inventoryManager = Role::create([
            'name' => 'Inventory Manager',
            'slug' => 'inventory-manager',
            'description' => 'Warehouse and inventory management',
            'parent_id' => $manager->id,
            'permissions' => [],
            'is_system' => true,
            'is_active' => true,
        ]);

        // HR Manager Role
        $hrManager = Role::create([
            'name' => 'HR Manager',
            'slug' => 'hr-manager',
            'description' => 'Human resources management',
            'parent_id' => $manager->id,
            'permissions' => [],
            'is_system' => true,
            'is_active' => true,
        ]);

        // Assign permissions to roles if they exist
        $this->assignPermissionsToRoles($admin, $tenantAdmin, $manager, $salesRep, $accountant, $inventoryManager, $hrManager);
    }

    /**
     * Assign permissions to roles based on their function.
     */
    private function assignPermissionsToRoles(...$roles): void
    {
        [$admin, $tenantAdmin, $manager, $salesRep, $accountant, $inventoryManager, $hrManager] = $roles;

        // Admin gets all permissions
        // (Handled by permissions array ['*'])

        // Tenant Admin permissions
        $tenantAdminPerms = Permission::whereIn('module', [
            'sales', 'inventory', 'accounting', 'hr', 'procurement',
        ])->pluck('id')->toArray();
        if (! empty($tenantAdminPerms)) {
            $tenantAdmin->rolePermissions()->sync($tenantAdminPerms);
        }

        // Sales Rep permissions
        $salesPerms = Permission::where('module', 'sales')
            ->whereIn('resource', ['customer', 'lead', 'sales-order'])
            ->pluck('id')->toArray();
        if (! empty($salesPerms)) {
            $salesRep->rolePermissions()->sync($salesPerms);
        }

        // Accountant permissions
        $accountingPerms = Permission::where('module', 'accounting')
            ->pluck('id')->toArray();
        if (! empty($accountingPerms)) {
            $accountant->rolePermissions()->sync($accountingPerms);
        }

        // Inventory Manager permissions
        $inventoryPerms = Permission::where('module', 'inventory')
            ->pluck('id')->toArray();
        if (! empty($inventoryPerms)) {
            $inventoryManager->rolePermissions()->sync($inventoryPerms);
        }

        // HR Manager permissions
        $hrPerms = Permission::where('module', 'hr')
            ->pluck('id')->toArray();
        if (! empty($hrPerms)) {
            $hrManager->rolePermissions()->sync($hrPerms);
        }
    }
}
