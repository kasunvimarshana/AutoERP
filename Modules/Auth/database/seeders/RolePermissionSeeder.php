<?php

declare(strict_types=1);

namespace Modules\Auth\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Role and Permission Seeder
 *
 * Seeds default roles and permissions for the application
 */
class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // User permissions
            'user.create',
            'user.read',
            'user.update',
            'user.delete',
            'user.list',

            // Role permissions
            'role.create',
            'role.read',
            'role.update',
            'role.delete',
            'role.list',
            'role.assign',
            'role.revoke',

            // Permission permissions
            'permission.create',
            'permission.read',
            'permission.update',
            'permission.delete',
            'permission.list',
            'permission.assign',
            'permission.revoke',

            // Tenant permissions
            'tenant.create',
            'tenant.read',
            'tenant.update',
            'tenant.delete',
            'tenant.list',
            'tenant.switch',

            // Audit permissions
            'audit.read',
            'audit.list',
            'audit.export',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create roles and assign permissions

        // Super Admin - has all permissions
        $superAdmin = Role::firstOrCreate(['name' => 'super-admin']);
        $superAdmin->givePermissionTo(Permission::all());

        // Admin - has most permissions except system-level operations
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $admin->givePermissionTo([
            'user.create',
            'user.read',
            'user.update',
            'user.delete',
            'user.list',
            'role.read',
            'role.list',
            'role.assign',
            'role.revoke',
            'permission.read',
            'permission.list',
            'tenant.read',
            'tenant.list',
            'audit.read',
            'audit.list',
        ]);

        // Manager - can manage users and view data
        $manager = Role::firstOrCreate(['name' => 'manager']);
        $manager->givePermissionTo([
            'user.create',
            'user.read',
            'user.update',
            'user.list',
            'role.read',
            'role.list',
            'tenant.read',
            'audit.read',
            'audit.list',
        ]);

        // User - basic permissions
        $user = Role::firstOrCreate(['name' => 'user']);
        $user->givePermissionTo([
            'user.read',
            'tenant.read',
        ]);

        // Guest - minimal permissions
        $guest = Role::firstOrCreate(['name' => 'guest']);
        $guest->givePermissionTo([
            'tenant.read',
        ]);

        $this->command->info('Roles and permissions seeded successfully!');
    }
}
