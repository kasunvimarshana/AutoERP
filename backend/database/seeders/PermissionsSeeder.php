<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Modules\Permission\Models\Permission;
use Illuminate\Support\Facades\DB;

class PermissionsSeeder extends Seeder
{
    public function run(): void
    {
        DB::beginTransaction();
        
        try {
            $permissions = [
                ['name' => 'users.view', 'guard_name' => 'web', 'module' => 'users'],
                ['name' => 'users.create', 'guard_name' => 'web', 'module' => 'users'],
                ['name' => 'users.update', 'guard_name' => 'web', 'module' => 'users'],
                ['name' => 'users.delete', 'guard_name' => 'web', 'module' => 'users'],
                ['name' => 'users.restore', 'guard_name' => 'web', 'module' => 'users'],

                ['name' => 'roles.view', 'guard_name' => 'web', 'module' => 'roles'],
                ['name' => 'roles.create', 'guard_name' => 'web', 'module' => 'roles'],
                ['name' => 'roles.update', 'guard_name' => 'web', 'module' => 'roles'],
                ['name' => 'roles.delete', 'guard_name' => 'web', 'module' => 'roles'],

                ['name' => 'permissions.view', 'guard_name' => 'web', 'module' => 'permissions'],

                ['name' => 'customers.view', 'guard_name' => 'web', 'module' => 'customers'],
                ['name' => 'customers.create', 'guard_name' => 'web', 'module' => 'customers'],
                ['name' => 'customers.update', 'guard_name' => 'web', 'module' => 'customers'],
                ['name' => 'customers.delete', 'guard_name' => 'web', 'module' => 'customers'],
                ['name' => 'customers.restore', 'guard_name' => 'web', 'module' => 'customers'],

                ['name' => 'vehicles.view', 'guard_name' => 'web', 'module' => 'vehicles'],
                ['name' => 'vehicles.create', 'guard_name' => 'web', 'module' => 'vehicles'],
                ['name' => 'vehicles.update', 'guard_name' => 'web', 'module' => 'vehicles'],
                ['name' => 'vehicles.delete', 'guard_name' => 'web', 'module' => 'vehicles'],
                ['name' => 'vehicles.restore', 'guard_name' => 'web', 'module' => 'vehicles'],

                ['name' => 'products.view', 'guard_name' => 'web', 'module' => 'products'],
                ['name' => 'products.create', 'guard_name' => 'web', 'module' => 'products'],
                ['name' => 'products.update', 'guard_name' => 'web', 'module' => 'products'],
                ['name' => 'products.delete', 'guard_name' => 'web', 'module' => 'products'],
                ['name' => 'products.restore', 'guard_name' => 'web', 'module' => 'products'],

                ['name' => 'inventory.view', 'guard_name' => 'web', 'module' => 'inventory'],
                ['name' => 'inventory.create', 'guard_name' => 'web', 'module' => 'inventory'],
                ['name' => 'inventory.update', 'guard_name' => 'web', 'module' => 'inventory'],
                ['name' => 'inventory.delete', 'guard_name' => 'web', 'module' => 'inventory'],
                ['name' => 'inventory.adjust', 'guard_name' => 'web', 'module' => 'inventory'],
                ['name' => 'inventory.transfer', 'guard_name' => 'web', 'module' => 'inventory'],

                ['name' => 'orders.view', 'guard_name' => 'web', 'module' => 'orders'],
                ['name' => 'orders.create', 'guard_name' => 'web', 'module' => 'orders'],
                ['name' => 'orders.update', 'guard_name' => 'web', 'module' => 'orders'],
                ['name' => 'orders.delete', 'guard_name' => 'web', 'module' => 'orders'],
                ['name' => 'orders.cancel', 'guard_name' => 'web', 'module' => 'orders'],

                ['name' => 'invoices.view', 'guard_name' => 'web', 'module' => 'invoices'],
                ['name' => 'invoices.create', 'guard_name' => 'web', 'module' => 'invoices'],
                ['name' => 'invoices.update', 'guard_name' => 'web', 'module' => 'invoices'],
                ['name' => 'invoices.delete', 'guard_name' => 'web', 'module' => 'invoices'],

                ['name' => 'payments.view', 'guard_name' => 'web', 'module' => 'payments'],
                ['name' => 'payments.create', 'guard_name' => 'web', 'module' => 'payments'],
                ['name' => 'payments.update', 'guard_name' => 'web', 'module' => 'payments'],
                ['name' => 'payments.delete', 'guard_name' => 'web', 'module' => 'payments'],

                ['name' => 'tenants.view', 'guard_name' => 'web', 'module' => 'tenants'],
                ['name' => 'tenants.create', 'guard_name' => 'web', 'module' => 'tenants'],
                ['name' => 'tenants.update', 'guard_name' => 'web', 'module' => 'tenants'],
                ['name' => 'tenants.delete', 'guard_name' => 'web', 'module' => 'tenants'],
            ];

            foreach ($permissions as $permission) {
                Permission::firstOrCreate(
                    ['name' => $permission['name'], 'guard_name' => $permission['guard_name']],
                    ['module' => $permission['module']]
                );
            }

            DB::commit();
            
            $this->command->info('Permissions seeded successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('Error seeding permissions: ' . $e->getMessage());
            throw $e;
        }
    }
}
