<?php

namespace App\Modules\Reporting\Http\Controllers;

use App\Http\Controllers\BaseController;
use App\Modules\Reporting\DTOs\CreateReportDTO;
use App\Modules\Reporting\DTOs\ExecuteReportDTO;
use App\Modules\Reporting\DTOs\ExportReportDTO;
use App\Modules\Reporting\Services\ExportService;
use App\Modules\Reporting\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Report Controller
 *
 * Handles HTTP requests for report management.
 */
class ReportController extends BaseController
{
    public function __construct(
        protected ReportService $reportService,
        protected ExportService $exportService,
    ) {}

    /**
     * Get all reports
     */
    public function index(): JsonResponse
    {
        try {
            $reports = $this->reportService->getAllReports();

            return $this->successResponse($reports, 'Reports retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), null, 500);
        }
    }

    /**
     * Get reports by module
     */
    public function getByModule(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'module' => 'required|string',
            ]);

            $reports = $this->reportService->getReportsByModule($request->module);

            return $this->successResponse($reports, 'Reports retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), null, 500);
        }
    }

    /**
     * Get a specific report
     */
    public function show(string|int $id): JsonResponse
    {
        try {
            $report = $this->reportService->getReport($id);

            return $this->successResponse($report, 'Report retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), null, 404);
        }
    }

    /**
     * Create a new report
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'report_type' => 'required|string',
                'module' => 'required|string',
                'query_config' => 'required|array',
                'description' => 'nullable|string',
                'schedule_config' => 'nullable|array',
                'is_public' => 'boolean',
            ]);

            $validated['created_by_id'] = auth()->id();
            $dto = CreateReportDTO::fromArray($validated);

            $report = $this->reportService->createReport($dto);

            return $this->createdResponse($report, 'Report created successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), null, 500);
        }
    }

    /**
     * Update a report
     */
    public function update(Request $request, string|int $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'report_type' => 'sometimes|string',
                'module' => 'sometimes|string',
                'query_config' => 'sometimes|array',
                'description' => 'nullable|string',
                'schedule_config' => 'nullable|array',
                'is_public' => 'boolean',
            ]);

            $this->reportService->updateReport($id, $validated);
            $report = $this->reportService->getReport($id);

            return $this->successResponse($report, 'Report updated successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), null, 500);
        }
    }

    /**
     * Delete a report
     */
    public function destroy(string|int $id): JsonResponse
    {
        try {
            $this->reportService->deleteReport($id);

            return $this->successResponse(null, 'Report deleted successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), null, 500);
        }
    }

    /**
     * Execute a report
     */
    public function execute(Request $request, string|int $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'parameters' => 'sometimes|array',
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after_or_equal:start_date',
                'limit' => 'nullable|integer|min:1',
                'page' => 'nullable|integer|min:1',
            ]);

            $validated['report_id'] = $id;
            $validated['executed_by_id'] = auth()->id();

            $dto = ExecuteReportDTO::fromArray($validated);
            $result = $this->reportService->executeReport($dto);

            return $this->successResponse($result, 'Report executed successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), null, 500);
        }
    }

    /**
     * Export a report
     *
     * @return mixed
     */
    public function export(Request $request, string|int $id)
    {
        try {
            $validated = $request->validate([
                'format' => 'required|string|in:csv,pdf,excel,json',
                'parameters' => 'sometimes|array',
                'filename' => 'nullable|string',
            ]);

            $validated['report_id'] = $id;

            // First execute the report to get data
            $executeDto = ExecuteReportDTO::fromArray([
                'report_id' => $id,
                'parameters' => $validated['parameters'] ?? [],
                'executed_by_id' => auth()->id(),
            ]);

            $result = $this->reportService->executeReport($executeDto);

            // Then export the results
            $exportDto = ExportReportDTO::fromArray($validated);

            return $this->exportService->export($exportDto, $result['results']);

        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), null, 500);
        }
    }
}
