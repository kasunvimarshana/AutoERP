<?php
namespace Database\Seeders;

use App\Models\Inventory;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'user.view', 'user.create', 'user.update', 'user.delete',
            'product.view', 'product.create', 'product.update', 'product.delete',
            'inventory.view', 'inventory.create', 'inventory.update', 'inventory.delete', 'inventory.adjust',
            'order.view', 'order.create', 'order.update', 'order.cancel',
            'tenant.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'api']);
        }

        $superAdmin = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'api']);
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'api']);
        $manager = Role::firstOrCreate(['name' => 'manager', 'guard_name' => 'api']);
        $staff = Role::firstOrCreate(['name' => 'staff', 'guard_name' => 'api']);
        $viewer = Role::firstOrCreate(['name' => 'viewer', 'guard_name' => 'api']);

        $superAdmin->syncPermissions(Permission::all());
        $admin->syncPermissions(Permission::whereNotIn('name', ['tenant.manage'])->get());
        $manager->syncPermissions([
            'user.view', 'product.view', 'product.create', 'product.update',
            'inventory.view', 'inventory.create', 'inventory.update', 'inventory.adjust',
            'order.view', 'order.create', 'order.update', 'order.cancel',
        ]);
        $staff->syncPermissions([
            'product.view', 'inventory.view', 'inventory.adjust',
            'order.view', 'order.create',
        ]);
        $viewer->syncPermissions([
            'product.view', 'inventory.view', 'order.view',
        ]);

        $tenant = Tenant::firstOrCreate(
            ['domain' => 'demo'],
            [
                'name' => 'Demo Company',
                'settings' => ['theme' => 'default', 'currency' => 'USD'],
                'is_active' => true,
            ]
        );

        $tenant->configs()->firstOrCreate(
            ['key' => 'mail_from_address'],
            ['value' => 'noreply@demo.com', 'group' => 'mail', 'type' => 'string']
        );
        $tenant->configs()->firstOrCreate(
            ['key' => 'mail_from_name'],
            ['value' => 'Demo Company', 'group' => 'mail', 'type' => 'string']
        );
        $tenant->configs()->firstOrCreate(
            ['key' => 'payment_gateway'],
            ['value' => 'stripe', 'group' => 'payment', 'type' => 'string']
        );
        $tenant->configs()->firstOrCreate(
            ['key' => 'notifications_enabled'],
            ['value' => 'true', 'group' => 'notification', 'type' => 'boolean']
        );

        $adminUser = User::firstOrCreate(
            ['email' => 'admin@demo.com'],
            [
                'name' => 'Admin User',
                'password' => bcrypt('password'),
                'tenant_id' => $tenant->id,
                'is_active' => true,
            ]
        );
        $adminUser->assignRole($admin);

        $superAdminUser = User::firstOrCreate(
            ['email' => 'superadmin@saas.com'],
            [
                'name' => 'Super Admin',
                'password' => bcrypt('password'),
                'tenant_id' => $tenant->id,
                'is_active' => true,
            ]
        );
        $superAdminUser->assignRole($superAdmin);

        $staffUser = User::firstOrCreate(
            ['email' => 'staff@demo.com'],
            [
                'name' => 'Staff User',
                'password' => bcrypt('password'),
                'tenant_id' => $tenant->id,
                'is_active' => true,
                'attributes' => ['department' => 'warehouse'],
            ]
        );
        $staffUser->assignRole($staff);

        $products = [
            ['tenant_id' => $tenant->id, 'name' => 'Widget A', 'sku' => 'WDG-001', 'price' => 29.99, 'category' => 'widgets', 'description' => 'Standard widget'],
            ['tenant_id' => $tenant->id, 'name' => 'Widget B', 'sku' => 'WDG-002', 'price' => 49.99, 'category' => 'widgets', 'description' => 'Premium widget'],
            ['tenant_id' => $tenant->id, 'name' => 'Gadget X', 'sku' => 'GDG-001', 'price' => 99.99, 'category' => 'gadgets', 'description' => 'High-tech gadget'],
        ];

        foreach ($products as $productData) {
            $product = Product::firstOrCreate(['sku' => $productData['sku']], $productData);

            Inventory::firstOrCreate(
                ['product_id' => $product->id, 'tenant_id' => $tenant->id],
                [
                    'warehouse_location' => 'Warehouse A',
                    'quantity' => 100,
                    'reserved_quantity' => 0,
                    'reorder_level' => 10,
                    'unit_cost' => $product->price * 0.6,
                ]
            );
        }

        $this->command->info('Database seeded successfully!');
    }
}
