<?php

namespace App\Modules\Finance\Http\Controllers;

use App\Http\Controllers\BaseController;
use App\Modules\Finance\Services\FiscalYearService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Fiscal Year API Controller
 *
 * Handles HTTP requests for fiscal year management.
 */
class FiscalYearController extends BaseController
{
    public function __construct(
        protected FiscalYearService $fiscalYearService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $fiscalYears = $this->fiscalYearService->list($perPage);

        return $this->successResponse($fiscalYears, 'Fiscal years retrieved successfully');
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
        ]);

        $fiscalYear = $this->fiscalYearService->create($validated);

        return $this->createdResponse($fiscalYear, 'Fiscal year created successfully');
    }

    public function show(int $id): JsonResponse
    {
        $fiscalYear = $this->fiscalYearService->find($id);

        return $this->successResponse($fiscalYear, 'Fiscal year retrieved successfully');
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
        ]);

        try {
            $fiscalYear = $this->fiscalYearService->update($id, $validated);

            return $this->successResponse($fiscalYear, 'Fiscal year updated successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), null, 400);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $this->fiscalYearService->delete($id);

            return $this->successResponse(null, 'Fiscal year deleted successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), null, 400);
        }
    }

    public function close(int $id): JsonResponse
    {
        try {
            $fiscalYear = $this->fiscalYearService->close($id);

            return $this->successResponse($fiscalYear, 'Fiscal year closed successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), null, 400);
        }
    }

    public function open(): JsonResponse
    {
        $fiscalYears = $this->fiscalYearService->getOpenFiscalYears();

        return $this->successResponse($fiscalYears, 'Open fiscal years retrieved successfully');
    }

    public function closed(): JsonResponse
    {
        $fiscalYears = $this->fiscalYearService->getClosedFiscalYears();

        return $this->successResponse($fiscalYears, 'Closed fiscal years retrieved successfully');
    }

    public function current(): JsonResponse
    {
        $fiscalYear = $this->fiscalYearService->getCurrentFiscalYear();

        if (! $fiscalYear) {
            return $this->notFoundResponse('No current fiscal year found');
        }

        return $this->successResponse($fiscalYear, 'Current fiscal year retrieved successfully');
    }
}
