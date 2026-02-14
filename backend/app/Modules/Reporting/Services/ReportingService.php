<?php

namespace App\Modules\Reporting\Services;

use App\Core\BaseService;
use App\Modules\Billing\Models\Invoice;
use App\Modules\Billing\Models\Payment;
use App\Modules\CRM\Models\Customer;
use App\Modules\Inventory\Models\Product;
use App\Modules\Inventory\Models\SKU;
use App\Modules\POS\Models\POSTransaction;
use App\Modules\POS\Models\POSTransactionItem;
use App\Modules\Reporting\DTOs\ReportFilterDTO;
use Illuminate\Support\Facades\DB;

/**
 * Reporting Service
 * 
 * Service for generating various analytics and KPI reports
 * 
 * @package App\Modules\Reporting\Services
 */
class ReportingService extends BaseService
{
    /**
     * Get sales report for a date range
     *
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function getSalesReport(string $startDate, string $endDate): array
    {
        $posTransactions = POSTransaction::whereBetween('transaction_date', [$startDate, $endDate])
            ->where('status', 'completed')
            ->get();

        $invoices = Invoice::whereBetween('invoice_date', [$startDate, $endDate])
            ->where('status', 'paid')
            ->get();

        $posSales = [
            'total_transactions' => $posTransactions->count(),
            'total_amount' => $posTransactions->sum('total_amount'),
            'total_tax' => $posTransactions->sum('tax_amount'),
            'average_transaction' => $posTransactions->count() > 0 
                ? $posTransactions->sum('total_amount') / $posTransactions->count() 
                : 0,
        ];

        $invoiceSales = [
            'total_invoices' => $invoices->count(),
            'total_amount' => $invoices->sum('total_amount'),
            'total_tax' => $invoices->sum('tax_amount'),
            'average_invoice' => $invoices->count() > 0 
                ? $invoices->sum('total_amount') / $invoices->count() 
                : 0,
        ];

        $totalSales = $posSales['total_amount'] + $invoiceSales['total_amount'];
        $totalTax = $posSales['total_tax'] + $invoiceSales['total_tax'];

        return [
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'pos_sales' => $posSales,
            'invoice_sales' => $invoiceSales,
            'summary' => [
                'total_sales' => $totalSales,
                'total_tax' => $totalTax,
                'total_transactions' => $posSales['total_transactions'] + $invoiceSales['total_invoices'],
                'average_sale' => ($posSales['total_transactions'] + $invoiceSales['total_invoices']) > 0
                    ? $totalSales / ($posSales['total_transactions'] + $invoiceSales['total_invoices'])
                    : 0,
            ],
            'daily_breakdown' => $this->getDailyBreakdown($startDate, $endDate),
        ];
    }

    /**
     * Get inventory report
     *
     * @return array
     */
    public function getInventoryReport(): array
    {
        $totalProducts = Product::count();
        $totalSKUs = SKU::count();

        $lowStockSKUs = SKU::whereColumn('quantity_on_hand', '<=', 'reorder_point')
            ->where('reorder_point', '>', 0)
            ->count();

        $outOfStockSKUs = SKU::where('quantity_on_hand', '<=', 0)
            ->count();

        $totalInventoryValue = SKU::selectRaw('SUM(quantity_on_hand * unit_cost) as total_value')
            ->value('total_value') ?? 0;

        $productsByStatus = Product::select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status')
            ->toArray();

        $skusByWarehouse = SKU::join('products', 'skus.product_id', '=', 'products.id')
            ->select('products.warehouse_location', DB::raw('COUNT(skus.id) as count'))
            ->groupBy('products.warehouse_location')
            ->get()
            ->pluck('count', 'warehouse_location')
            ->toArray();

        return [
            'summary' => [
                'total_products' => $totalProducts,
                'total_skus' => $totalSKUs,
                'low_stock_items' => $lowStockSKUs,
                'out_of_stock_items' => $outOfStockSKUs,
                'total_inventory_value' => round($totalInventoryValue, 2),
            ],
            'products_by_status' => $productsByStatus,
            'skus_by_warehouse' => $skusByWarehouse,
            'stock_alerts' => [
                'low_stock_percentage' => $totalSKUs > 0 
                    ? round(($lowStockSKUs / $totalSKUs) * 100, 2) 
                    : 0,
                'out_of_stock_percentage' => $totalSKUs > 0 
                    ? round(($outOfStockSKUs / $totalSKUs) * 100, 2) 
                    : 0,
            ],
        ];
    }

    /**
     * Get customer report
     *
     * @return array
     */
    public function getCustomerReport(): array
    {
        $totalCustomers = Customer::count();
        $activeCustomers = Customer::where('status', 'active')->count();
        $inactiveCustomers = Customer::where('status', 'inactive')->count();

        $customersWithPurchases = Customer::whereHas('invoices')->count();

        $customersByStatus = Customer::select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status')
            ->toArray();

        $averageLifetimeValue = Invoice::where('status', 'paid')
            ->select('customer_id', DB::raw('SUM(total_amount) as lifetime_value'))
            ->groupBy('customer_id')
            ->avg('lifetime_value') ?? 0;

        return [
            'summary' => [
                'total_customers' => $totalCustomers,
                'active_customers' => $activeCustomers,
                'inactive_customers' => $inactiveCustomers,
                'customers_with_purchases' => $customersWithPurchases,
                'average_lifetime_value' => round($averageLifetimeValue, 2),
            ],
            'customers_by_status' => $customersByStatus,
            'engagement' => [
                'purchase_rate' => $totalCustomers > 0 
                    ? round(($customersWithPurchases / $totalCustomers) * 100, 2) 
                    : 0,
                'active_rate' => $totalCustomers > 0 
                    ? round(($activeCustomers / $totalCustomers) * 100, 2) 
                    : 0,
            ],
        ];
    }

    /**
     * Get revenue KPIs for a period
     *
     * @param string $period (daily, weekly, monthly, yearly)
     * @return array
     */
    public function getRevenueKPIs(string $period = 'monthly'): array
    {
        $dates = $this->getDateRangeForPeriod($period);
        $startDate = $dates['start'];
        $endDate = $dates['end'];
        $previousStartDate = $dates['previous_start'];
        $previousEndDate = $dates['previous_end'];

        $currentRevenue = $this->calculateRevenue($startDate, $endDate);
        $previousRevenue = $this->calculateRevenue($previousStartDate, $previousEndDate);

        $growth = $previousRevenue > 0 
            ? (($currentRevenue - $previousRevenue) / $previousRevenue) * 100 
            : 0;

        $currentPayments = Payment::whereBetween('payment_date', [$startDate, $endDate])
            ->where('status', 'completed')
            ->sum('amount');

        $outstandingInvoices = Invoice::where('status', '!=', 'paid')
            ->sum('total_amount');

        return [
            'period' => $period,
            'date_range' => [
                'start' => $startDate,
                'end' => $endDate,
            ],
            'current_period' => [
                'revenue' => round($currentRevenue, 2),
                'payments_received' => round($currentPayments, 2),
            ],
            'previous_period' => [
                'revenue' => round($previousRevenue, 2),
            ],
            'kpis' => [
                'revenue_growth' => round($growth, 2),
                'outstanding_invoices' => round($outstandingInvoices, 2),
                'average_daily_revenue' => round($currentRevenue / max(1, $this->getDaysInPeriod($period)), 2),
            ],
        ];
    }

    /**
     * Get top products by sales
     *
     * @param int $limit
     * @return array
     */
    public function getTopProducts(int $limit = 10): array
    {
        $topPOSProducts = POSTransactionItem::join('skus', 'pos_transaction_items.sku_id', '=', 'skus.id')
            ->join('products', 'skus.product_id', '=', 'products.id')
            ->select(
                'products.id',
                'products.name',
                'products.sku as product_sku',
                DB::raw('SUM(pos_transaction_items.quantity) as total_quantity'),
                DB::raw('SUM(pos_transaction_items.subtotal) as total_revenue')
            )
            ->groupBy('products.id', 'products.name', 'products.sku')
            ->orderByDesc('total_revenue')
            ->limit($limit)
            ->get()
            ->map(function ($item) {
                return [
                    'product_id' => $item->id,
                    'product_name' => $item->name,
                    'product_sku' => $item->product_sku,
                    'total_quantity' => (int) $item->total_quantity,
                    'total_revenue' => round($item->total_revenue, 2),
                ];
            });

        return [
            'limit' => $limit,
            'top_products' => $topPOSProducts->toArray(),
        ];
    }

    /**
     * Get top customers by revenue
     *
     * @param int $limit
     * @return array
     */
    public function getTopCustomers(int $limit = 10): array
    {
        $topCustomers = Customer::select(
                'customers.id',
                'customers.name',
                'customers.email',
                DB::raw('COUNT(DISTINCT invoices.id) as total_invoices'),
                DB::raw('SUM(invoices.total_amount) as total_revenue')
            )
            ->join('invoices', 'customers.id', '=', 'invoices.customer_id')
            ->where('invoices.status', 'paid')
            ->groupBy('customers.id', 'customers.name', 'customers.email')
            ->orderByDesc('total_revenue')
            ->limit($limit)
            ->get()
            ->map(function ($customer) {
                return [
                    'customer_id' => $customer->id,
                    'customer_name' => $customer->name,
                    'customer_email' => $customer->email,
                    'total_invoices' => (int) $customer->total_invoices,
                    'total_revenue' => round($customer->total_revenue, 2),
                    'average_invoice_value' => $customer->total_invoices > 0 
                        ? round($customer->total_revenue / $customer->total_invoices, 2) 
                        : 0,
                ];
            });

        return [
            'limit' => $limit,
            'top_customers' => $topCustomers->toArray(),
        ];
    }

    /**
     * Get daily breakdown for a date range
     *
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    private function getDailyBreakdown(string $startDate, string $endDate): array
    {
        $posDaily = POSTransaction::selectRaw('DATE(transaction_date) as date, SUM(total_amount) as total')
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->where('status', 'completed')
            ->groupBy('date')
            ->get()
            ->pluck('total', 'date')
            ->toArray();

        $invoiceDaily = Invoice::selectRaw('DATE(invoice_date) as date, SUM(total_amount) as total')
            ->whereBetween('invoice_date', [$startDate, $endDate])
            ->where('status', 'paid')
            ->groupBy('date')
            ->get()
            ->pluck('total', 'date')
            ->toArray();

        $allDates = array_unique(array_merge(array_keys($posDaily), array_keys($invoiceDaily)));
        sort($allDates);

        return array_map(function ($date) use ($posDaily, $invoiceDaily) {
            return [
                'date' => $date,
                'pos_sales' => round($posDaily[$date] ?? 0, 2),
                'invoice_sales' => round($invoiceDaily[$date] ?? 0, 2),
                'total_sales' => round(($posDaily[$date] ?? 0) + ($invoiceDaily[$date] ?? 0), 2),
            ];
        }, $allDates);
    }

    /**
     * Calculate revenue for a date range
     *
     * @param string $startDate
     * @param string $endDate
     * @return float
     */
    private function calculateRevenue(string $startDate, string $endDate): float
    {
        $posRevenue = POSTransaction::whereBetween('transaction_date', [$startDate, $endDate])
            ->where('status', 'completed')
            ->sum('total_amount');

        $invoiceRevenue = Invoice::whereBetween('invoice_date', [$startDate, $endDate])
            ->where('status', 'paid')
            ->sum('total_amount');

        return $posRevenue + $invoiceRevenue;
    }

    /**
     * Get date range for a period
     *
     * @param string $period
     * @return array
     */
    private function getDateRangeForPeriod(string $period): array
    {
        $now = now();

        switch ($period) {
            case 'daily':
                $start = $now->copy()->startOfDay();
                $end = $now->copy()->endOfDay();
                $previousStart = $now->copy()->subDay()->startOfDay();
                $previousEnd = $now->copy()->subDay()->endOfDay();
                break;
            case 'weekly':
                $start = $now->copy()->startOfWeek();
                $end = $now->copy()->endOfWeek();
                $previousStart = $now->copy()->subWeek()->startOfWeek();
                $previousEnd = $now->copy()->subWeek()->endOfWeek();
                break;
            case 'yearly':
                $start = $now->copy()->startOfYear();
                $end = $now->copy()->endOfYear();
                $previousStart = $now->copy()->subYear()->startOfYear();
                $previousEnd = $now->copy()->subYear()->endOfYear();
                break;
            case 'monthly':
            default:
                $start = $now->copy()->startOfMonth();
                $end = $now->copy()->endOfMonth();
                $previousStart = $now->copy()->subMonth()->startOfMonth();
                $previousEnd = $now->copy()->subMonth()->endOfMonth();
                break;
        }

        return [
            'start' => $start->toDateString(),
            'end' => $end->toDateString(),
            'previous_start' => $previousStart->toDateString(),
            'previous_end' => $previousEnd->toDateString(),
        ];
    }

    /**
     * Get number of days in period
     *
     * @param string $period
     * @return int
     */
    private function getDaysInPeriod(string $period): int
    {
        switch ($period) {
            case 'daily':
                return 1;
            case 'weekly':
                return 7;
            case 'yearly':
                return 365;
            case 'monthly':
            default:
                return 30;
        }
    }
}
