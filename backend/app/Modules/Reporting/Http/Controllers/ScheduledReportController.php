<?php

namespace App\Modules\Reporting\Http\Controllers;

use App\Http\Controllers\BaseController;
use App\Modules\Reporting\DTOs\ScheduleReportDTO;
use App\Modules\Reporting\Services\ScheduledReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Scheduled Report Controller
 *
 * Handles HTTP requests for scheduled reports.
 */
class ScheduledReportController extends BaseController
{
    public function __construct(
        protected ScheduledReportService $scheduledReportService,
    ) {}

    /**
     * Get all scheduled reports
     */
    public function index(): JsonResponse
    {
        try {
            $schedules = $this->scheduledReportService->getAllSchedules();

            return $this->successResponse($schedules, 'Scheduled reports retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), null, 500);
        }
    }

    /**
     * Get scheduled reports for a report
     */
    public function getForReport(int $reportId): JsonResponse
    {
        try {
            $schedules = $this->scheduledReportService->getSchedulesForReport($reportId);

            return $this->successResponse($schedules, 'Scheduled reports retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), null, 500);
        }
    }

    /**
     * Create a scheduled report
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'report_id' => 'required|integer|exists:reports,id',
                'schedule_cron' => 'required|string',
                'recipients' => 'required|array',
                'recipients.*' => 'email',
                'format' => 'required|string|in:csv,pdf,excel,json',
                'is_active' => 'boolean',
            ]);

            $dto = ScheduleReportDTO::fromArray($validated);
            $scheduledReport = $this->scheduledReportService->scheduleReport($dto);

            return $this->createdResponse($scheduledReport, 'Report scheduled successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), null, 500);
        }
    }

    /**
     * Update a scheduled report
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'schedule_cron' => 'sometimes|string',
                'recipients' => 'sometimes|array',
                'recipients.*' => 'email',
                'format' => 'sometimes|string|in:csv,pdf,excel,json',
                'is_active' => 'boolean',
            ]);

            $this->scheduledReportService->updateSchedule($id, $validated);

            return $this->successResponse(null, 'Scheduled report updated successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), null, 500);
        }
    }

    /**
     * Delete a scheduled report
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->scheduledReportService->deleteSchedule($id);

            return $this->successResponse(null, 'Scheduled report deleted successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), null, 500);
        }
    }

    /**
     * Get due reports
     */
    public function getDueReports(): JsonResponse
    {
        try {
            $dueReports = $this->scheduledReportService->getDueReports();

            return $this->successResponse($dueReports, 'Due reports retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), null, 500);
        }
    }

    /**
     * Process all due reports
     */
    public function processDueReports(): JsonResponse
    {
        try {
            $results = $this->scheduledReportService->processDueReports();

            return $this->successResponse($results, 'Due reports processed successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), null, 500);
        }
    }
}
