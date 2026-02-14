<?php

namespace App\Modules\Analytics\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Analytics\Services\AnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Analytics Controller
 *
 * @OA\Tag(name="Analytics", description="Analytics and reporting endpoints")
 */
class AnalyticsController extends Controller
{
    protected AnalyticsService $analyticsService;

    public function __construct(AnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    public function dashboard(Request $request): JsonResponse
    {
        try {
            $startDate = $request->get('start_date');
            $endDate = $request->get('end_date');

            $dashboardData = $this->analyticsService->getDashboardMetrics($startDate, $endDate);

            return response()->json([
                'success' => true,
                'data' => $dashboardData,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function reports(Request $request): JsonResponse
    {
        try {
            $type = $request->get('type', 'summary');
            $startDate = $request->get('start_date');
            $endDate = $request->get('end_date');

            $reportData = match ($type) {
                'sales' => $this->analyticsService->getSalesReport($startDate, $endDate),
                'revenue' => $this->analyticsService->getRevenueReport($startDate, $endDate),
                'inventory' => $this->analyticsService->getInventoryReport(),
                'customer' => $this->analyticsService->getCustomerReport($startDate, $endDate),
                default => $this->analyticsService->getDashboardMetrics($startDate, $endDate),
            };

            return response()->json([
                'success' => true,
                'type' => $type,
                'data' => $reportData,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function salesReport(Request $request): JsonResponse
    {
        try {
            $startDate = $request->get('start_date');
            $endDate = $request->get('end_date');

            $salesData = $this->analyticsService->getSalesReport($startDate, $endDate);

            return response()->json([
                'success' => true,
                'data' => $salesData,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function revenueReport(Request $request): JsonResponse
    {
        try {
            $startDate = $request->get('start_date');
            $endDate = $request->get('end_date');

            $revenueData = $this->analyticsService->getRevenueReport($startDate, $endDate);

            return response()->json([
                'success' => true,
                'data' => $revenueData,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function inventoryReport(Request $request): JsonResponse
    {
        try {
            $inventoryData = $this->analyticsService->getInventoryReport();

            return response()->json([
                'success' => true,
                'data' => $inventoryData,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function customerReport(Request $request): JsonResponse
    {
        try {
            $startDate = $request->get('start_date');
            $endDate = $request->get('end_date');

            $customerData = $this->analyticsService->getCustomerReport($startDate, $endDate);

            return response()->json([
                'success' => true,
                'data' => $customerData,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
