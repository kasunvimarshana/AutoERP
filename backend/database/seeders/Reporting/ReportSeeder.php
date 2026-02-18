<?php

namespace Database\Seeders\Reporting;

use App\Models\Tenant;
use App\Models\User;
use App\Modules\Reporting\Enums\ReportType;
use App\Modules\Reporting\Models\Report;
use Illuminate\Database\Seeder;

class ReportSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tenant = Tenant::first();
        $user = User::first();

        if (! $tenant || ! $user) {
            $this->command->warn('No tenant or user found. Please seed tenants and users first.');

            return;
        }

        $reports = [
            // Inventory Reports
            [
                'name' => 'Stock Level Report',
                'description' => 'Current stock levels by product and warehouse',
                'report_type' => ReportType::TABLE->value,
                'module' => 'inventory',
                'query_config' => [
                    'pre_built' => true,
                    'report_name' => 'stock_level',
                ],
                'is_public' => true,
            ],
            [
                'name' => 'Stock Movement Report',
                'description' => 'Stock movements by period and type',
                'report_type' => ReportType::TABLE->value,
                'module' => 'inventory',
                'query_config' => [
                    'pre_built' => true,
                    'report_name' => 'stock_movement',
                ],
                'is_public' => true,
            ],
            [
                'name' => 'Low Stock Alert Report',
                'description' => 'Products below reorder level',
                'report_type' => ReportType::TABLE->value,
                'module' => 'inventory',
                'query_config' => [
                    'pre_built' => true,
                    'report_name' => 'low_stock_alert',
                ],
                'is_public' => true,
            ],
            [
                'name' => 'Stock Valuation Report',
                'description' => 'Total inventory value by product',
                'report_type' => ReportType::TABLE->value,
                'module' => 'inventory',
                'query_config' => [
                    'pre_built' => true,
                    'report_name' => 'stock_valuation',
                ],
                'is_public' => true,
            ],
            [
                'name' => 'Expiry Report',
                'description' => 'Products expiring within specified days',
                'report_type' => ReportType::TABLE->value,
                'module' => 'inventory',
                'query_config' => [
                    'pre_built' => true,
                    'report_name' => 'expiry_report',
                ],
                'is_public' => true,
            ],

            // Sales Reports
            [
                'name' => 'Sales Summary Report',
                'description' => 'Sales summary by period, product, and customer',
                'report_type' => ReportType::TABLE->value,
                'module' => 'sales',
                'query_config' => [
                    'pre_built' => true,
                    'report_name' => 'sales_summary',
                ],
                'is_public' => true,
            ],
            [
                'name' => 'Top Products Report',
                'description' => 'Best selling products by revenue',
                'report_type' => ReportType::CHART->value,
                'module' => 'sales',
                'query_config' => [
                    'pre_built' => true,
                    'report_name' => 'top_products',
                ],
                'is_public' => true,
            ],
            [
                'name' => 'Customer Sales Report',
                'description' => 'Sales analysis by customer',
                'report_type' => ReportType::TABLE->value,
                'module' => 'sales',
                'query_config' => [
                    'pre_built' => true,
                    'report_name' => 'customer_sales',
                ],
                'is_public' => true,
            ],
            [
                'name' => 'Sales Trend Analysis',
                'description' => 'Daily sales trends over time',
                'report_type' => ReportType::CHART->value,
                'module' => 'sales',
                'query_config' => [
                    'pre_built' => true,
                    'report_name' => 'sales_trend',
                ],
                'is_public' => true,
            ],
            [
                'name' => 'Invoice Aging Report',
                'description' => 'Overdue invoices by customer',
                'report_type' => ReportType::TABLE->value,
                'module' => 'sales',
                'query_config' => [
                    'pre_built' => true,
                    'report_name' => 'invoice_aging',
                ],
                'is_public' => true,
            ],

            // Purchase Reports
            [
                'name' => 'Purchase Summary Report',
                'description' => 'Purchase orders by period and supplier',
                'report_type' => ReportType::TABLE->value,
                'module' => 'procurement',
                'query_config' => [
                    'pre_built' => true,
                    'report_name' => 'purchase_summary',
                ],
                'is_public' => true,
            ],
            [
                'name' => 'Supplier Performance Report',
                'description' => 'Supplier performance metrics',
                'report_type' => ReportType::TABLE->value,
                'module' => 'procurement',
                'query_config' => [
                    'pre_built' => true,
                    'report_name' => 'supplier_performance',
                ],
                'is_public' => true,
            ],
            [
                'name' => 'Purchase Order Status Report',
                'description' => 'Current status of all purchase orders',
                'report_type' => ReportType::TABLE->value,
                'module' => 'procurement',
                'query_config' => [
                    'pre_built' => true,
                    'report_name' => 'purchase_order_status',
                ],
                'is_public' => true,
            ],

            // Manufacturing Reports
            [
                'name' => 'Production Summary Report',
                'description' => 'Production orders by product and period',
                'report_type' => ReportType::TABLE->value,
                'module' => 'manufacturing',
                'query_config' => [
                    'pre_built' => true,
                    'report_name' => 'production_summary',
                ],
                'is_public' => true,
            ],
            [
                'name' => 'Production Efficiency Report',
                'description' => 'Production efficiency metrics',
                'report_type' => ReportType::KPI->value,
                'module' => 'manufacturing',
                'query_config' => [
                    'pre_built' => true,
                    'report_name' => 'production_efficiency',
                ],
                'is_public' => true,
            ],
            [
                'name' => 'Material Consumption Report',
                'description' => 'Materials consumed in production',
                'report_type' => ReportType::TABLE->value,
                'module' => 'manufacturing',
                'query_config' => [
                    'pre_built' => true,
                    'report_name' => 'material_consumption',
                ],
                'is_public' => true,
            ],
            [
                'name' => 'Work Order Status Report',
                'description' => 'Current status of all work orders',
                'report_type' => ReportType::TABLE->value,
                'module' => 'manufacturing',
                'query_config' => [
                    'pre_built' => true,
                    'report_name' => 'work_order_status',
                ],
                'is_public' => true,
            ],

            // Financial Reports
            [
                'name' => 'Cash Flow Report',
                'description' => 'Cash inflows and outflows over time',
                'report_type' => ReportType::CHART->value,
                'module' => 'finance',
                'query_config' => [
                    'pre_built' => true,
                    'report_name' => 'cash_flow',
                ],
                'is_public' => true,
            ],
            [
                'name' => 'Accounts Receivable Aging',
                'description' => 'Aging analysis of receivables',
                'report_type' => ReportType::TABLE->value,
                'module' => 'finance',
                'query_config' => [
                    'pre_built' => true,
                    'report_name' => 'accounts_receivable_aging',
                ],
                'is_public' => true,
            ],
            [
                'name' => 'Accounts Payable Aging',
                'description' => 'Aging analysis of payables',
                'report_type' => ReportType::TABLE->value,
                'module' => 'finance',
                'query_config' => [
                    'pre_built' => true,
                    'report_name' => 'accounts_payable_aging',
                ],
                'is_public' => true,
            ],
        ];

        foreach ($reports as $reportData) {
            Report::create(array_merge($reportData, [
                'tenant_id' => $tenant->id,
                'created_by_id' => $user->id,
            ]));
        }

        $this->command->info('Pre-built reports seeded successfully!');
    }
}
