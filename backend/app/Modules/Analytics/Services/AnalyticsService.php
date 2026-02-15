<?php

declare(strict_types=1);

namespace App\Modules\Analytics\Services;

use App\Core\Services\BaseService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class AnalyticsService extends BaseService
{
    public function getDashboardMetrics(): array
    {
        $tenantId = auth()->user()->tenant_id;

        return Cache::remember("dashboard_metrics_{$tenantId}", 300, function () use ($tenantId) {
            return [
                'sales' => $this->getSalesMetrics($tenantId),
                'inventory' => $this->getInventoryMetrics($tenantId),
                'customers' => $this->getCustomerMetrics($tenantId),
                'financial' => $this->getFinancialMetrics($tenantId),
            ];
        });
    }

    private function getSalesMetrics(int $tenantId): array
    {
        $today = now()->startOfDay();
        $monthStart = now()->startOfMonth();

        return [
            'today_orders' => DB::table('sales_orders')
                ->where('tenant_id', $tenantId)
                ->whereDate('order_date', $today)
                ->count(),
            'today_revenue' => DB::table('sales_orders')
                ->where('tenant_id', $tenantId)
                ->whereDate('order_date', $today)
                ->sum('total_amount'),
            'month_orders' => DB::table('sales_orders')
                ->where('tenant_id', $tenantId)
                ->whereDate('order_date', '>=', $monthStart)
                ->count(),
            'month_revenue' => DB::table('sales_orders')
                ->where('tenant_id', $tenantId)
                ->whereDate('order_date', '>=', $monthStart)
                ->sum('total_amount'),
        ];
    }

    private function getInventoryMetrics(int $tenantId): array
    {
        return [
            'total_products' => DB::table('products')
                ->where('tenant_id', $tenantId)
                ->count(),
            'low_stock_items' => DB::table('products')
                ->where('tenant_id', $tenantId)
                ->whereRaw('current_stock <= reorder_level')
                ->count(),
            'out_of_stock' => DB::table('products')
                ->where('tenant_id', $tenantId)
                ->where('current_stock', 0)
                ->count(),
        ];
    }

    private function getCustomerMetrics(int $tenantId): array
    {
        return [
            'total_customers' => DB::table('customers')
                ->where('tenant_id', $tenantId)
                ->where('status', 'active')
                ->count(),
            'new_customers_this_month' => DB::table('customers')
                ->where('tenant_id', $tenantId)
                ->whereDate('created_at', '>=', now()->startOfMonth())
                ->count(),
        ];
    }

    private function getFinancialMetrics(int $tenantId): array
    {
        $monthStart = now()->startOfMonth();

        return [
            'outstanding_invoices' => DB::table('invoices')
                ->where('tenant_id', $tenantId)
                ->where('status', 'sent')
                ->sum('total_amount'),
            'overdue_invoices' => DB::table('invoices')
                ->where('tenant_id', $tenantId)
                ->where('status', 'overdue')
                ->sum('total_amount'),
            'month_revenue' => DB::table('invoices')
                ->where('tenant_id', $tenantId)
                ->whereDate('invoice_date', '>=', $monthStart)
                ->where('status', 'paid')
                ->sum('total_amount'),
        ];
    }
}
