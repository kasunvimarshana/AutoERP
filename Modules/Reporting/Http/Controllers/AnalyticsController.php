<?php

declare(strict_types=1);

namespace Modules\Reporting\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Http\Responses\ApiResponse;
use Modules\Reporting\Services\AnalyticsService;

class AnalyticsController extends Controller
{
    public function __construct(
        private AnalyticsService $analyticsService
    ) {}

    /**
     * Get sales metrics
     */
    public function sales(Request $request): JsonResponse
    {
        $this->authorize('viewSales', \Modules\Reporting\Policies\AnalyticsPolicy::class);

        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'organization_id' => 'nullable|integer|exists:organizations,id',
        ]);

        // Check organization access
        if ($request->has('organization_id')) {
            $this->authorize('viewOrganization', [\Modules\Reporting\Policies\AnalyticsPolicy::class, $request->organization_id]);
        }

        $metrics = $this->analyticsService->salesMetrics(
            $request->start_date,
            $request->end_date,
            $request->organization_id
        );

        return ApiResponse::success(
            $metrics,
            'Sales metrics retrieved successfully'
        );
    }

    /**
     * Get inventory metrics
     */
    public function inventory(Request $request): JsonResponse
    {
        $this->authorize('viewInventory', \Modules\Reporting\Policies\AnalyticsPolicy::class);

        $request->validate([
            'organization_id' => 'nullable|integer|exists:organizations,id',
        ]);

        // Check organization access
        if ($request->has('organization_id')) {
            $this->authorize('viewOrganization', [\Modules\Reporting\Policies\AnalyticsPolicy::class, $request->organization_id]);
        }

        $metrics = $this->analyticsService->inventoryMetrics($request->organization_id);

        return ApiResponse::success(
            $metrics,
            'Inventory metrics retrieved successfully'
        );
    }

    /**
     * Get CRM metrics
     */
    public function crm(Request $request): JsonResponse
    {
        $this->authorize('viewCrm', \Modules\Reporting\Policies\AnalyticsPolicy::class);

        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'organization_id' => 'nullable|integer|exists:organizations,id',
        ]);

        // Check organization access
        if ($request->has('organization_id')) {
            $this->authorize('viewOrganization', [\Modules\Reporting\Policies\AnalyticsPolicy::class, $request->organization_id]);
        }

        $metrics = $this->analyticsService->crmMetrics(
            $request->start_date,
            $request->end_date,
            $request->organization_id
        );

        return ApiResponse::success(
            $metrics,
            'CRM metrics retrieved successfully'
        );
    }

    /**
     * Get financial metrics
     */
    public function financial(Request $request): JsonResponse
    {
        $this->authorize('viewFinancial', \Modules\Reporting\Policies\AnalyticsPolicy::class);

        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'organization_id' => 'nullable|integer|exists:organizations,id',
        ]);

        // Check organization access
        if ($request->has('organization_id')) {
            $this->authorize('viewOrganization', [\Modules\Reporting\Policies\AnalyticsPolicy::class, $request->organization_id]);
        }

        $metrics = $this->analyticsService->financialMetrics(
            $request->start_date,
            $request->end_date,
            $request->organization_id
        );

        return ApiResponse::success(
            $metrics,
            'Financial metrics retrieved successfully'
        );
    }

    /**
     * Get top selling products
     */
    public function topProducts(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'limit' => 'nullable|integer|min:1|max:50',
        ]);

        $products = $this->analyticsService->topSellingProducts(
            $request->start_date,
            $request->end_date,
            $request->get('limit', 10)
        );

        return ApiResponse::success(
            $products,
            'Top selling products retrieved successfully'
        );
    }

    /**
     * Get customer analytics
     */
    public function customers(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $analytics = $this->analyticsService->customerAnalytics(
            $request->start_date,
            $request->end_date
        );

        return ApiResponse::success(
            $analytics,
            'Customer analytics retrieved successfully'
        );
    }

    /**
     * Get trend data
     */
    public function trend(Request $request): JsonResponse
    {
        $request->validate([
            'metric' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'interval' => 'nullable|in:hour,day,week,month,year',
        ]);

        $trend = $this->analyticsService->getTrend(
            $request->metric,
            $request->start_date,
            $request->end_date,
            $request->get('interval', 'day')
        );

        return ApiResponse::success(
            $trend,
            'Trend data retrieved successfully'
        );
    }
}
