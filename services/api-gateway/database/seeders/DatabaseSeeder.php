<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database with default roles, permissions,
     * and a demo tenant + admin user.
     */
    public function run(): void
    {
        // -----------------------------------------------------------------------
        // Roles
        // -----------------------------------------------------------------------
        $superAdmin = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'api']);
        $admin      = Role::firstOrCreate(['name' => 'admin',       'guard_name' => 'api']);
        $manager    = Role::firstOrCreate(['name' => 'manager',     'guard_name' => 'api']);
        $user       = Role::firstOrCreate(['name' => 'user',        'guard_name' => 'api']);

        // -----------------------------------------------------------------------
        // Permissions
        // -----------------------------------------------------------------------
        $permissions = [
            'view-orders',   'create-orders',   'update-orders',   'delete-orders',
            'view-inventory','create-inventory','update-inventory','delete-inventory',
            'view-payments', 'create-payments', 'update-payments', 'delete-payments',
            'view-users',    'create-users',    'update-users',    'delete-users',
            'delete-resources',
            'manage-tenants',
        ];

        foreach ($permissions as $permName) {
            Permission::firstOrCreate(['name' => $permName, 'guard_name' => 'api']);
        }

        // -----------------------------------------------------------------------
        // Role ↔ Permission assignments
        // -----------------------------------------------------------------------
        $superAdmin->syncPermissions(Permission::where('guard_name', 'api')->get());

        $admin->syncPermissions([
            'view-orders',   'create-orders',   'update-orders',   'delete-orders',
            'view-inventory','create-inventory','update-inventory','delete-inventory',
            'view-payments', 'create-payments', 'update-payments', 'delete-payments',
            'view-users',    'create-users',    'update-users',    'delete-users',
            'delete-resources',
        ]);

        $manager->syncPermissions([
            'view-orders',   'create-orders',   'update-orders',
            'view-inventory','create-inventory','update-inventory',
            'view-payments',
            'view-users',
        ]);

        $user->syncPermissions([
            'view-orders',
            'view-inventory',
            'view-payments',
        ]);

        // -----------------------------------------------------------------------
        // Demo tenant + admin user
        // -----------------------------------------------------------------------
        if (app()->environment('local', 'development')) {
            $tenant = Tenant::firstOrCreate(
                ['domain' => 'demo.localhost'],
                [
                    'name'      => 'Demo Tenant',
                    'settings'  => ['theme' => 'default'],
                    'is_active' => true,
                ]
            );

            $demoAdmin = User::firstOrCreate(
                ['email' => 'admin@demo.localhost'],
                [
                    'tenant_id' => $tenant->id,
                    'name'      => 'Demo Admin',
                    'password'  => Hash::make('password'),
                    'role'      => 'admin',
                    'is_active' => true,
                ]
            );

            $demoAdmin->assignRole('admin');
        }
    }
}
