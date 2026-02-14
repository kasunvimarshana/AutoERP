<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            // IAM Module
            ['name' => 'View Users', 'slug' => 'users.view', 'module' => 'IAM', 'group' => 'Users', 'is_system' => true],
            ['name' => 'Create Users', 'slug' => 'users.create', 'module' => 'IAM', 'group' => 'Users', 'is_system' => true],
            ['name' => 'Edit Users', 'slug' => 'users.edit', 'module' => 'IAM', 'group' => 'Users', 'is_system' => true],
            ['name' => 'Delete Users', 'slug' => 'users.delete', 'module' => 'IAM', 'group' => 'Users', 'is_system' => true],
            ['name' => 'View Roles', 'slug' => 'roles.view', 'module' => 'IAM', 'group' => 'Roles', 'is_system' => true],
            ['name' => 'Create Roles', 'slug' => 'roles.create', 'module' => 'IAM', 'group' => 'Roles', 'is_system' => true],
            ['name' => 'Edit Roles', 'slug' => 'roles.edit', 'module' => 'IAM', 'group' => 'Roles', 'is_system' => true],
            ['name' => 'Delete Roles', 'slug' => 'roles.delete', 'module' => 'IAM', 'group' => 'Roles', 'is_system' => true],
            ['name' => 'Assign Permissions', 'slug' => 'permissions.assign', 'module' => 'IAM', 'group' => 'Permissions', 'is_system' => true],
            
            // Organization Module
            ['name' => 'View Organizations', 'slug' => 'organizations.view', 'module' => 'Organization', 'group' => 'Organizations', 'is_system' => true],
            ['name' => 'Create Organizations', 'slug' => 'organizations.create', 'module' => 'Organization', 'group' => 'Organizations', 'is_system' => true],
            ['name' => 'Edit Organizations', 'slug' => 'organizations.edit', 'module' => 'Organization', 'group' => 'Organizations', 'is_system' => true],
            ['name' => 'Delete Organizations', 'slug' => 'organizations.delete', 'module' => 'Organization', 'group' => 'Organizations', 'is_system' => true],
            ['name' => 'View Branches', 'slug' => 'branches.view', 'module' => 'Organization', 'group' => 'Branches', 'is_system' => true],
            ['name' => 'Create Branches', 'slug' => 'branches.create', 'module' => 'Organization', 'group' => 'Branches', 'is_system' => true],
            ['name' => 'Edit Branches', 'slug' => 'branches.edit', 'module' => 'Organization', 'group' => 'Branches', 'is_system' => true],
            ['name' => 'Delete Branches', 'slug' => 'branches.delete', 'module' => 'Organization', 'group' => 'Branches', 'is_system' => true],
            
            // Inventory Module
            ['name' => 'View Products', 'slug' => 'products.view', 'module' => 'Inventory', 'group' => 'Products', 'is_system' => true],
            ['name' => 'Create Products', 'slug' => 'products.create', 'module' => 'Inventory', 'group' => 'Products', 'is_system' => true],
            ['name' => 'Edit Products', 'slug' => 'products.edit', 'module' => 'Inventory', 'group' => 'Products', 'is_system' => true],
            ['name' => 'Delete Products', 'slug' => 'products.delete', 'module' => 'Inventory', 'group' => 'Products', 'is_system' => true],
            ['name' => 'View Stock', 'slug' => 'stock.view', 'module' => 'Inventory', 'group' => 'Stock', 'is_system' => true],
            ['name' => 'Adjust Stock', 'slug' => 'stock.adjust', 'module' => 'Inventory', 'group' => 'Stock', 'is_system' => true],
            ['name' => 'Transfer Stock', 'slug' => 'stock.transfer', 'module' => 'Inventory', 'group' => 'Stock', 'is_system' => true],
            
            // Pricing Module
            ['name' => 'View Price Lists', 'slug' => 'pricelists.view', 'module' => 'Pricing', 'group' => 'Price Lists', 'is_system' => true],
            ['name' => 'Create Price Lists', 'slug' => 'pricelists.create', 'module' => 'Pricing', 'group' => 'Price Lists', 'is_system' => true],
            ['name' => 'Edit Price Lists', 'slug' => 'pricelists.edit', 'module' => 'Pricing', 'group' => 'Price Lists', 'is_system' => true],
            ['name' => 'Delete Price Lists', 'slug' => 'pricelists.delete', 'module' => 'Pricing', 'group' => 'Price Lists', 'is_system' => true],
        ];

        foreach ($permissions as $permission) {
            \App\Modules\IAM\Models\Permission::firstOrCreate(
                ['slug' => $permission['slug']],
                $permission
            );
        }
    }
}
