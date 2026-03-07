<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ---------------------------------------------------------------
        // Default Tenant
        // ---------------------------------------------------------------
        $tenant = Tenant::firstOrCreate(
            ['slug' => 'demo'],
            [
                'name'      => 'Demo Tenant',
                'domain'    => 'demo.localhost',
                'plan'      => 'enterprise',
                'status'    => 'active',
                'max_users' => 100,
                'timezone'  => 'UTC',
                'locale'    => 'en',
                'currency'  => 'USD',
            ]
        );

        // ---------------------------------------------------------------
        // Roles
        // ---------------------------------------------------------------
        $roles = ['super-admin', 'admin', 'manager', 'staff'];

        foreach ($roles as $roleName) {
            Role::firstOrCreate(
                ['name' => $roleName, 'guard_name' => 'api'],
                ['description' => ucfirst(str_replace('-', ' ', $roleName)), 'is_system' => true]
            );
        }

        // ---------------------------------------------------------------
        // Permissions
        // ---------------------------------------------------------------
        $permissions = [
            ['name' => 'inventory:read',  'group' => 'inventory'],
            ['name' => 'inventory:write', 'group' => 'inventory'],
            ['name' => 'orders:read',     'group' => 'orders'],
            ['name' => 'orders:write',    'group' => 'orders'],
            ['name' => 'tenants:manage',  'group' => 'tenants'],
            ['name' => 'webhooks:manage', 'group' => 'webhooks'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission['name'], 'guard_name' => 'api'],
                ['group' => $permission['group']]
            );
        }

        // Assign all permissions to super-admin.
        $superAdmin = Role::where('name', 'super-admin')->first();
        $superAdmin->syncPermissions(Permission::all());

        // Assign inventory + order permissions to admin.
        $admin = Role::where('name', 'admin')->first();
        $admin->syncPermissions(
            Permission::whereIn('name', ['inventory:read', 'inventory:write', 'orders:read', 'orders:write'])->get()
        );

        // Manager gets read + order write.
        $manager = Role::where('name', 'manager')->first();
        $manager->syncPermissions(
            Permission::whereIn('name', ['inventory:read', 'orders:read', 'orders:write'])->get()
        );

        // Staff read-only.
        $staff = Role::where('name', 'staff')->first();
        $staff->syncPermissions(
            Permission::whereIn('name', ['inventory:read', 'orders:read'])->get()
        );

        // ---------------------------------------------------------------
        // Super-admin user
        // NOTE: These are demo credentials. Change or remove them before
        //       deploying to any environment accessible from the internet.
        // ---------------------------------------------------------------
        $superAdminUser = User::firstOrCreate(
            ['email' => 'admin@demo.inventory.local'],
            [
                'tenant_id' => $tenant->id,
                'name'      => 'Super Admin',
                'password'  => Hash::make('password'),
                'status'    => 'active',
            ]
        );
        $superAdminUser->assignRole('super-admin');

        // ---------------------------------------------------------------
        // Demo staff user
        // NOTE: Demo credentials only — MUST be removed before production.
        // ---------------------------------------------------------------
        $staffUser = User::firstOrCreate(
            ['email' => 'staff@demo.inventory.local'],
            [
                'tenant_id' => $tenant->id,
                'name'      => 'Demo Staff',
                'password'  => Hash::make('password'),
                'status'    => 'active',
            ]
        );
        $staffUser->assignRole('staff');

        $this->command->info('Database seeded successfully.');
        $this->command->table(
            ['Role', 'Email', 'Password'],
            [
                ['super-admin', 'admin@demo.inventory.local', 'password'],
                ['staff', 'staff@demo.inventory.local', 'password'],
            ]
        );
    }
}
