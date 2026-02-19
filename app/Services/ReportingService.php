<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class ReportingService
{
    public function salesSummary(string $tenantId, string $dateFrom, string $dateTo, ?string $organizationId = null): array
    {
        $query = DB::table('orders')
            ->where('tenant_id', $tenantId)
            ->whereDate('created_at', '>=', $dateFrom)
            ->whereDate('created_at', '<=', $dateTo);

        if ($organizationId !== null) {
            $query->where('organization_id', $organizationId);
        }

        $byStatus = $query->selectRaw('status, count(*) as count, sum(total_amount) as total_revenue')
            ->groupBy('status')
            ->get();

        $totalCount = $byStatus->sum('count');
        $totalRevenue = $byStatus->sum('total_revenue');

        return [
            'period' => ['from' => $dateFrom, 'to' => $dateTo],
            'by_status' => $byStatus->values()->all(),
            'total_count' => $totalCount,
            'total_revenue' => number_format((float) $totalRevenue, 2, '.', ''),
        ];
    }

    public function inventorySummary(string $tenantId): array
    {
        $total = DB::table('stock_items')
            ->where('tenant_id', $tenantId)
            ->selectRaw('count(*) as total_sku_count, sum(quantity_on_hand) as total_quantity_on_hand')
            ->first();

        $lowStockCount = DB::table('stock_items')
            ->where('tenant_id', $tenantId)
            ->whereColumn('quantity_on_hand', '<=', 'reorder_point')
            ->count();

        return [
            'total_sku_count' => (int) ($total->total_sku_count ?? 0),
            'total_quantity_on_hand' => number_format((float) ($total->total_quantity_on_hand ?? 0), 4, '.', ''),
            'low_stock_count' => $lowStockCount,
        ];
    }

    public function receivablesSummary(string $tenantId): array
    {
        $rows = DB::table('invoices')
            ->where('tenant_id', $tenantId)
            ->selectRaw('status, count(*) as count, sum(total_amount) as total_amount')
            ->groupBy('status')
            ->get();

        return [
            'by_status' => $rows->values()->all(),
            'total_count' => $rows->sum('count'),
            'total_amount' => number_format((float) $rows->sum('total_amount'), 2, '.', ''),
        ];
    }

    public function topProducts(string $tenantId, string $dateFrom, string $dateTo, int $limit = 10): array
    {
        $rows = DB::table('order_lines')
            ->join('orders', 'orders.id', '=', 'order_lines.order_id')
            ->join('products', 'products.id', '=', 'order_lines.product_id')
            ->where('orders.tenant_id', $tenantId)
            ->whereDate('orders.created_at', '>=', $dateFrom)
            ->whereDate('orders.created_at', '<=', $dateTo)
            ->selectRaw('order_lines.product_id, products.name, sum(order_lines.qty_ordered) as total_qty, sum(order_lines.line_total) as total_revenue')
            ->groupBy('order_lines.product_id', 'products.name')
            ->orderByDesc('total_revenue')
            ->limit($limit)
            ->get();

        return $rows->values()->all();
    }
}
