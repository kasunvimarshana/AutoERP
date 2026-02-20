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

    public function posSalesSummary(string $tenantId, string $dateFrom, string $dateTo, ?string $businessLocationId = null): array
    {
        $query = DB::table('pos_transactions')
            ->where('tenant_id', $tenantId)
            ->where('status', 'completed')
            ->whereDate('created_at', '>=', $dateFrom)
            ->whereDate('created_at', '<=', $dateTo);

        if ($businessLocationId !== null) {
            $query->where('business_location_id', $businessLocationId);
        }

        $summary = $query->selectRaw(
            'count(*) as total_transactions,
             sum(total) as gross_sales,
             sum(discount_amount) as total_discounts,
             sum(tax_amount) as total_tax,
             sum(total) as net_sales'
        )->first();

        $byLocation = (clone $query)
            ->join('business_locations', 'business_locations.id', '=', 'pos_transactions.business_location_id')
            ->selectRaw('pos_transactions.business_location_id, business_locations.name, count(*) as count, sum(pos_transactions.total) as total')
            ->groupBy('pos_transactions.business_location_id', 'business_locations.name')
            ->get();

        return [
            'period' => ['from' => $dateFrom, 'to' => $dateTo],
            'total_transactions' => (int) ($summary->total_transactions ?? 0),
            'gross_sales' => number_format((float) ($summary->gross_sales ?? 0), 2, '.', ''),
            'total_discounts' => number_format((float) ($summary->total_discounts ?? 0), 2, '.', ''),
            'total_tax' => number_format((float) ($summary->total_tax ?? 0), 2, '.', ''),
            'net_sales' => number_format((float) ($summary->net_sales ?? 0), 2, '.', ''),
            'by_location' => $byLocation->values()->all(),
        ];
    }

    public function purchaseSummary(string $tenantId, string $dateFrom, string $dateTo): array
    {
        $rows = DB::table('purchases')
            ->where('tenant_id', $tenantId)
            ->whereDate('purchase_date', '>=', $dateFrom)
            ->whereDate('purchase_date', '<=', $dateTo)
            ->selectRaw('status, count(*) as count, sum(total) as total_amount')
            ->groupBy('status')
            ->get();

        return [
            'period' => ['from' => $dateFrom, 'to' => $dateTo],
            'by_status' => $rows->values()->all(),
            'total_count' => $rows->sum('count'),
            'total_amount' => number_format((float) $rows->sum('total_amount'), 2, '.', ''),
        ];
    }

    public function expenseSummary(string $tenantId, string $dateFrom, string $dateTo, ?string $businessLocationId = null): array
    {
        $query = DB::table('expenses')
            ->join('expense_categories', 'expense_categories.id', '=', 'expenses.expense_category_id')
            ->where('expenses.tenant_id', $tenantId)
            ->whereNull('expenses.deleted_at')
            ->whereDate('expenses.expense_date', '>=', $dateFrom)
            ->whereDate('expenses.expense_date', '<=', $dateTo);

        if ($businessLocationId !== null) {
            $query->where('expenses.business_location_id', $businessLocationId);
        }

        $byCategory = $query
            ->selectRaw('expenses.expense_category_id, expense_categories.name, count(*) as count, sum(expenses.amount) as total_amount')
            ->groupBy('expenses.expense_category_id', 'expense_categories.name')
            ->orderByDesc('total_amount')
            ->get();

        return [
            'period' => ['from' => $dateFrom, 'to' => $dateTo],
            'by_category' => $byCategory->values()->all(),
            'total_count' => $byCategory->sum('count'),
            'total_amount' => number_format((float) $byCategory->sum('total_amount'), 2, '.', ''),
        ];
    }

    // ── parity reports ────────────────────────────────────────

    /**
     * Profit & Loss report: gross sales minus COGS and expenses.
     */
    public function profitLoss(string $tenantId, string $dateFrom, string $dateTo, ?string $businessLocationId = null): array
    {
        // Gross sales from POS transactions
        $posQuery = DB::table('pos_transactions')
            ->where('tenant_id', $tenantId)
            ->where('status', 'completed')
            ->whereDate('created_at', '>=', $dateFrom)
            ->whereDate('created_at', '<=', $dateTo);

        if ($businessLocationId) {
            $posQuery->where('business_location_id', $businessLocationId);
        }

        $posData = $posQuery->selectRaw(
            'sum(total) as gross_sales, sum(discount_amount) as total_discounts, sum(tax_amount) as total_tax'
        )->first();

        // Total expenses
        $expenseQuery = DB::table('expenses')
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->whereDate('expense_date', '>=', $dateFrom)
            ->whereDate('expense_date', '<=', $dateTo);

        if ($businessLocationId) {
            $expenseQuery->where('business_location_id', $businessLocationId);
        }

        $totalExpenses = $expenseQuery->sum('amount');

        // Purchase costs received in period
        $purchaseCost = DB::table('purchases')
            ->where('tenant_id', $tenantId)
            ->whereIn('status', ['received', 'partial'])
            ->whereDate('purchase_date', '>=', $dateFrom)
            ->whereDate('purchase_date', '<=', $dateTo)
            ->sum('total');

        $grossSales = (float) ($posData->gross_sales ?? 0);
        $totalExpensesFloat = (float) $totalExpenses;
        $grossProfit = bcsub((string) $grossSales, (string) $purchaseCost, 2);
        $netProfit = bcsub($grossProfit, (string) $totalExpensesFloat, 2);

        return [
            'period' => ['from' => $dateFrom, 'to' => $dateTo],
            'gross_sales' => number_format($grossSales, 2, '.', ''),
            'total_discounts' => number_format((float) ($posData->total_discounts ?? 0), 2, '.', ''),
            'total_tax' => number_format((float) ($posData->total_tax ?? 0), 2, '.', ''),
            'purchase_cost' => number_format((float) $purchaseCost, 2, '.', ''),
            'gross_profit' => number_format((float) $grossProfit, 2, '.', ''),
            'total_expenses' => number_format($totalExpensesFloat, 2, '.', ''),
            'net_profit' => number_format((float) $netProfit, 2, '.', ''),
        ];
    }

    /**
     * Tax report: taxes collected on POS and order sales.
     */
    public function taxReport(string $tenantId, string $dateFrom, string $dateTo, ?string $businessLocationId = null): array
    {
        $query = DB::table('pos_transactions')
            ->where('tenant_id', $tenantId)
            ->where('status', 'completed')
            ->whereDate('created_at', '>=', $dateFrom)
            ->whereDate('created_at', '<=', $dateTo);

        if ($businessLocationId) {
            $query->where('business_location_id', $businessLocationId);
        }

        $totals = $query->selectRaw('sum(total) as gross_sales, sum(tax_amount) as total_tax_collected')->first();

        return [
            'period' => ['from' => $dateFrom, 'to' => $dateTo],
            'gross_sales' => number_format((float) ($totals->gross_sales ?? 0), 2, '.', ''),
            'total_tax_collected' => number_format((float) ($totals->total_tax_collected ?? 0), 2, '.', ''),
        ];
    }

    /**
     * Stock expiry report: items expiring within a given number of days.
     */
    public function stockExpiry(string $tenantId, int $daysAhead = 30, ?string $warehouseId = null): array
    {
        $query = DB::table('stock_batches')
            ->join('products', 'products.id', '=', 'stock_batches.product_id')
            ->join('warehouses', 'warehouses.id', '=', 'stock_batches.warehouse_id')
            ->where('stock_batches.tenant_id', $tenantId)
            ->where('stock_batches.quantity_remaining', '>', 0)
            ->whereNotNull('stock_batches.expiry_date')
            ->whereDate('stock_batches.expiry_date', '<=', now()->addDays($daysAhead)->toDateString());

        if ($warehouseId) {
            $query->where('stock_batches.warehouse_id', $warehouseId);
        }

        $items = $query->select([
            'stock_batches.id',
            'stock_batches.product_id',
            'products.name as product_name',
            'stock_batches.warehouse_id',
            'warehouses.name as warehouse_name',
            'stock_batches.batch_number',
            'stock_batches.lot_number',
            'stock_batches.expiry_date',
            'stock_batches.quantity_remaining',
            'stock_batches.cost_per_unit',
        ])->orderBy('stock_batches.expiry_date')->get();

        return [
            'days_ahead' => $daysAhead,
            'items' => $items->values()->all(),
            'total_items' => $items->count(),
        ];
    }

    /**
     * Cash register / till report.
     */
    public function registerReport(string $tenantId, string $dateFrom, string $dateTo, ?string $businessLocationId = null): array
    {
        $query = DB::table('cash_registers')
            ->leftJoin('cash_register_transactions', 'cash_register_transactions.cash_register_id', '=', 'cash_registers.id')
            ->where('cash_registers.tenant_id', $tenantId)
            ->whereDate('cash_registers.created_at', '>=', $dateFrom)
            ->whereDate('cash_registers.created_at', '<=', $dateTo);

        if ($businessLocationId) {
            $query->where('cash_registers.business_location_id', $businessLocationId);
        }

        $summary = $query->selectRaw(
            'cash_registers.id,
             cash_registers.name,
             sum(case when cash_register_transactions.type = \'pay_in\' then cash_register_transactions.amount else 0 end) as total_pay_in,
             sum(case when cash_register_transactions.type = \'pay_out\' then cash_register_transactions.amount else 0 end) as total_pay_out,
             cash_registers.closing_balance'
        )
            ->groupBy('cash_registers.id', 'cash_registers.name', 'cash_registers.closing_balance')
            ->get();

        return [
            'period' => ['from' => $dateFrom, 'to' => $dateTo],
            'registers' => $summary->values()->all(),
        ];
    }

    /**
     * Customer group sales report.
     */
    public function customerGroupReport(string $tenantId, string $dateFrom, string $dateTo): array
    {
        $rows = DB::table('pos_transactions')
            ->leftJoin('customer_groups', 'customer_groups.id', '=', 'pos_transactions.customer_group_id')
            ->where('pos_transactions.tenant_id', $tenantId)
            ->where('pos_transactions.status', 'completed')
            ->whereDate('pos_transactions.created_at', '>=', $dateFrom)
            ->whereDate('pos_transactions.created_at', '<=', $dateTo)
            ->selectRaw(
                'pos_transactions.customer_group_id,
                 coalesce(customer_groups.name, \'No Group\') as group_name,
                 count(*) as transaction_count,
                 sum(pos_transactions.total) as total_amount'
            )
            ->groupBy('pos_transactions.customer_group_id', 'customer_groups.name')
            ->orderByDesc('total_amount')
            ->get();

        return [
            'period' => ['from' => $dateFrom, 'to' => $dateTo],
            'by_group' => $rows->values()->all(),
            'total_amount' => number_format((float) $rows->sum('total_amount'), 2, '.', ''),
        ];
    }

    /**
     * Product sell report: individual product sales quantities and revenue.
     */
    public function productSellReport(string $tenantId, string $dateFrom, string $dateTo, ?string $businessLocationId = null): array
    {
        $query = DB::table('pos_transaction_lines')
            ->join('pos_transactions', 'pos_transactions.id', '=', 'pos_transaction_lines.pos_transaction_id')
            ->join('products', 'products.id', '=', 'pos_transaction_lines.product_id')
            ->where('pos_transactions.tenant_id', $tenantId)
            ->where('pos_transactions.status', 'completed')
            ->whereDate('pos_transactions.created_at', '>=', $dateFrom)
            ->whereDate('pos_transactions.created_at', '<=', $dateTo);

        if ($businessLocationId) {
            $query->where('pos_transactions.business_location_id', $businessLocationId);
        }

        $rows = $query->selectRaw(
            'pos_transaction_lines.product_id,
             products.name as product_name,
             sum(pos_transaction_lines.quantity) as total_qty_sold,
             sum(pos_transaction_lines.line_total) as total_revenue,
             sum(pos_transaction_lines.discount_amount) as total_discount,
             sum(pos_transaction_lines.tax_amount) as total_tax'
        )
            ->groupBy('pos_transaction_lines.product_id', 'products.name')
            ->orderByDesc('total_revenue')
            ->get();

        return [
            'period' => ['from' => $dateFrom, 'to' => $dateTo],
            'products' => $rows->values()->all(),
            'total_revenue' => number_format((float) $rows->sum('total_revenue'), 2, '.', ''),
        ];
    }

    /**
     * Product purchase report: individual product purchase quantities and cost.
     */
    public function productPurchaseReport(string $tenantId, string $dateFrom, string $dateTo, ?string $supplierId = null): array
    {
        $query = DB::table('purchase_lines')
            ->join('purchases', 'purchases.id', '=', 'purchase_lines.purchase_id')
            ->join('products', 'products.id', '=', 'purchase_lines.product_id')
            ->where('purchases.tenant_id', $tenantId)
            ->whereDate('purchases.purchase_date', '>=', $dateFrom)
            ->whereDate('purchases.purchase_date', '<=', $dateTo);

        if ($supplierId) {
            $query->where('purchases.supplier_id', $supplierId);
        }

        $rows = $query->selectRaw(
            'purchase_lines.product_id,
             products.name as product_name,
             sum(purchase_lines.quantity_ordered) as total_qty_ordered,
             sum(purchase_lines.quantity_received) as total_qty_received,
             sum(purchase_lines.line_total) as total_cost'
        )
            ->groupBy('purchase_lines.product_id', 'products.name')
            ->orderByDesc('total_cost')
            ->get();

        return [
            'period' => ['from' => $dateFrom, 'to' => $dateTo],
            'products' => $rows->values()->all(),
            'total_cost' => number_format((float) $rows->sum('total_cost'), 2, '.', ''),
        ];
    }

    /**
     * Sales representative report: sales by commission agent.
     */
    public function salesRepresentativeReport(string $tenantId, string $dateFrom, string $dateTo): array
    {
        $rows = DB::table('pos_transactions')
            ->join('users', 'users.id', '=', 'pos_transactions.created_by')
            ->where('pos_transactions.tenant_id', $tenantId)
            ->where('pos_transactions.status', 'completed')
            ->where('users.is_sales_commission_agent', true)
            ->whereDate('pos_transactions.created_at', '>=', $dateFrom)
            ->whereDate('pos_transactions.created_at', '<=', $dateTo)
            ->selectRaw(
                'pos_transactions.created_by as user_id,
                 users.name as user_name,
                 users.commission_rate,
                 count(*) as transaction_count,
                 sum(pos_transactions.total) as total_sales'
            )
            ->groupBy('pos_transactions.created_by', 'users.name', 'users.commission_rate')
            ->orderByDesc('total_sales')
            ->get();

        return [
            'period' => ['from' => $dateFrom, 'to' => $dateTo],
            'agents' => $rows->values()->all(),
        ];
    }

    /**
     * Trending products: top products by units sold in a period.
     */
    public function trendingProducts(string $tenantId, string $dateFrom, string $dateTo, int $limit = 10): array
    {
        $rows = DB::table('pos_transaction_lines')
            ->join('pos_transactions', 'pos_transactions.id', '=', 'pos_transaction_lines.pos_transaction_id')
            ->join('products', 'products.id', '=', 'pos_transaction_lines.product_id')
            ->where('pos_transactions.tenant_id', $tenantId)
            ->where('pos_transactions.status', 'completed')
            ->whereDate('pos_transactions.created_at', '>=', $dateFrom)
            ->whereDate('pos_transactions.created_at', '<=', $dateTo)
            ->selectRaw(
                'pos_transaction_lines.product_id,
                 products.name as product_name,
                 sum(pos_transaction_lines.quantity) as total_qty_sold,
                 sum(pos_transaction_lines.line_total) as total_revenue'
            )
            ->groupBy('pos_transaction_lines.product_id', 'products.name')
            ->orderByDesc('total_qty_sold')
            ->limit($limit)
            ->get();

        return $rows->values()->all();
    }

    /**
     * Lot report: stock by lot number.
     */
    public function lotReport(string $tenantId, ?string $warehouseId = null): array
    {
        $query = DB::table('stock_batches')
            ->join('products', 'products.id', '=', 'stock_batches.product_id')
            ->join('warehouses', 'warehouses.id', '=', 'stock_batches.warehouse_id')
            ->where('stock_batches.tenant_id', $tenantId)
            ->where('stock_batches.quantity_remaining', '>', 0)
            ->whereNotNull('stock_batches.lot_number');

        if ($warehouseId) {
            $query->where('stock_batches.warehouse_id', $warehouseId);
        }

        $rows = $query->select([
            'stock_batches.lot_number',
            'stock_batches.product_id',
            'products.name as product_name',
            'stock_batches.warehouse_id',
            'warehouses.name as warehouse_name',
            'stock_batches.quantity_remaining',
            'stock_batches.cost_per_unit',
            'stock_batches.expiry_date',
        ])->orderBy('stock_batches.lot_number')->get();

        return [
            'items' => $rows->values()->all(),
            'total_items' => $rows->count(),
        ];
    }
}
