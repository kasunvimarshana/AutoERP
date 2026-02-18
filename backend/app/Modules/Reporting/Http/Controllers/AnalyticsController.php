<?php

namespace App\Modules\Reporting\Http\Controllers;

use App\Http\Controllers\BaseController;
use App\Modules\Reporting\Services\AnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Analytics Controller
 *
 * Handles HTTP requests for analytics and KPIs.
 */
class AnalyticsController extends BaseController
{
    public function __construct(
        protected AnalyticsService $analyticsService,
    ) {}

    /**
     * Get all KPIs for a period
     */
    public function getKPIs(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
            ]);

            $kpis = $this->analyticsService->getAllKPIs(
                $validated['start_date'],
                $validated['end_date']
            );

            return $this->successResponse($kpis, 'KPIs retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), null, 500);
        }
    }

    /**
     * Get total revenue
     */
    public function getTotalRevenue(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
            ]);

            $revenue = $this->analyticsService->getTotalRevenue(
                $validated['start_date'],
                $validated['end_date']
            );

            return $this->successResponse(['total_revenue' => $revenue], 'Revenue retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), null, 500);
        }
    }

    /**
     * Get total expenses
     */
    public function getTotalExpenses(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
            ]);

            $expenses = $this->analyticsService->getTotalExpenses(
                $validated['start_date'],
                $validated['end_date']
            );

            return $this->successResponse(['total_expenses' => $expenses], 'Expenses retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), null, 500);
        }
    }

    /**
     * Get gross profit margin
     */
    public function getGrossProfitMargin(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
            ]);

            $margin = $this->analyticsService->getGrossProfitMargin(
                $validated['start_date'],
                $validated['end_date']
            );

            return $this->successResponse(['gross_profit_margin' => $margin], 'Gross profit margin retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), null, 500);
        }
    }

    /**
     * Get net profit margin
     */
    public function getNetProfitMargin(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
            ]);

            $margin = $this->analyticsService->getNetProfitMargin(
                $validated['start_date'],
                $validated['end_date']
            );

            return $this->successResponse(['net_profit_margin' => $margin], 'Net profit margin retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), null, 500);
        }
    }

    /**
     * Get inventory turnover ratio
     */
    public function getInventoryTurnoverRatio(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
            ]);

            $ratio = $this->analyticsService->getInventoryTurnoverRatio(
                $validated['start_date'],
                $validated['end_date']
            );

            return $this->successResponse(['inventory_turnover_ratio' => $ratio], 'Inventory turnover ratio retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), null, 500);
        }
    }

    /**
     * Get days sales outstanding
     */
    public function getDaysSalesOutstanding(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
            ]);

            $dso = $this->analyticsService->getDaysSalesOutstanding(
                $validated['start_date'],
                $validated['end_date']
            );

            return $this->successResponse(['days_sales_outstanding' => $dso], 'DSO retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), null, 500);
        }
    }

    /**
     * Get order fulfillment rate
     */
    public function getOrderFulfillmentRate(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
            ]);

            $rate = $this->analyticsService->getOrderFulfillmentRate(
                $validated['start_date'],
                $validated['end_date']
            );

            return $this->successResponse(['order_fulfillment_rate' => $rate], 'Order fulfillment rate retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), null, 500);
        }
    }

    /**
     * Get production efficiency
     */
    public function getProductionEfficiency(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
            ]);

            $efficiency = $this->analyticsService->getProductionEfficiency(
                $validated['start_date'],
                $validated['end_date']
            );

            return $this->successResponse(['production_efficiency' => $efficiency], 'Production efficiency retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), null, 500);
        }
    }

    /**
     * Get customer acquisition cost
     */
    public function getCustomerAcquisitionCost(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
            ]);

            $cac = $this->analyticsService->getCustomerAcquisitionCost(
                $validated['start_date'],
                $validated['end_date']
            );

            return $this->successResponse(['customer_acquisition_cost' => $cac], 'CAC retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), null, 500);
        }
    }

    /**
     * Get average order value
     */
    public function getAverageOrderValue(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
            ]);

            $aov = $this->analyticsService->getAverageOrderValue(
                $validated['start_date'],
                $validated['end_date']
            );

            return $this->successResponse(['average_order_value' => $aov], 'AOV retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), null, 500);
        }
    }
}
