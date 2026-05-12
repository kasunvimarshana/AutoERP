<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CanonicalPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            ['permission_code' => 'tenant.manage', 'permission_name' => 'Manage Tenant', 'module_code' => 'tenant'],
            ['permission_code' => 'users.manage', 'permission_name' => 'Manage Users', 'module_code' => 'identity'],
            ['permission_code' => 'roles.manage', 'permission_name' => 'Manage Roles', 'module_code' => 'identity'],
            ['permission_code' => 'org_units.manage', 'permission_name' => 'Manage Organization Units', 'module_code' => 'organization'],
            ['permission_code' => 'parties.manage', 'permission_name' => 'Manage Parties', 'module_code' => 'party'],
            ['permission_code' => 'products.manage', 'permission_name' => 'Manage Products', 'module_code' => 'product'],
            ['permission_code' => 'price_lists.manage', 'permission_name' => 'Manage Price Lists', 'module_code' => 'product'],
            ['permission_code' => 'warehouses.manage', 'permission_name' => 'Manage Warehouses', 'module_code' => 'warehouse'],
            ['permission_code' => 'inventory.view', 'permission_name' => 'View Inventory', 'module_code' => 'inventory'],
            ['permission_code' => 'inventory.move', 'permission_name' => 'Post Inventory Movements', 'module_code' => 'inventory'],
            ['permission_code' => 'inventory.count', 'permission_name' => 'Run Stock Counts', 'module_code' => 'inventory'],
            ['permission_code' => 'sales.manage', 'permission_name' => 'Manage Sales Documents', 'module_code' => 'commercial'],
            ['permission_code' => 'purchase.manage', 'permission_name' => 'Manage Purchase Documents', 'module_code' => 'commercial'],
            ['permission_code' => 'service.manage', 'permission_name' => 'Manage Service Documents', 'module_code' => 'commercial'],
            ['permission_code' => 'finance.view', 'permission_name' => 'View Finance', 'module_code' => 'finance'],
            ['permission_code' => 'journals.post', 'permission_name' => 'Post Journal Entries', 'module_code' => 'finance'],
            ['permission_code' => 'payments.manage', 'permission_name' => 'Manage Payments', 'module_code' => 'finance'],
            ['permission_code' => 'bank.reconcile', 'permission_name' => 'Reconcile Bank Accounts', 'module_code' => 'finance'],
            ['permission_code' => 'audit.view', 'permission_name' => 'View Audit Logs', 'module_code' => 'audit'],
            ['permission_code' => 'integration.manage', 'permission_name' => 'Manage Integrations', 'module_code' => 'integration'],
        ];

        $tenantIds = DB::table('tenants')->pluck('id');

        foreach ($tenantIds as $tenantId) {
            foreach ($permissions as $permission) {
                DB::table('permissions')->updateOrInsert(
                    [
                        'tenant_id' => $tenantId,
                        'permission_code' => $permission['permission_code'],
                    ],
                    array_merge($permission, [
                        'tenant_id' => $tenantId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ])
                );
            }
        }
    }
}
