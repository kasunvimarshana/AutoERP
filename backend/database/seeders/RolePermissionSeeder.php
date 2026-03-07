<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    private array $modules = ['users', 'products', 'inventory', 'orders', 'webhooks', 'tenants'];
    private array $actions = ['view', 'create', 'update', 'delete', 'restore', 'export'];

    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Create all permissions
        foreach ($this->modules as $module) {
            foreach ($this->actions as $action) {
                Permission::firstOrCreate(
                    ['name' => "{$module}.{$action}", 'guard_name' => 'api'],
                    ['module' => $module, 'description' => ucfirst($action) . ' ' . $module]
                );
            }
        }

        // Superadmin — all permissions
        $superadmin = Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'api']);
        $superadmin->syncPermissions(Permission::all());

        // Admin — all permissions except tenant management
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'api']);
        $admin->syncPermissions(
            Permission::where('name', 'NOT LIKE', 'tenants.%')->get()
        );

        // Manager — CRUD on products, inventory, orders; view users
        $manager = Role::firstOrCreate(['name' => 'manager', 'guard_name' => 'api']);
        $manager->syncPermissions([
            'users.view',
            'products.view', 'products.create', 'products.update',
            'inventory.view', 'inventory.create', 'inventory.update',
            'orders.view', 'orders.create', 'orders.update',
            'webhooks.view',
        ]);

        // User — view + create orders
        $user = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'api']);
        $user->syncPermissions([
            'products.view',
            'inventory.view',
            'orders.view', 'orders.create',
        ]);

        // Viewer — read-only access
        $viewer = Role::firstOrCreate(['name' => 'viewer', 'guard_name' => 'api']);
        $viewer->syncPermissions(
            Permission::where('name', 'LIKE', '%.view')->get()
        );

        $this->command->info('Roles and permissions seeded successfully.');
    }
}
