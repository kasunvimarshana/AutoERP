<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use App\Modules\Tenant\Models\Tenant;
use App\Modules\Tenant\Models\Organization;
use App\Modules\Tenant\Models\Branch;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get demo tenant
        $tenant = Tenant::where('slug', 'demo-company')->first();
        if (!$tenant) {
            $this->command->error('Tenant not found. Please run TenantSeeder first.');
            return;
        }

        $organization = Organization::where('tenant_id', $tenant->id)->first();
        $branch = Branch::where('tenant_id', $tenant->id)->where('is_warehouse', true)->first();

        // Create roles
        $superAdminRole = Role::create(['name' => 'super-admin']);
        $adminRole = Role::create(['name' => 'admin']);
        $managerRole = Role::create(['name' => 'manager']);
        $userRole = Role::create(['name' => 'user']);

        // Create permissions
        $permissions = [
            // Product permissions
            'view-products',
            'create-products',
            'edit-products',
            'delete-products',
            // Inventory permissions
            'view-inventory',
            'manage-inventory',
            'stock-in',
            'stock-out',
            'stock-transfer',
            'stock-adjustment',
            // User permissions
            'view-users',
            'create-users',
            'edit-users',
            'delete-users',
            // Role permissions
            'view-roles',
            'create-roles',
            'edit-roles',
            'delete-roles',
            // Tenant permissions
            'view-tenants',
            'manage-tenants',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Assign all permissions to super-admin
        $superAdminRole->givePermissionTo(Permission::all());

        // Assign specific permissions to other roles
        $adminRole->givePermissionTo([
            'view-products', 'create-products', 'edit-products', 'delete-products',
            'view-inventory', 'manage-inventory', 'stock-in', 'stock-out', 'stock-transfer', 'stock-adjustment',
            'view-users', 'create-users', 'edit-users',
            'view-roles',
        ]);

        $managerRole->givePermissionTo([
            'view-products', 'create-products', 'edit-products',
            'view-inventory', 'manage-inventory', 'stock-in', 'stock-out', 'stock-transfer',
            'view-users',
        ]);

        $userRole->givePermissionTo([
            'view-products',
            'view-inventory',
        ]);

        // Create super admin user
        $superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@demo.autoerp.local',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'tenant_id' => $tenant->id,
            'organization_id' => $organization->id,
            'branch_id' => $branch->id,
        ]);
        $superAdmin->assignRole($superAdminRole);

        // Create admin user
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@demo.autoerp.local',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'tenant_id' => $tenant->id,
            'organization_id' => $organization->id,
            'branch_id' => $branch->id,
        ]);
        $admin->assignRole($adminRole);

        // Create manager user
        $manager = User::create([
            'name' => 'Manager User',
            'email' => 'manager@demo.autoerp.local',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'tenant_id' => $tenant->id,
            'organization_id' => $organization->id,
            'branch_id' => $branch->id,
        ]);
        $manager->assignRole($managerRole);

        // Create regular user
        $user = User::create([
            'name' => 'Regular User',
            'email' => 'user@demo.autoerp.local',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'tenant_id' => $tenant->id,
            'organization_id' => $organization->id,
            'branch_id' => $branch->id,
        ]);
        $user->assignRole($userRole);

        $this->command->info('Users created successfully!');
        $this->command->info('Super Admin: superadmin@demo.autoerp.local / password');
        $this->command->info('Admin: admin@demo.autoerp.local / password');
        $this->command->info('Manager: manager@demo.autoerp.local / password');
        $this->command->info('User: user@demo.autoerp.local / password');
    }
}
