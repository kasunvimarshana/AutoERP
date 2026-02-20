<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\ReportingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportingController extends Controller
{
    public function __construct(
        private readonly ReportingService $reportingService
    ) {}

    public function salesSummary(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('reports.view'), 403);

        $data = $request->validate([
            'date_from' => 'required|date',
            'date_to' => 'required|date',
            'organization_id' => 'nullable|uuid|exists:organizations,id',
        ]);

        $result = $this->reportingService->salesSummary(
            $request->user()->tenant_id,
            $data['date_from'],
            $data['date_to'],
            $data['organization_id'] ?? null
        );

        return response()->json($result);
    }

    public function inventorySummary(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('reports.view'), 403);

        $result = $this->reportingService->inventorySummary($request->user()->tenant_id);

        return response()->json($result);
    }

    public function receivablesSummary(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('reports.view'), 403);

        $result = $this->reportingService->receivablesSummary($request->user()->tenant_id);

        return response()->json($result);
    }

    public function topProducts(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('reports.view'), 403);

        $data = $request->validate([
            'date_from' => 'required|date',
            'date_to' => 'required|date',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        $result = $this->reportingService->topProducts(
            $request->user()->tenant_id,
            $data['date_from'],
            $data['date_to'],
            (int) ($data['limit'] ?? 10)
        );

        return response()->json($result);
    }

    public function posSalesSummary(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('reports.view'), 403);

        $data = $request->validate([
            'date_from' => 'required|date',
            'date_to' => 'required|date',
            'business_location_id' => 'nullable|uuid|exists:business_locations,id',
        ]);

        $result = $this->reportingService->posSalesSummary(
            $request->user()->tenant_id,
            $data['date_from'],
            $data['date_to'],
            $data['business_location_id'] ?? null
        );

        return response()->json($result);
    }

    public function purchaseSummary(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('reports.view'), 403);

        $data = $request->validate([
            'date_from' => 'required|date',
            'date_to' => 'required|date',
        ]);

        $result = $this->reportingService->purchaseSummary(
            $request->user()->tenant_id,
            $data['date_from'],
            $data['date_to']
        );

        return response()->json($result);
    }

    public function expenseSummary(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('reports.view'), 403);

        $data = $request->validate([
            'date_from' => 'required|date',
            'date_to' => 'required|date',
            'business_location_id' => 'nullable|uuid|exists:business_locations,id',
        ]);

        $result = $this->reportingService->expenseSummary(
            $request->user()->tenant_id,
            $data['date_from'],
            $data['date_to'],
            $data['business_location_id'] ?? null
        );

        return response()->json($result);
    }

    public function profitLoss(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('reports.view'), 403);

        $data = $request->validate([
            'date_from' => 'required|date',
            'date_to' => 'required|date',
            'business_location_id' => 'nullable|uuid|exists:business_locations,id',
        ]);

        $result = $this->reportingService->profitLoss(
            $request->user()->tenant_id,
            $data['date_from'],
            $data['date_to'],
            $data['business_location_id'] ?? null
        );

        return response()->json($result);
    }

    public function taxReport(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('reports.view'), 403);

        $data = $request->validate([
            'date_from' => 'required|date',
            'date_to' => 'required|date',
            'business_location_id' => 'nullable|uuid|exists:business_locations,id',
        ]);

        $result = $this->reportingService->taxReport(
            $request->user()->tenant_id,
            $data['date_from'],
            $data['date_to'],
            $data['business_location_id'] ?? null
        );

        return response()->json($result);
    }

    public function stockExpiry(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('reports.view'), 403);

        $data = $request->validate([
            'days_ahead' => 'nullable|integer|min:1|max:365',
            'warehouse_id' => 'nullable|uuid|exists:warehouses,id',
        ]);

        $result = $this->reportingService->stockExpiry(
            $request->user()->tenant_id,
            (int) ($data['days_ahead'] ?? 30),
            $data['warehouse_id'] ?? null
        );

        return response()->json($result);
    }

    public function registerReport(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('reports.view'), 403);

        $data = $request->validate([
            'date_from' => 'required|date',
            'date_to' => 'required|date',
            'business_location_id' => 'nullable|uuid|exists:business_locations,id',
        ]);

        $result = $this->reportingService->registerReport(
            $request->user()->tenant_id,
            $data['date_from'],
            $data['date_to'],
            $data['business_location_id'] ?? null
        );

        return response()->json($result);
    }

    public function customerGroupReport(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('reports.view'), 403);

        $data = $request->validate([
            'date_from' => 'required|date',
            'date_to' => 'required|date',
        ]);

        $result = $this->reportingService->customerGroupReport(
            $request->user()->tenant_id,
            $data['date_from'],
            $data['date_to']
        );

        return response()->json($result);
    }

    public function productSellReport(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('reports.view'), 403);

        $data = $request->validate([
            'date_from' => 'required|date',
            'date_to' => 'required|date',
            'business_location_id' => 'nullable|uuid|exists:business_locations,id',
        ]);

        $result = $this->reportingService->productSellReport(
            $request->user()->tenant_id,
            $data['date_from'],
            $data['date_to'],
            $data['business_location_id'] ?? null
        );

        return response()->json($result);
    }

    public function productPurchaseReport(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('reports.view'), 403);

        $data = $request->validate([
            'date_from' => 'required|date',
            'date_to' => 'required|date',
            'supplier_id' => 'nullable|uuid|exists:contacts,id',
        ]);

        $result = $this->reportingService->productPurchaseReport(
            $request->user()->tenant_id,
            $data['date_from'],
            $data['date_to'],
            $data['supplier_id'] ?? null
        );

        return response()->json($result);
    }

    public function salesRepresentativeReport(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('reports.view'), 403);

        $data = $request->validate([
            'date_from' => 'required|date',
            'date_to' => 'required|date',
        ]);

        $result = $this->reportingService->salesRepresentativeReport(
            $request->user()->tenant_id,
            $data['date_from'],
            $data['date_to']
        );

        return response()->json($result);
    }

    public function trendingProducts(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('reports.view'), 403);

        $data = $request->validate([
            'date_from' => 'required|date',
            'date_to' => 'required|date',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        $result = $this->reportingService->trendingProducts(
            $request->user()->tenant_id,
            $data['date_from'],
            $data['date_to'],
            (int) ($data['limit'] ?? 10)
        );

        return response()->json($result);
    }

    public function lotReport(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('reports.view'), 403);

        $data = $request->validate([
            'warehouse_id' => 'nullable|uuid|exists:warehouses,id',
        ]);

        $result = $this->reportingService->lotReport(
            $request->user()->tenant_id,
            $data['warehouse_id'] ?? null
        );

        return response()->json($result);
    }
}
