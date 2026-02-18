<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();

        // Insert root menu items
        $menus = [
            [
                'id' => 1,
                'parent_id' => null,
                'name' => 'dashboard',
                'label' => 'Dashboard',
                'icon' => 'tachometer-alt',
                'route' => '/',
                'entity_name' => null,
                'permission' => null,
                'order' => 0,
                'is_active' => true,
                'is_system' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 2,
                'parent_id' => null,
                'name' => 'inventory',
                'label' => 'Inventory',
                'icon' => 'package',
                'route' => null,
                'entity_name' => null,
                'permission' => 'inventory.view',
                'order' => 10,
                'is_active' => true,
                'is_system' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 3,
                'parent_id' => null,
                'name' => 'crm',
                'label' => 'CRM',
                'icon' => 'users',
                'route' => null,
                'entity_name' => null,
                'permission' => 'crm.view',
                'order' => 20,
                'is_active' => true,
                'is_system' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 4,
                'parent_id' => null,
                'name' => 'procurement',
                'label' => 'Procurement',
                'icon' => 'shopping-cart',
                'route' => null,
                'entity_name' => null,
                'permission' => 'procurement.view',
                'order' => 30,
                'is_active' => true,
                'is_system' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 5,
                'parent_id' => null,
                'name' => 'pos',
                'label' => 'Sales & POS',
                'icon' => 'shopping-bag',
                'route' => null,
                'entity_name' => null,
                'permission' => 'pos.view',
                'order' => 40,
                'is_active' => true,
                'is_system' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 6,
                'parent_id' => null,
                'name' => 'manufacturing',
                'label' => 'Manufacturing',
                'icon' => 'industry',
                'route' => null,
                'entity_name' => null,
                'permission' => 'manufacturing.view',
                'order' => 50,
                'is_active' => true,
                'is_system' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 7,
                'parent_id' => null,
                'name' => 'finance',
                'label' => 'Finance',
                'icon' => 'dollar-sign',
                'route' => null,
                'entity_name' => null,
                'permission' => 'finance.view',
                'order' => 60,
                'is_active' => true,
                'is_system' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 8,
                'parent_id' => null,
                'name' => 'reporting',
                'label' => 'Reports',
                'icon' => 'chart-bar',
                'route' => null,
                'entity_name' => null,
                'permission' => 'reports.view',
                'order' => 70,
                'is_active' => true,
                'is_system' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 9,
                'parent_id' => null,
                'name' => 'iam',
                'label' => 'Administration',
                'icon' => 'shield',
                'route' => null,
                'entity_name' => null,
                'permission' => 'iam.view',
                'order' => 80,
                'is_active' => true,
                'is_system' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        // Insert child menu items
        $subMenus = [
            // Inventory submenu
            ['parent_id' => 2, 'name' => 'products', 'label' => 'Products', 'icon' => 'box', 'route' => '/inventory/products', 'entity_name' => 'product', 'permission' => 'products.view', 'order' => 0],
            ['parent_id' => 2, 'name' => 'stock_ledger', 'label' => 'Stock Ledger', 'icon' => 'list', 'route' => '/inventory/stock-ledger', 'entity_name' => 'stock_ledger', 'permission' => 'stock-ledgers.view', 'order' => 1],
            ['parent_id' => 2, 'name' => 'warehouses', 'label' => 'Warehouses', 'icon' => 'warehouse', 'route' => '/inventory/warehouses', 'entity_name' => 'warehouse', 'permission' => 'warehouses.view', 'order' => 2],

            // CRM submenu
            ['parent_id' => 3, 'name' => 'customers', 'label' => 'Customers', 'icon' => 'user', 'route' => '/crm/customers', 'entity_name' => 'customer', 'permission' => 'customers.view', 'order' => 0],

            // Procurement submenu
            ['parent_id' => 4, 'name' => 'suppliers', 'label' => 'Suppliers', 'icon' => 'truck', 'route' => '/procurement/suppliers', 'entity_name' => 'supplier', 'permission' => 'suppliers.view', 'order' => 0],
            ['parent_id' => 4, 'name' => 'purchase_orders', 'label' => 'Purchase Orders', 'icon' => 'shopping-cart', 'route' => '/procurement/purchase-orders', 'entity_name' => 'purchase_order', 'permission' => 'purchase-orders.view', 'order' => 1],

            // POS submenu
            ['parent_id' => 5, 'name' => 'quotations', 'label' => 'Quotations', 'icon' => 'file-text', 'route' => '/pos/quotations', 'entity_name' => 'quotation', 'permission' => 'quotations.view', 'order' => 0],
            ['parent_id' => 5, 'name' => 'sales_orders', 'label' => 'Sales Orders', 'icon' => 'shopping-bag', 'route' => '/pos/sales-orders', 'entity_name' => 'sales_order', 'permission' => 'sales-orders.view', 'order' => 1],
            ['parent_id' => 5, 'name' => 'invoices', 'label' => 'Invoices', 'icon' => 'file-invoice', 'route' => '/pos/invoices', 'entity_name' => 'invoice', 'permission' => 'invoices.view', 'order' => 2],
            ['parent_id' => 5, 'name' => 'payments', 'label' => 'Payments', 'icon' => 'credit-card', 'route' => '/pos/payments', 'entity_name' => 'payment', 'permission' => 'payments.view', 'order' => 3],

            // Manufacturing submenu
            ['parent_id' => 6, 'name' => 'boms', 'label' => 'Bill of Materials', 'icon' => 'list-alt', 'route' => '/manufacturing/boms', 'entity_name' => 'bom', 'permission' => 'boms.view', 'order' => 0],
            ['parent_id' => 6, 'name' => 'production_orders', 'label' => 'Production Orders', 'icon' => 'industry', 'route' => '/manufacturing/production-orders', 'entity_name' => 'production_order', 'permission' => 'production-orders.view', 'order' => 1],
            ['parent_id' => 6, 'name' => 'work_orders', 'label' => 'Work Orders', 'icon' => 'wrench', 'route' => '/manufacturing/work-orders', 'entity_name' => 'work_order', 'permission' => 'work-orders.view', 'order' => 2],

            // Finance submenu
            ['parent_id' => 7, 'name' => 'accounts', 'label' => 'Chart of Accounts', 'icon' => 'book', 'route' => '/finance/accounts', 'entity_name' => 'account', 'permission' => 'accounts.view', 'order' => 0],
            ['parent_id' => 7, 'name' => 'journal_entries', 'label' => 'Journal Entries', 'icon' => 'edit', 'route' => '/finance/journal-entries', 'entity_name' => 'journal_entry', 'permission' => 'journal-entries.view', 'order' => 1],
            ['parent_id' => 7, 'name' => 'financial_reports', 'label' => 'Financial Reports', 'icon' => 'file-alt', 'route' => '/finance/reports', 'entity_name' => null, 'permission' => 'financial-reports.view', 'order' => 2],

            // Reporting submenu
            ['parent_id' => 8, 'name' => 'reports_list', 'label' => 'Reports', 'icon' => 'chart-bar', 'route' => '/reports', 'entity_name' => 'report', 'permission' => 'reports.view', 'order' => 0],
            ['parent_id' => 8, 'name' => 'dashboards_list', 'label' => 'Dashboards', 'icon' => 'tachometer-alt', 'route' => '/reports/dashboards', 'entity_name' => 'dashboard', 'permission' => 'dashboards.view', 'order' => 1],
            ['parent_id' => 8, 'name' => 'analytics', 'label' => 'Analytics', 'icon' => 'chart-line', 'route' => '/reports/analytics', 'entity_name' => null, 'permission' => 'analytics.view', 'order' => 2],

            // IAM submenu
            ['parent_id' => 9, 'name' => 'users', 'label' => 'Users', 'icon' => 'user', 'route' => '/iam/users', 'entity_name' => 'user', 'permission' => 'users.view', 'order' => 0],
            ['parent_id' => 9, 'name' => 'roles', 'label' => 'Roles', 'icon' => 'shield', 'route' => '/iam/roles', 'entity_name' => 'role', 'permission' => 'roles.view', 'order' => 1],
            ['parent_id' => 9, 'name' => 'permissions', 'label' => 'Permissions', 'icon' => 'key', 'route' => '/iam/permissions', 'entity_name' => 'permission', 'permission' => 'permissions.view', 'order' => 2],
        ];

        foreach ($subMenus as &$submenu) {
            $submenu['is_active'] = true;
            $submenu['is_system'] = true;
            $submenu['created_at'] = $now;
            $submenu['updated_at'] = $now;
        }

        DB::table('metadata_menus')->insert($menus);
        DB::table('metadata_menus')->insert($subMenus);

        $this->command->info('Menu structure seeded successfully!');
    }
}
