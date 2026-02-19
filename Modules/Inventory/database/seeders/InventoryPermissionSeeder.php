<?php

declare(strict_types=1);

namespace Modules\Inventory\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Inventory Permission Seeder
 *
 * Seeds inventory-related permissions
 */
class InventoryPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create Inventory permissions
        $permissions = [
            // Inventory Item permissions
            'inventory.view',
            'inventory.create',
            'inventory.edit',
            'inventory.delete',
            'inventory.adjust-stock',
            'inventory.transfer-stock',

            // Supplier permissions
            'supplier.view',
            'supplier.create',
            'supplier.edit',
            'supplier.delete',

            // Purchase Order permissions
            'purchase-order.view',
            'purchase-order.create',
            'purchase-order.edit',
            'purchase-order.delete',
            'purchase-order.approve',
            'purchase-order.receive',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Assign permissions to existing roles

        // Super Admin - has all permissions
        $superAdmin = Role::where('name', 'super-admin')->first();
        if ($superAdmin) {
            $superAdmin->givePermissionTo($permissions);
        }

        // Admin - has all inventory permissions
        $admin = Role::where('name', 'admin')->first();
        if ($admin) {
            $admin->givePermissionTo($permissions);
        }

        // Manager - can view and manage inventory
        $manager = Role::where('name', 'manager')->first();
        if ($manager) {
            $manager->givePermissionTo([
                'inventory.view',
                'inventory.create',
                'inventory.edit',
                'inventory.adjust-stock',
                'supplier.view',
                'purchase-order.view',
                'purchase-order.create',
            ]);
        }

        // User - can only view
        $user = Role::where('name', 'user')->first();
        if ($user) {
            $user->givePermissionTo([
                'inventory.view',
                'supplier.view',
                'purchase-order.view',
            ]);
        }

        $this->command->info('Inventory permissions seeded successfully!');
    }
}
