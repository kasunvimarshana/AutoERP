<?php

declare(strict_types=1);

namespace Modules\Reporting\Repositories;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Analytics Repository
 *
 * Handles data access for analytics and metrics.
 * Provides secure, optimized queries for business intelligence.
 */
class AnalyticsRepository
{
    /**
     * Get sales invoice data for metrics.
     */
    public function getSalesInvoiceData(
        string $tenantId,
        string $startDate,
        string $endDate,
        ?int $organizationId = null
    ): Collection {
        $query = DB::table('sales_invoices')
            ->where('tenant_id', $tenantId)
            ->whereBetween('invoice_date', [$startDate, $endDate])
            ->select([
                'id',
                'total_amount',
                'paid_amount',
                'outstanding_amount',
                'invoice_date',
                'status',
            ]);

        if ($organizationId) {
            $query->where('organization_id', $organizationId);
        }

        return $query->get();
    }

    /**
     * Get inventory items with products for metrics.
     */
    public function getInventoryItems(string $tenantId, ?int $organizationId = null): Collection
    {
        $query = DB::table('inventory_items')
            ->join('products', 'inventory_items.product_id', '=', 'products.id')
            ->where('inventory_items.tenant_id', $tenantId)
            ->select([
                'inventory_items.id',
                'inventory_items.quantity',
                'inventory_items.reorder_level',
                'products.name as product_name',
                'products.cost_price',
            ]);

        if ($organizationId) {
            $query->where('inventory_items.organization_id', $organizationId);
        }

        return $query->get();
    }

    /**
     * Get CRM leads data for metrics.
     */
    public function getCrmLeadsData(
        string $tenantId,
        string $startDate,
        string $endDate,
        ?int $organizationId = null
    ): Collection {
        $query = DB::table('crm_leads')
            ->where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->select(['id', 'status', 'created_at']);

        if ($organizationId) {
            $query->where('organization_id', $organizationId);
        }

        return $query->get();
    }

    /**
     * Get CRM opportunities data for metrics.
     */
    public function getCrmOpportunitiesData(
        string $tenantId,
        string $startDate,
        string $endDate,
        ?int $organizationId = null
    ): Collection {
        $query = DB::table('crm_opportunities')
            ->where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->select(['id', 'stage', 'estimated_value', 'created_at']);

        if ($organizationId) {
            $query->where('organization_id', $organizationId);
        }

        return $query->get();
    }

    /**
     * Get paid invoices for revenue calculation.
     */
    public function getPaidInvoices(
        string $tenantId,
        string $startDate,
        string $endDate,
        ?int $organizationId = null
    ): Collection {
        $query = DB::table('sales_invoices')
            ->where('tenant_id', $tenantId)
            ->whereBetween('invoice_date', [$startDate, $endDate])
            ->where('status', 'paid')
            ->select(['id', 'total_amount', 'invoice_date']);

        if ($organizationId) {
            $query->where('organization_id', $organizationId);
        }

        return $query->get();
    }

    /**
     * Get paid purchase invoices for expense calculation.
     */
    public function getPaidPurchaseInvoices(
        string $tenantId,
        string $startDate,
        string $endDate,
        ?int $organizationId = null
    ): Collection {
        $query = DB::table('purchase_invoices')
            ->where('tenant_id', $tenantId)
            ->whereBetween('invoice_date', [$startDate, $endDate])
            ->where('status', 'paid')
            ->select(['id', 'total_amount', 'invoice_date']);

        if ($organizationId) {
            $query->where('organization_id', $organizationId);
        }

        return $query->get();
    }

    /**
     * Get top selling products.
     */
    public function getTopSellingProducts(
        string $tenantId,
        string $startDate,
        string $endDate,
        int $limit = 10
    ): Collection {
        return DB::table('sales_order_items')
            ->join('sales_orders', 'sales_order_items.order_id', '=', 'sales_orders.id')
            ->join('products', 'sales_order_items.product_id', '=', 'products.id')
            ->where('sales_orders.tenant_id', $tenantId)
            ->whereBetween('sales_orders.order_date', [$startDate, $endDate])
            ->select([
                'products.id',
                'products.name',
                DB::raw('SUM(sales_order_items.quantity) as total_quantity'),
                DB::raw('SUM(sales_order_items.total_price) as total_revenue'),
            ])
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('total_revenue')
            ->limit($limit)
            ->get();
    }

    /**
     * Get customer analytics data.
     */
    public function getCustomerAnalytics(
        string $tenantId,
        string $startDate,
        string $endDate
    ): Collection {
        return DB::table('sales_invoices')
            ->join('customers', 'sales_invoices.customer_id', '=', 'customers.id')
            ->where('sales_invoices.tenant_id', $tenantId)
            ->whereBetween('sales_invoices.invoice_date', [$startDate, $endDate])
            ->select([
                'customers.id',
                'customers.name',
                DB::raw('COUNT(sales_invoices.id) as invoice_count'),
                DB::raw('SUM(sales_invoices.total_amount) as total_spent'),
            ])
            ->groupBy('customers.id', 'customers.name')
            ->orderByDesc('total_spent')
            ->limit(20)
            ->get();
    }

    /**
     * Get sales trend data safely using parameterized queries.
     */
    public function getSalesTrend(
        string $tenantId,
        string $startDate,
        string $endDate,
        string $interval = 'day'
    ): Collection {
        // Validate interval to prevent any potential injection
        $allowedIntervals = ['hour', 'day', 'week', 'month', 'year'];
        if (! in_array($interval, $allowedIntervals, true)) {
            throw new \InvalidArgumentException('Invalid interval. Must be one of: ' . implode(', ', $allowedIntervals));
        }
        
        // Use match for safe format selection - no string interpolation
        $dateFormatExpression = match ($interval) {
            'hour' => "DATE_FORMAT(invoice_date, '%Y-%m-%d %H:00:00')",
            'day' => "DATE_FORMAT(invoice_date, '%Y-%m-%d')",
            'week' => "DATE_FORMAT(invoice_date, '%Y-%U')",
            'month' => "DATE_FORMAT(invoice_date, '%Y-%m')",
            'year' => "DATE_FORMAT(invoice_date, '%Y')",
            default => "DATE_FORMAT(invoice_date, '%Y-%m-%d')",
        };

        return DB::table('sales_invoices')
            ->where('tenant_id', $tenantId)
            ->whereBetween('invoice_date', [$startDate, $endDate])
            ->select([
                DB::raw("{$dateFormatExpression} as period"),
                DB::raw('SUM(total_amount) as value'),
            ])
            ->groupBy('period')
            ->orderBy('period')
            ->get();
    }
}
