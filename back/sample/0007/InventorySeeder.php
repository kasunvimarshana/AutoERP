<?php

namespace Database\Seeders;

use App\Models\{Organization, InventorySettings, Permission, Role, DocumentSequence};
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InventorySeeder extends Seeder
{
    public function run(): void
    {
        // ── Permissions ───────────────────────────────────────────────────────
        $permissions = [
            // Inventory
            ['group' => 'inventory',   'slug' => 'inventory.view',        'name' => 'View inventory'],
            ['group' => 'inventory',   'slug' => 'inventory.adjust',       'name' => 'Adjust stock'],
            ['group' => 'inventory',   'slug' => 'inventory.transfer',     'name' => 'Transfer stock'],
            ['group' => 'inventory',   'slug' => 'inventory.count',        'name' => 'Perform physical count'],
            ['group' => 'inventory',   'slug' => 'inventory.count.approve','name' => 'Approve physical count'],
            ['group' => 'inventory',   'slug' => 'batches.manage',         'name' => 'Manage batches and lots'],
            ['group' => 'inventory',   'slug' => 'serials.manage',         'name' => 'Manage serial numbers'],

            // Purchasing
            ['group' => 'purchasing',  'slug' => 'po.view',               'name' => 'View purchase orders'],
            ['group' => 'purchasing',  'slug' => 'po.create',             'name' => 'Create purchase orders'],
            ['group' => 'purchasing',  'slug' => 'po.approve',            'name' => 'Approve purchase orders'],
            ['group' => 'purchasing',  'slug' => 'po.receive',            'name' => 'Receive goods (GRN)'],
            ['group' => 'purchasing',  'slug' => 'suppliers.manage',      'name' => 'Manage suppliers'],
            ['group' => 'purchasing',  'slug' => 'landed_costs.manage',   'name' => 'Manage landed costs'],

            // Sales
            ['group' => 'sales',       'slug' => 'so.view',               'name' => 'View sales orders'],
            ['group' => 'sales',       'slug' => 'so.create',             'name' => 'Create sales orders'],
            ['group' => 'sales',       'slug' => 'so.ship',               'name' => 'Ship orders'],
            ['group' => 'sales',       'slug' => 'so.cancel',             'name' => 'Cancel sales orders'],
            ['group' => 'sales',       'slug' => 'rma.manage',            'name' => 'Process returns (RMA)'],
            ['group' => 'sales',       'slug' => 'customers.manage',      'name' => 'Manage customers'],

            // Catalog
            ['group' => 'catalog',     'slug' => 'products.view',         'name' => 'View products'],
            ['group' => 'catalog',     'slug' => 'products.create',       'name' => 'Create products'],
            ['group' => 'catalog',     'slug' => 'products.edit',         'name' => 'Edit products'],
            ['group' => 'catalog',     'slug' => 'products.delete',       'name' => 'Delete products'],
            ['group' => 'catalog',     'slug' => 'prices.manage',         'name' => 'Manage price lists'],

            // Warehouse
            ['group' => 'warehouse',   'slug' => 'pick.manage',           'name' => 'Manage pick lists'],
            ['group' => 'warehouse',   'slug' => 'shipments.manage',      'name' => 'Manage shipments'],
            ['group' => 'warehouse',   'slug' => 'locations.manage',      'name' => 'Manage warehouse locations'],

            // Production
            ['group' => 'production',  'slug' => 'bom.manage',            'name' => 'Manage bills of materials'],
            ['group' => 'production',  'slug' => 'production.manage',     'name' => 'Manage production orders'],

            // Reports
            ['group' => 'reports',     'slug' => 'reports.view',          'name' => 'View reports'],
            ['group' => 'reports',     'slug' => 'reports.export',        'name' => 'Export reports'],
            ['group' => 'reports',     'slug' => 'audit.view',            'name' => 'View audit logs'],
            ['group' => 'reports',     'slug' => 'valuations.view',       'name' => 'View inventory valuations'],

            // Settings & Admin
            ['group' => 'settings',    'slug' => 'settings.manage',       'name' => 'Manage system settings'],
            ['group' => 'settings',    'slug' => 'users.manage',          'name' => 'Manage users'],
            ['group' => 'settings',    'slug' => 'roles.manage',          'name' => 'Manage roles'],
            ['group' => 'settings',    'slug' => 'warehouses.manage',     'name' => 'Manage warehouses'],
            ['group' => 'settings',    'slug' => 'reorder_rules.manage',  'name' => 'Manage reorder rules'],
            ['group' => 'settings',    'slug' => 'webhooks.manage',       'name' => 'Manage webhooks'],
            ['group' => 'settings',    'slug' => 'api_keys.manage',       'name' => 'Manage API keys'],
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['slug' => $perm['slug']], $perm);
        }

        $this->command->info('Permissions seeded: ' . count($permissions));

        // ── System Roles (organization_id = null = global) ────────────────────
        $roles = [
            [
                'name'        => 'Super Admin',
                'slug'        => 'super_admin',
                'description' => 'Full access to everything across all organizations',
                'is_system'   => true,
                'permissions' => Permission::all()->pluck('slug')->toArray(),
            ],
            [
                'name'        => 'Inventory Manager',
                'slug'        => 'inventory_manager',
                'description' => 'Full inventory management: stock, adjustments, transfers, counts',
                'is_system'   => true,
                'permissions' => [
                    'inventory.view', 'inventory.adjust', 'inventory.transfer',
                    'inventory.count', 'inventory.count.approve',
                    'batches.manage', 'serials.manage',
                    'products.view', 'reports.view', 'valuations.view',
                    'reorder_rules.manage', 'locations.manage',
                ],
            ],
            [
                'name'        => 'Purchasing Manager',
                'slug'        => 'purchasing_manager',
                'description' => 'Manage suppliers, purchase orders, and goods receipt',
                'is_system'   => true,
                'permissions' => [
                    'po.view', 'po.create', 'po.approve', 'po.receive',
                    'suppliers.manage', 'landed_costs.manage',
                    'inventory.view', 'products.view', 'reports.view',
                ],
            ],
            [
                'name'        => 'Sales Manager',
                'slug'        => 'sales_manager',
                'description' => 'Manage customers, sales orders, and fulfillment',
                'is_system'   => true,
                'permissions' => [
                    'so.view', 'so.create', 'so.ship', 'so.cancel',
                    'rma.manage', 'customers.manage', 'pick.manage', 'shipments.manage',
                    'inventory.view', 'products.view', 'prices.manage', 'reports.view',
                ],
            ],
            [
                'name'        => 'Warehouse Operator',
                'slug'        => 'warehouse_operator',
                'description' => 'Pick, pack, ship, and receive goods',
                'is_system'   => true,
                'permissions' => [
                    'inventory.view', 'inventory.transfer',
                    'po.receive', 'so.ship', 'pick.manage', 'shipments.manage',
                    'batches.manage', 'serials.manage',
                ],
            ],
            [
                'name'        => 'Viewer',
                'slug'        => 'viewer',
                'description' => 'Read-only access to inventory and reports',
                'is_system'   => true,
                'permissions' => [
                    'inventory.view', 'products.view', 'po.view', 'so.view',
                    'reports.view', 'valuations.view',
                ],
            ],
        ];

        foreach ($roles as $roleData) {
            $perms = $roleData['permissions'];
            unset($roleData['permissions']);

            $role = Role::firstOrCreate(['slug' => $roleData['slug']], array_merge($roleData, ['organization_id' => null]));

            $permIds = Permission::whereIn('slug', $perms)->pluck('id');
            $role->permissions()->syncWithoutDetaching($permIds);
        }

        $this->command->info('Roles seeded: ' . count($roles));

        // ── Default Document Sequences ────────────────────────────────────────
        // These are created per-org on first use, but we add the schema here
        $this->command->info('Seeder complete. Document sequences created on first use per organization.');
    }
}
