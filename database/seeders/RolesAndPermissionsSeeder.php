<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

/**
 * Roles and Permissions Seeder
 * 
 * Seeds initial roles and permissions for RBAC system
 */
class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // User permissions
            'user.view',
            'user.create',
            'user.edit',
            'user.delete',
            
            // Role permissions
            'role.view',
            'role.create',
            'role.edit',
            'role.delete',
            'role.assign',
            
            // Permission permissions
            'permission.view',
            'permission.create',
            'permission.edit',
            'permission.delete',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create roles and assign permissions

        // Super Admin - has all permissions
        $superAdmin = Role::firstOrCreate(['name' => 'super-admin']);
        $superAdmin->givePermissionTo(Permission::all());

        // Admin - has most permissions except critical ones
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $admin->givePermissionTo([
            'user.view',
            'user.create',
            'user.edit',
            'user.delete',
            'role.view',
            'role.assign',
            'permission.view',
        ]);

        // Manager - can manage users
        $manager = Role::firstOrCreate(['name' => 'manager']);
        $manager->givePermissionTo([
            'user.view',
            'user.create',
            'user.edit',
        ]);

        // User - basic permissions
        $user = Role::firstOrCreate(['name' => 'user']);
        $user->givePermissionTo([
            'user.view',
        ]);

        $this->command->info('Roles and permissions seeded successfully!');
    }
}
