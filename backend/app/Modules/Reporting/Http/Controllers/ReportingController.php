<?php

namespace App\Modules\Reporting\Http\Controllers;

use App\Core\BaseController;
use App\Modules\Reporting\Services\ReportingService;
use App\Modules\Reporting\Resources\SalesReportResource;
use App\Modules\Reporting\Resources\InventoryReportResource;
use App\Modules\Reporting\Resources\CustomerReportResource;
use App\Modules\Reporting\DTOs\ReportFilterDTO;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Reporting Controller
 * 
 * Handles analytics and KPI report generation
 * 
 * @package App\Modules\Reporting\Http\Controllers
 */
class ReportingController extends BaseController
{
    protected ReportingService $service;

    public function __construct(ReportingService $service)
    {
        $this->service = $service;
    }

    /**
     * Get sales report for a date range
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getSalesReport(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $report = $this->service->getSalesReport(
            $request->input('start_date'),
            $request->input('end_date')
        );

        return $this->resource(new SalesReportResource($report));
    }

    /**
     * Get inventory report
     *
     * @return JsonResponse
     */
    public function getInventoryReport(): JsonResponse
    {
        $report = $this->service->getInventoryReport();

        return $this->resource(new InventoryReportResource($report));
    }

    /**
     * Get customer report
     *
     * @return JsonResponse
     */
    public function getCustomerReport(): JsonResponse
    {
        $report = $this->service->getCustomerReport();

        return $this->resource(new CustomerReportResource($report));
    }

    /**
     * Get revenue KPIs for a period
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getRevenueKPIs(Request $request): JsonResponse
    {
        $request->validate([
            'period' => 'required|in:daily,weekly,monthly,yearly',
        ]);

        $kpis = $this->service->getRevenueKPIs($request->input('period'));

        return $this->success($kpis, 'Revenue KPIs retrieved successfully');
    }

    /**
     * Get top products by sales
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getTopProducts(Request $request): JsonResponse
    {
        $request->validate([
            'limit' => 'sometimes|integer|min:1|max:100',
        ]);

        $limit = $request->input('limit', 10);
        $topProducts = $this->service->getTopProducts($limit);

        return $this->success($topProducts, 'Top products retrieved successfully');
    }

    /**
     * Get top customers by revenue
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getTopCustomers(Request $request): JsonResponse
    {
        $request->validate([
            'limit' => 'sometimes|integer|min:1|max:100',
        ]);

        $limit = $request->input('limit', 10);
        $topCustomers = $this->service->getTopCustomers($limit);

        return $this->success($topCustomers, 'Top customers retrieved successfully');
    }

    /**
     * Get dashboard summary with key metrics
     *
     * @return JsonResponse
     */
    public function getDashboardSummary(): JsonResponse
    {
        $revenueKPIs = $this->service->getRevenueKPIs('monthly');
        $inventorySummary = $this->service->getInventoryReport()['summary'];
        $customerSummary = $this->service->getCustomerReport()['summary'];
        $topProducts = $this->service->getTopProducts(5);
        $topCustomers = $this->service->getTopCustomers(5);

        $dashboard = [
            'revenue_kpis' => $revenueKPIs,
            'inventory_summary' => $inventorySummary,
            'customer_summary' => $customerSummary,
            'top_products' => $topProducts['top_products'],
            'top_customers' => $topCustomers['top_customers'],
        ];

        return $this->success($dashboard, 'Dashboard summary retrieved successfully');
    }

    /**
     * Get sales trends over time
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getSalesTrends(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $report = $this->service->getSalesReport(
            $request->input('start_date'),
            $request->input('end_date')
        );

        return $this->success([
            'period' => $report['period'],
            'daily_breakdown' => $report['daily_breakdown'],
            'summary' => $report['summary'],
        ], 'Sales trends retrieved successfully');
    }
}
