<?php

declare(strict_types=1);

namespace Modules\Reporting\Services;

use Modules\Reporting\Repositories\AnalyticsRepository;

/**
 * AnalyticsService
 *
 * Pre-built analytics and metrics for common business reports
 */
class AnalyticsService
{
    public function __construct(
        private AnalyticsRepository $analyticsRepository
    ) {}
    /**
     * Get sales metrics for a date range
     */
    public function salesMetrics(string $startDate, string $endDate, ?int $organizationId = null): array
    {
        $tenantId = auth()->user()->tenant_id;
        
        $invoices = $this->analyticsRepository->getSalesInvoiceData(
            $tenantId,
            $startDate,
            $endDate,
            $organizationId
        );

        // Calculate metrics using BCMath
        $totalRevenue = '0';
        $totalPaid = '0';
        $totalOutstanding = '0';

        foreach ($invoices as $invoice) {
            $totalRevenue = bcadd($totalRevenue, (string) $invoice->total_amount, 2);
            $totalPaid = bcadd($totalPaid, (string) $invoice->paid_amount, 2);
            $totalOutstanding = bcadd($totalOutstanding, (string) $invoice->outstanding_amount, 2);
        }

        return [
            'period' => [
                'start' => $startDate,
                'end' => $endDate,
            ],
            'metrics' => [
                'total_revenue' => $totalRevenue,
                'total_paid' => $totalPaid,
                'total_outstanding' => $totalOutstanding,
                'invoice_count' => $invoices->count(),
                'average_invoice_value' => $invoices->count() > 0
                    ? bcdiv($totalRevenue, (string) $invoices->count(), 2)
                    : '0',
            ],
        ];
    }

    /**
     * Get inventory metrics
     */
    public function inventoryMetrics(?int $organizationId = null): array
    {
        $tenantId = auth()->user()->tenant_id;
        
        $items = $this->analyticsRepository->getInventoryItems($tenantId, $organizationId);

        $totalQuantity = '0';
        $totalValue = '0';
        $lowStockCount = 0;
        $outOfStockCount = 0;

        foreach ($items as $item) {
            $totalQuantity = bcadd($totalQuantity, (string) $item->quantity, 2);
            $itemValue = bcmul((string) $item->quantity, (string) $item->cost_price, 2);
            $totalValue = bcadd($totalValue, $itemValue, 2);

            if ($item->quantity <= ($item->reorder_level ?? 0)) {
                $lowStockCount++;
            }
            if ($item->quantity == 0) {
                $outOfStockCount++;
            }
        }

        return [
            'metrics' => [
                'total_items' => $items->count(),
                'total_quantity' => $totalQuantity,
                'total_value' => $totalValue,
                'low_stock_items' => $lowStockCount,
                'out_of_stock_items' => $outOfStockCount,
            ],
        ];
    }

    /**
     * Get CRM metrics
     */
    public function crmMetrics(string $startDate, string $endDate, ?int $organizationId = null): array
    {
        $tenantId = auth()->user()->tenant_id;
        
        // Leads metrics
        $leads = $this->analyticsRepository->getCrmLeadsData(
            $tenantId,
            $startDate,
            $endDate,
            $organizationId
        );
        $convertedLeads = $leads->where('status', 'converted')->count();
        $conversionRate = $leads->count() > 0
            ? bcdiv((string) $convertedLeads, (string) $leads->count(), 4)
            : '0';

        // Opportunities metrics
        $opportunities = $this->analyticsRepository->getCrmOpportunitiesData(
            $tenantId,
            $startDate,
            $endDate,
            $organizationId
        );

        $totalOpportunityValue = '0';
        $wonOpportunities = 0;
        $wonValue = '0';

        foreach ($opportunities as $opp) {
            $totalOpportunityValue = bcadd($totalOpportunityValue, (string) $opp->estimated_value, 2);
            if ($opp->stage === 'won') {
                $wonOpportunities++;
                $wonValue = bcadd($wonValue, (string) $opp->estimated_value, 2);
            }
        }

        $winRate = $opportunities->count() > 0
            ? bcdiv((string) $wonOpportunities, (string) $opportunities->count(), 4)
            : '0';

        return [
            'period' => [
                'start' => $startDate,
                'end' => $endDate,
            ],
            'leads' => [
                'total' => $leads->count(),
                'converted' => $convertedLeads,
                'conversion_rate' => bcmul($conversionRate, '100', 2).'%',
            ],
            'opportunities' => [
                'total' => $opportunities->count(),
                'won' => $wonOpportunities,
                'win_rate' => bcmul($winRate, '100', 2).'%',
                'total_value' => $totalOpportunityValue,
                'won_value' => $wonValue,
            ],
        ];
    }

    /**
     * Get financial metrics
     */
    public function financialMetrics(string $startDate, string $endDate, ?int $organizationId = null): array
    {
        $tenantId = auth()->user()->tenant_id;
        
        // Revenue
        $revenue = $this->analyticsRepository->getPaidInvoices(
            $tenantId,
            $startDate,
            $endDate,
            $organizationId
        );
        $totalRevenue = '0';
        foreach ($revenue as $inv) {
            $totalRevenue = bcadd($totalRevenue, (string) $inv->total_amount, 2);
        }

        // Expenses (from purchase invoices)
        $expenses = $this->analyticsRepository->getPaidPurchaseInvoices(
            $tenantId,
            $startDate,
            $endDate,
            $organizationId
        );
        $totalExpenses = '0';
        foreach ($expenses as $exp) {
            $totalExpenses = bcadd($totalExpenses, (string) $exp->total_amount, 2);
        }

        $profit = bcsub($totalRevenue, $totalExpenses, 2);
        $profitMargin = bccomp($totalRevenue, '0', 2) > 0
            ? bcdiv($profit, $totalRevenue, 4)
            : '0';

        return [
            'period' => [
                'start' => $startDate,
                'end' => $endDate,
            ],
            'metrics' => [
                'total_revenue' => $totalRevenue,
                'total_expenses' => $totalExpenses,
                'profit' => $profit,
                'profit_margin' => bcmul($profitMargin, '100', 2).'%',
            ],
        ];
    }

    /**
     * Get top selling products
     */
    public function topSellingProducts(string $startDate, string $endDate, int $limit = 10): array
    {
        $tenantId = auth()->user()->tenant_id;
        
        $products = $this->analyticsRepository->getTopSellingProducts(
            $tenantId,
            $startDate,
            $endDate,
            $limit
        );

        return $products->toArray();
    }

    /**
     * Get customer analytics
     */
    public function customerAnalytics(string $startDate, string $endDate): array
    {
        $tenantId = auth()->user()->tenant_id;
        
        $customers = $this->analyticsRepository->getCustomerAnalytics(
            $tenantId,
            $startDate,
            $endDate
        );

        return $customers->toArray();
    }

    /**
     * Get trend data for a metric over time
     */
    public function getTrend(string $metric, string $startDate, string $endDate, string $interval = 'day'): array
    {
        $tenantId = auth()->user()->tenant_id;
        
        // Use repository for secure query execution
        $data = $this->analyticsRepository->getSalesTrend(
            $tenantId,
            $startDate,
            $endDate,
            $interval
        );

        return $data->toArray();
    }
}
