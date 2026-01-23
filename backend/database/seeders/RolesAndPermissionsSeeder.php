<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // Tenant management
            'tenants.view',
            'tenants.create',
            'tenants.edit',
            'tenants.delete',
            'tenants.manage-subscription',

            // User management
            'users.view',
            'users.create',
            'users.edit',
            'users.delete',
            'users.manage-roles',

            // Role & Permission management
            'roles.view',
            'roles.create',
            'roles.edit',
            'roles.delete',
            'permissions.view',
            'permissions.create',
            'permissions.edit',
            'permissions.delete',

            // Customer management
            'customers.view',
            'customers.create',
            'customers.edit',
            'customers.delete',

            // Vehicle management
            'vehicles.view',
            'vehicles.create',
            'vehicles.edit',
            'vehicles.delete',
            'vehicles.transfer-ownership',

            // Appointment management
            'appointments.view',
            'appointments.create',
            'appointments.edit',
            'appointments.delete',

            // Job card management
            'job-cards.view',
            'job-cards.create',
            'job-cards.edit',
            'job-cards.delete',
            'job-cards.manage-tasks',

            // Inventory management
            'inventory.view',
            'inventory.create',
            'inventory.edit',
            'inventory.delete',
            'inventory.manage-stock',

            // Invoice management
            'invoices.view',
            'invoices.create',
            'invoices.edit',
            'invoices.delete',
            'invoices.manage-payments',

            // CRM management
            'communications.view',
            'communications.create',
            'communications.send',
            'notifications.view',
            'notifications.create',
            'segments.view',
            'segments.create',
            'segments.edit',
            'segments.delete',

            // Fleet management
            'fleets.view',
            'fleets.create',
            'fleets.edit',
            'fleets.delete',
            'fleets.manage-vehicles',

            // Reporting
            'reports.view',
            'reports.create',
            'reports.export',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Create roles
        $superAdmin = Role::create(['name' => 'super_admin']);
        $admin = Role::create(['name' => 'admin']);
        $manager = Role::create(['name' => 'manager']);
        $user = Role::create(['name' => 'user']);

        // Super admin gets all permissions
        $superAdmin->givePermissionTo(Permission::all());

        // Admin gets most permissions except tenant and role management
        $admin->givePermissionTo([
            'users.view', 'users.create', 'users.edit',
            'customers.view', 'customers.create', 'customers.edit', 'customers.delete',
            'vehicles.view', 'vehicles.create', 'vehicles.edit', 'vehicles.delete', 'vehicles.transfer-ownership',
            'appointments.view', 'appointments.create', 'appointments.edit', 'appointments.delete',
            'job-cards.view', 'job-cards.create', 'job-cards.edit', 'job-cards.delete', 'job-cards.manage-tasks',
            'inventory.view', 'inventory.create', 'inventory.edit', 'inventory.delete', 'inventory.manage-stock',
            'invoices.view', 'invoices.create', 'invoices.edit', 'invoices.delete', 'invoices.manage-payments',
            'communications.view', 'communications.create', 'communications.send',
            'notifications.view', 'notifications.create',
            'segments.view', 'segments.create', 'segments.edit', 'segments.delete',
            'fleets.view', 'fleets.create', 'fleets.edit', 'fleets.delete', 'fleets.manage-vehicles',
            'reports.view', 'reports.create', 'reports.export',
        ]);

        // Manager gets operational permissions
        $manager->givePermissionTo([
            'customers.view', 'customers.create', 'customers.edit',
            'vehicles.view', 'vehicles.create', 'vehicles.edit',
            'appointments.view', 'appointments.create', 'appointments.edit',
            'job-cards.view', 'job-cards.create', 'job-cards.edit', 'job-cards.manage-tasks',
            'inventory.view', 'inventory.create', 'inventory.edit',
            'invoices.view', 'invoices.create', 'invoices.edit', 'invoices.manage-payments',
            'communications.view', 'communications.create',
            'reports.view',
        ]);

        // User gets basic view and create permissions
        $user->givePermissionTo([
            'customers.view',
            'vehicles.view',
            'appointments.view', 'appointments.create',
            'job-cards.view',
            'inventory.view',
            'invoices.view',
        ]);

        $this->command->info('Roles and permissions created successfully!');
    }
}
