<?php

namespace App\Modules\Analytics\Services;

use App\Modules\Billing\Services\InvoiceService;
use App\Modules\Billing\Services\PaymentService;
use App\Modules\CRM\Services\LeadService;
use App\Modules\CRM\Services\OpportunityService;
use App\Modules\Customer\Services\CustomerService;
use App\Modules\Inventory\Services\ProductService;
use App\Modules\Inventory\Services\StockService;
use App\Modules\POS\Services\POSService;
use Illuminate\Support\Facades\Log;

class AnalyticsService
{
    protected InvoiceService $invoiceService;

    protected PaymentService $paymentService;

    protected POSService $posService;

    protected ProductService $productService;

    protected StockService $stockService;

    protected LeadService $leadService;

    protected OpportunityService $opportunityService;

    protected CustomerService $customerService;

    /**
     * AnalyticsService constructor
     */
    public function __construct(
        InvoiceService $invoiceService,
        PaymentService $paymentService,
        POSService $posService,
        ProductService $productService,
        StockService $stockService,
        LeadService $leadService,
        OpportunityService $opportunityService,
        CustomerService $customerService
    ) {
        $this->invoiceService = $invoiceService;
        $this->paymentService = $paymentService;
        $this->posService = $posService;
        $this->productService = $productService;
        $this->stockService = $stockService;
        $this->leadService = $leadService;
        $this->opportunityService = $opportunityService;
        $this->customerService = $customerService;
    }

    /**
     * Get dashboard statistics
     */
    public function getDashboardStats(): array
    {
        try {
            $invoiceSummary = $this->invoiceService->getSummary();
            $lowStockProducts = $this->productService->getLowStock()->count();
            $activeCustomers = $this->customerService->getActive()->count();
            $todaySales = $this->posService->getDailySales();

            return [
                'invoices' => [
                    'total' => $invoiceSummary['total_invoices'],
                    'pending' => $invoiceSummary['pending_invoices'],
                    'overdue' => $invoiceSummary['overdue_invoices'],
                    'outstanding_amount' => $invoiceSummary['total_outstanding'],
                ],
                'sales' => [
                    'today_total' => $todaySales['total_sales'],
                    'today_transactions' => $todaySales['total_transactions'],
                    'average_transaction' => $todaySales['average_transaction_value'],
                ],
                'inventory' => [
                    'low_stock_items' => $lowStockProducts,
                ],
                'customers' => [
                    'active_count' => $activeCustomers,
                ],
                'generated_at' => now()->toDateTimeString(),
            ];
        } catch (\Exception $e) {
            Log::error('Error fetching dashboard stats: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Get revenue report
     */
    public function getRevenueReport(string $startDate, string $endDate, ?int $branchId = null): array
    {
        try {
            $payments = $this->paymentService->getByDateRange($startDate, $endDate);

            $totalRevenue = $payments->where('status', 'completed')->sum('amount');
            $paymentsByMethod = $payments->groupBy('payment_method')->map(function ($group) {
                return [
                    'count' => $group->count(),
                    'total' => $group->sum('amount'),
                ];
            });

            $invoices = $this->invoiceService->getByDateRange($startDate, $endDate);
            $totalInvoiced = $invoices->sum('total_amount');
            $paidInvoices = $invoices->where('status', 'paid')->count();
            $pendingInvoices = $invoices->where('status', 'pending')->count();

            $posTransactions = $this->posService->getByDateRange($startDate, $endDate);
            if ($branchId) {
                $posTransactions = $posTransactions->where('branch_id', $branchId);
            }
            $posSales = $posTransactions->where('status', 'completed')->sum('total_amount');

            return [
                'period' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'branch_id' => $branchId,
                ],
                'revenue' => [
                    'total_revenue' => $totalRevenue,
                    'pos_sales' => $posSales,
                    'payments_by_method' => $paymentsByMethod,
                ],
                'invoices' => [
                    'total_invoiced' => $totalInvoiced,
                    'paid_invoices' => $paidInvoices,
                    'pending_invoices' => $pendingInvoices,
                ],
                'generated_at' => now()->toDateTimeString(),
            ];
        } catch (\Exception $e) {
            Log::error('Error generating revenue report: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Get inventory report
     */
    public function getInventoryReport(): array
    {
        try {
            $allProducts = $this->productService->getAll();
            $activeProducts = $this->productService->getActive();
            $lowStockProducts = $this->productService->getLowStock();

            $totalProducts = $allProducts->count();
            $activeCount = $activeProducts->count();
            $lowStockCount = $lowStockProducts->count();

            $stockValue = $allProducts->sum(function ($product) {
                return ($product->current_stock ?? 0) * ($product->unit_price ?? 0);
            });

            $lowStockItems = $lowStockProducts->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'current_stock' => $product->current_stock ?? 0,
                    'min_stock_level' => $product->min_stock_level ?? 0,
                    'unit_price' => $product->unit_price ?? 0,
                ];
            })->take(20);

            return [
                'summary' => [
                    'total_products' => $totalProducts,
                    'active_products' => $activeCount,
                    'low_stock_products' => $lowStockCount,
                    'total_stock_value' => $stockValue,
                ],
                'low_stock_items' => $lowStockItems,
                'generated_at' => now()->toDateTimeString(),
            ];
        } catch (\Exception $e) {
            Log::error('Error generating inventory report: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Get sales report
     */
    public function getSalesReport(string $startDate, string $endDate): array
    {
        try {
            $posTransactions = $this->posService->getByDateRange($startDate, $endDate);

            $completedTransactions = $posTransactions->where('status', 'completed');
            $totalSales = $completedTransactions->sum('total_amount');
            $transactionCount = $completedTransactions->count();
            $averageTransaction = $transactionCount > 0 ? $totalSales / $transactionCount : 0;

            $dailySales = $completedTransactions->groupBy(function ($transaction) {
                return $transaction->created_at->format('Y-m-d');
            })->map(function ($group) {
                return [
                    'date' => $group->first()->created_at->format('Y-m-d'),
                    'transactions' => $group->count(),
                    'total' => $group->sum('total_amount'),
                ];
            })->values();

            return [
                'period' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                ],
                'summary' => [
                    'total_sales' => $totalSales,
                    'transaction_count' => $transactionCount,
                    'average_transaction' => round($averageTransaction, 2),
                ],
                'daily_breakdown' => $dailySales,
                'generated_at' => now()->toDateTimeString(),
            ];
        } catch (\Exception $e) {
            Log::error('Error generating sales report: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Get CRM metrics
     */
    public function getCRMMetrics(): array
    {
        try {
            $qualifiedLeads = $this->leadService->getQualified()->count();
            $winRate = $this->opportunityService->calculateWinRate();

            return [
                'leads' => [
                    'qualified_leads' => $qualifiedLeads,
                ],
                'opportunities' => [
                    'total' => $winRate['total_opportunities'],
                    'won' => $winRate['won'],
                    'lost' => $winRate['lost'],
                    'active' => $winRate['active'],
                    'win_rate' => $winRate['win_rate'],
                ],
                'generated_at' => now()->toDateTimeString(),
            ];
        } catch (\Exception $e) {
            Log::error('Error fetching CRM metrics: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Get customer insights
     */
    public function getCustomerInsights(): array
    {
        try {
            $activeCustomers = $this->customerService->getActive();
            $customersWithBalance = $this->customerService->getWithOutstandingBalance();

            $totalActive = $activeCustomers->count();
            $withBalance = $customersWithBalance->count();
            $totalOutstanding = $customersWithBalance->sum('balance');

            return [
                'total_active_customers' => $totalActive,
                'customers_with_outstanding' => $withBalance,
                'total_outstanding_balance' => $totalOutstanding,
                'average_outstanding' => $withBalance > 0 ? $totalOutstanding / $withBalance : 0,
                'generated_at' => now()->toDateTimeString(),
            ];
        } catch (\Exception $e) {
            Log::error('Error fetching customer insights: '.$e->getMessage());
            throw $e;
        }
    }
}
