<?php

namespace App\Modules\Reporting\Services;

use App\Services\BaseService;
use Illuminate\Support\Facades\DB;

/**
 * Analytics Service
 *
 * Handles KPI calculations and analytics metrics.
 */
class AnalyticsService extends BaseService
{
    /**
     * Calculate total revenue for a period
     */
    public function getTotalRevenue(string $startDate, string $endDate): float
    {
        return DB::table('invoices')
            ->whereBetween('invoice_date', [$startDate, $endDate])
            ->whereIn('status', ['paid', 'partially_paid'])
            ->sum('total_amount');
    }

    /**
     * Calculate total expenses for a period
     */
    public function getTotalExpenses(string $startDate, string $endDate): float
    {
        return DB::table('journal_entries as je')
            ->join('journal_entry_lines as jel', 'je.id', '=', 'jel.journal_entry_id')
            ->join('accounts as a', 'jel.account_id', '=', 'a.id')
            ->whereBetween('je.entry_date', [$startDate, $endDate])
            ->where('a.type', 'expense')
            ->where('je.status', 'posted')
            ->sum('jel.debit');
    }

    /**
     * Calculate gross profit margin
     */
    public function getGrossProfitMargin(string $startDate, string $endDate): float
    {
        $revenue = $this->getTotalRevenue($startDate, $endDate);

        if ($revenue <= 0) {
            return 0;
        }

        // Calculate COGS (Cost of Goods Sold)
        $cogs = DB::table('stock_ledgers')
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->where('movement_type', 'sale')
            ->sum('total_cost');

        $grossProfit = $revenue - abs($cogs);

        return ($grossProfit / $revenue) * 100;
    }

    /**
     * Calculate net profit margin
     */
    public function getNetProfitMargin(string $startDate, string $endDate): float
    {
        $revenue = $this->getTotalRevenue($startDate, $endDate);

        if ($revenue <= 0) {
            return 0;
        }

        $expenses = $this->getTotalExpenses($startDate, $endDate);
        $netProfit = $revenue - $expenses;

        return ($netProfit / $revenue) * 100;
    }

    /**
     * Calculate inventory turnover ratio
     */
    public function getInventoryTurnoverRatio(string $startDate, string $endDate): float
    {
        // Calculate COGS
        $cogs = DB::table('stock_ledgers')
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->where('movement_type', 'sale')
            ->sum('total_cost');

        // Calculate average inventory
        $startInventory = DB::table('stock_ledgers')
            ->where('transaction_date', '<', $startDate)
            ->sum(DB::raw('quantity * unit_cost'));

        $endInventory = DB::table('stock_ledgers')
            ->where('transaction_date', '<=', $endDate)
            ->sum(DB::raw('quantity * unit_cost'));

        $avgInventory = ($startInventory + $endInventory) / 2;

        if ($avgInventory <= 0) {
            return 0;
        }

        return abs($cogs) / $avgInventory;
    }

    /**
     * Calculate Days Sales Outstanding (DSO)
     */
    public function getDaysSalesOutstanding(string $startDate, string $endDate): float
    {
        $revenue = $this->getTotalRevenue($startDate, $endDate);

        if ($revenue <= 0) {
            return 0;
        }

        // Calculate accounts receivable
        $accountsReceivable = DB::table('invoices')
            ->whereIn('status', ['pending', 'partially_paid'])
            ->sum(DB::raw('total_amount - paid_amount'));

        $days = (new \DateTime($startDate))->diff(new \DateTime($endDate))->days;

        return ($accountsReceivable / $revenue) * $days;
    }

    /**
     * Calculate order fulfillment rate
     */
    public function getOrderFulfillmentRate(string $startDate, string $endDate): float
    {
        $totalOrders = DB::table('sales_orders')
            ->whereBetween('order_date', [$startDate, $endDate])
            ->count();

        if ($totalOrders <= 0) {
            return 0;
        }

        $fulfilledOrders = DB::table('sales_orders')
            ->whereBetween('order_date', [$startDate, $endDate])
            ->where('status', 'fulfilled')
            ->count();

        return ($fulfilledOrders / $totalOrders) * 100;
    }

    /**
     * Calculate production efficiency
     */
    public function getProductionEfficiency(string $startDate, string $endDate): float
    {
        $totalOrders = DB::table('production_orders')
            ->whereBetween('start_date', [$startDate, $endDate])
            ->count();

        if ($totalOrders <= 0) {
            return 0;
        }

        $completedOrders = DB::table('production_orders')
            ->whereBetween('start_date', [$startDate, $endDate])
            ->where('status', 'completed')
            ->count();

        return ($completedOrders / $totalOrders) * 100;
    }

    /**
     * Calculate customer acquisition cost
     */
    public function getCustomerAcquisitionCost(string $startDate, string $endDate): float
    {
        $marketingExpenses = DB::table('journal_entries as je')
            ->join('journal_entry_lines as jel', 'je.id', '=', 'jel.journal_entry_id')
            ->join('accounts as a', 'jel.account_id', '=', 'a.id')
            ->whereBetween('je.entry_date', [$startDate, $endDate])
            ->where('a.name', 'like', '%marketing%')
            ->where('je.status', 'posted')
            ->sum('jel.debit');

        $newCustomers = DB::table('customers')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        if ($newCustomers <= 0) {
            return 0;
        }

        return $marketingExpenses / $newCustomers;
    }

    /**
     * Calculate average order value
     */
    public function getAverageOrderValue(string $startDate, string $endDate): float
    {
        $totalRevenue = $this->getTotalRevenue($startDate, $endDate);

        $totalOrders = DB::table('sales_orders')
            ->whereBetween('order_date', [$startDate, $endDate])
            ->count();

        if ($totalOrders <= 0) {
            return 0;
        }

        return $totalRevenue / $totalOrders;
    }

    /**
     * Get all KPIs for a period
     */
    public function getAllKPIs(string $startDate, string $endDate): array
    {
        return [
            'total_revenue' => $this->getTotalRevenue($startDate, $endDate),
            'total_expenses' => $this->getTotalExpenses($startDate, $endDate),
            'gross_profit_margin' => $this->getGrossProfitMargin($startDate, $endDate),
            'net_profit_margin' => $this->getNetProfitMargin($startDate, $endDate),
            'inventory_turnover_ratio' => $this->getInventoryTurnoverRatio($startDate, $endDate),
            'days_sales_outstanding' => $this->getDaysSalesOutstanding($startDate, $endDate),
            'order_fulfillment_rate' => $this->getOrderFulfillmentRate($startDate, $endDate),
            'production_efficiency' => $this->getProductionEfficiency($startDate, $endDate),
            'customer_acquisition_cost' => $this->getCustomerAcquisitionCost($startDate, $endDate),
            'average_order_value' => $this->getAverageOrderValue($startDate, $endDate),
        ];
    }
}
