<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesPermissionsSeeder extends Seeder
{
    /**
     * System-wide roles.
     */
    private array $roles = [
        'super-admin',   // Global - bypasses all permission checks
        'tenant-admin',  // Manages everything within their tenant
        'manager',       // Manages users, inventory, orders within their org
        'staff',         // Operational access (create/read/update)
        'viewer',        // Read-only access
    ];

    /**
     * All permissions in {resource}.{action} format.
     */
    private array $permissions = [
        // Tenant management
        'tenants.view', 'tenants.create', 'tenants.update', 'tenants.delete', 'tenants.manage',

        // User management
        'users.view', 'users.create', 'users.update', 'users.delete',
        'users.assign-roles', 'users.assign-permissions',

        // Organization management
        'organizations.view', 'organizations.create', 'organizations.update', 'organizations.delete',

        // Role / permission management
        'roles.view', 'roles.create', 'roles.update', 'roles.delete', 'roles.assign',
        'permissions.view', 'permissions.create', 'permissions.update', 'permissions.delete',

        // Audit logs
        'audit-logs.view', 'audit-logs.export',

        // Inventory
        'inventory.view', 'inventory.create', 'inventory.update', 'inventory.delete',
        'inventory.import', 'inventory.export',

        // Orders
        'orders.view', 'orders.create', 'orders.update', 'orders.delete',
        'orders.approve', 'orders.cancel',

        // Reports
        'reports.view', 'reports.generate', 'reports.export',

        // Settings
        'settings.view', 'settings.update',
    ];

    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Create all permissions
        foreach ($this->permissions as $permission) {
            Permission::firstOrCreate([
                'name'       => $permission,
                'guard_name' => 'api',
            ]);
        }

        $this->command->info('Permissions created: ' . count($this->permissions));

        // Create roles
        foreach ($this->roles as $roleName) {
            Role::firstOrCreate([
                'name'       => $roleName,
                'guard_name' => 'api',
            ]);
        }

        $this->command->info('Roles created: ' . count($this->roles));

        // Assign permissions to roles
        $this->assignPermissionsToRoles();

        $this->command->info('Role-permission mappings applied.');
    }

    private function assignPermissionsToRoles(): void
    {
        // super-admin — gets all permissions
        $superAdmin = Role::findByName('super-admin', 'api');
        $superAdmin->syncPermissions(Permission::all());

        // tenant-admin — all except tenant/system management
        $tenantAdmin = Role::findByName('tenant-admin', 'api');
        $tenantAdmin->syncPermissions(
            Permission::whereNotIn('name', [
                'tenants.create', 'tenants.delete',
                'permissions.create', 'permissions.delete',
            ])->get()
        );

        // manager — user, org, inventory, orders, reports
        $manager = Role::findByName('manager', 'api');
        $manager->syncPermissions([
            'users.view', 'users.create', 'users.update',
            'organizations.view', 'organizations.create', 'organizations.update',
            'inventory.view', 'inventory.create', 'inventory.update', 'inventory.import', 'inventory.export',
            'orders.view', 'orders.create', 'orders.update', 'orders.approve', 'orders.cancel',
            'reports.view', 'reports.generate', 'reports.export',
            'audit-logs.view',
            'settings.view',
        ]);

        // staff — operational
        $staff = Role::findByName('staff', 'api');
        $staff->syncPermissions([
            'users.view',
            'organizations.view',
            'inventory.view', 'inventory.create', 'inventory.update',
            'orders.view', 'orders.create', 'orders.update',
            'reports.view',
        ]);

        // viewer — read-only
        $viewer = Role::findByName('viewer', 'api');
        $viewer->syncPermissions([
            'users.view',
            'organizations.view',
            'inventory.view',
            'orders.view',
            'reports.view',
        ]);
    }
}
