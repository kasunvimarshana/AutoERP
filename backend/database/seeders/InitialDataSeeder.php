<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Modules\IAM\Models\Permission;
use App\Modules\IAM\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class InitialDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            // Create a demo tenant
            $tenant = Tenant::create([
                'name' => 'Demo Company',
                'slug' => 'demo-company',
                'email' => 'demo@autoerp.com',
                'phone' => '+1234567890',
                'is_active' => true,
                'trial_ends_at' => now()->addDays(30),
            ]);

            // Create permissions
            $permissions = [
                // Product permissions
                ['name' => 'View Products', 'slug' => 'products.view', 'module' => 'inventory'],
                ['name' => 'Create Products', 'slug' => 'products.create', 'module' => 'inventory'],
                ['name' => 'Edit Products', 'slug' => 'products.edit', 'module' => 'inventory'],
                ['name' => 'Delete Products', 'slug' => 'products.delete', 'module' => 'inventory'],

                // Stock permissions
                ['name' => 'View Stock', 'slug' => 'stock.view', 'module' => 'inventory'],
                ['name' => 'Adjust Stock', 'slug' => 'stock.adjust', 'module' => 'inventory'],
                ['name' => 'Transfer Stock', 'slug' => 'stock.transfer', 'module' => 'inventory'],

                // User permissions
                ['name' => 'View Users', 'slug' => 'users.view', 'module' => 'iam'],
                ['name' => 'Create Users', 'slug' => 'users.create', 'module' => 'iam'],
                ['name' => 'Edit Users', 'slug' => 'users.edit', 'module' => 'iam'],
                ['name' => 'Delete Users', 'slug' => 'users.delete', 'module' => 'iam'],

                // Role permissions
                ['name' => 'View Roles', 'slug' => 'roles.view', 'module' => 'iam'],
                ['name' => 'Create Roles', 'slug' => 'roles.create', 'module' => 'iam'],
                ['name' => 'Edit Roles', 'slug' => 'roles.edit', 'module' => 'iam'],
                ['name' => 'Delete Roles', 'slug' => 'roles.delete', 'module' => 'iam'],
            ];

            foreach ($permissions as $permission) {
                Permission::create($permission);
            }

            // Create system roles
            $adminRole = Role::create([
                'tenant_id' => $tenant->id,
                'name' => 'Administrator',
                'slug' => 'admin',
                'description' => 'Full system access',
                'is_system_role' => true,
            ]);

            $managerRole = Role::create([
                'tenant_id' => $tenant->id,
                'name' => 'Manager',
                'slug' => 'manager',
                'description' => 'Manager with limited access',
                'is_system_role' => true,
            ]);

            $staffRole = Role::create([
                'tenant_id' => $tenant->id,
                'name' => 'Staff',
                'slug' => 'staff',
                'description' => 'Basic staff access',
                'is_system_role' => true,
            ]);

            // Assign all permissions to admin role
            $allPermissions = Permission::all();
            $adminRole->permissions()->attach($allPermissions);

            // Assign limited permissions to manager
            $managerPermissions = Permission::whereIn('slug', [
                'products.view',
                'products.create',
                'products.edit',
                'stock.view',
                'stock.adjust',
                'users.view',
            ])->get();
            $managerRole->permissions()->attach($managerPermissions);

            // Assign basic permissions to staff
            $staffPermissions = Permission::whereIn('slug', [
                'products.view',
                'stock.view',
            ])->get();
            $staffRole->permissions()->attach($staffPermissions);

            // Create demo admin user
            $adminUser = \App\Models\User::create([
                'tenant_id' => $tenant->id,
                'name' => 'Admin User',
                'email' => 'admin@demo.com',
                'password' => Hash::make('password'),
                'is_active' => true,
            ]);

            // Assign admin role to admin user
            $adminUser->assignRole($adminRole);

            // Create sample categories
            $categories = [
                ['tenant_id' => $tenant->id, 'name' => 'Electronics', 'slug' => 'electronics', 'is_active' => true],
                ['tenant_id' => $tenant->id, 'name' => 'Clothing', 'slug' => 'clothing', 'is_active' => true],
                ['tenant_id' => $tenant->id, 'name' => 'Food & Beverages', 'slug' => 'food-beverages', 'is_active' => true],
                ['tenant_id' => $tenant->id, 'name' => 'Office Supplies', 'slug' => 'office-supplies', 'is_active' => true],
            ];

            foreach ($categories as $category) {
                DB::table('categories')->insert(array_merge($category, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }

            // Create sample units
            $units = [
                ['tenant_id' => $tenant->id, 'name' => 'Piece', 'symbol' => 'pcs', 'type' => 'quantity', 'is_base' => true],
                ['tenant_id' => $tenant->id, 'name' => 'Kilogram', 'symbol' => 'kg', 'type' => 'weight', 'is_base' => true],
                ['tenant_id' => $tenant->id, 'name' => 'Liter', 'symbol' => 'L', 'type' => 'volume', 'is_base' => true],
                ['tenant_id' => $tenant->id, 'name' => 'Meter', 'symbol' => 'm', 'type' => 'length', 'is_base' => true],
            ];

            foreach ($units as $unit) {
                DB::table('units')->insert(array_merge($unit, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }

            // Create sample warehouse
            DB::table('warehouses')->insert([
                'tenant_id' => $tenant->id,
                'name' => 'Main Warehouse',
                'code' => 'WH-001',
                'type' => 'warehouse',
                'address' => '123 Main Street',
                'city' => 'New York',
                'country' => 'USA',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Create sample taxes
            $taxes = [
                ['tenant_id' => $tenant->id, 'name' => 'VAT 10%', 'type' => 'percentage', 'rate' => 10.0000],
                ['tenant_id' => $tenant->id, 'name' => 'Sales Tax 5%', 'type' => 'percentage', 'rate' => 5.0000],
            ];

            foreach ($taxes as $tax) {
                DB::table('taxes')->insert(array_merge($tax, [
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }

            // Create base currency
            DB::table('currencies')->insert([
                'tenant_id' => $tenant->id,
                'name' => 'US Dollar',
                'code' => 'USD',
                'symbol' => '$',
                'exchange_rate' => 1.000000,
                'is_base' => true,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->command->info('✅ Initial data seeded successfully!');
            $this->command->info('Demo Tenant: demo-company');
            $this->command->info('Demo Admin: admin@demo.com / password');
        });
    }
}
