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
}
