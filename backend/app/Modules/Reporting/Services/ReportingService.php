<?php

declare(strict_types=1);

namespace App\Modules\Reporting\Services;

use App\Core\Services\BaseService;
use App\Modules\Inventory\Repositories\StockLedgerRepositoryInterface;
use App\Modules\Product\Repositories\ProductRepositoryInterface;
use App\Modules\Sales\Repositories\SalesOrderRepositoryInterface;
use Illuminate\Support\Facades\DB;

class ReportingService extends BaseService
{
    public function __construct(
        private readonly ProductRepositoryInterface $productRepository,
        private readonly StockLedgerRepositoryInterface $stockLedgerRepository,
        private readonly SalesOrderRepositoryInterface $salesOrderRepository
    ) {}

    public function generateInventoryReport(array $filters = []): array
    {
        $query = DB::table('products')
            ->select(
                'products.id',
                'products.name',
                'products.sku',
                DB::raw('COALESCE(SUM(stock_ledgers.quantity), 0) as current_stock'),
                'products.reorder_level',
                'products.reorder_quantity'
            )
            ->leftJoin('stock_ledgers', 'products.id', '=', 'stock_ledgers.product_id')
            ->where('products.tenant_id', auth()->user()->tenant_id)
            ->groupBy('products.id', 'products.name', 'products.sku', 'products.reorder_level', 'products.reorder_quantity');

        if (isset($filters['branch_id'])) {
            $query->where('stock_ledgers.branch_id', $filters['branch_id']);
        }

        return $query->get()->toArray();
    }

    public function generateSalesReport(string $startDate, string $endDate, array $filters = []): array
    {
        $query = DB::table('sales_orders')
            ->select(
                DB::raw('DATE(sales_orders.order_date) as date'),
                DB::raw('COUNT(*) as total_orders'),
                DB::raw('SUM(sales_orders.total_amount) as total_revenue'),
                DB::raw('AVG(sales_orders.total_amount) as average_order_value')
            )
            ->where('sales_orders.tenant_id', auth()->user()->tenant_id)
            ->whereBetween('sales_orders.order_date', [$startDate, $endDate])
            ->groupBy(DB::raw('DATE(sales_orders.order_date)'))
            ->orderBy('date');

        if (isset($filters['branch_id'])) {
            $query->where('sales_orders.branch_id', $filters['branch_id']);
        }

        return $query->get()->toArray();
    }

    public function generateFinancialReport(string $startDate, string $endDate): array
    {
        return [
            'revenue' => $this->calculateRevenue($startDate, $endDate),
            'expenses' => $this->calculateExpenses($startDate, $endDate),
            'profit' => $this->calculateProfit($startDate, $endDate),
        ];
    }

    private function calculateRevenue(string $startDate, string $endDate): float
    {
        return (float) DB::table('invoices')
            ->where('tenant_id', auth()->user()->tenant_id)
            ->whereBetween('invoice_date', [$startDate, $endDate])
            ->where('status', 'paid')
            ->sum('total_amount');
    }

    private function calculateExpenses(string $startDate, string $endDate): float
    {
        return (float) DB::table('purchase_orders')
            ->where('tenant_id', auth()->user()->tenant_id)
            ->whereBetween('order_date', [$startDate, $endDate])
            ->whereIn('status', ['completed', 'partially_received'])
            ->sum('total_amount');
    }

    private function calculateProfit(string $startDate, string $endDate): float
    {
        return $this->calculateRevenue($startDate, $endDate) - $this->calculateExpenses($startDate, $endDate);
    }
}
