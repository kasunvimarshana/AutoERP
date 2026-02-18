<?php

namespace App\Modules\Reporting\Services;

use App\Modules\Reporting\DTOs\ScheduleReportDTO;
use App\Modules\Reporting\Events\ReportScheduled;
use App\Modules\Reporting\Models\ScheduledReport;
use App\Services\BaseService;
use Cron\CronExpression;

/**
 * Scheduled Report Service
 *
 * Handles scheduling and sending of reports.
 */
class ScheduledReportService extends BaseService
{
    public function __construct(
        protected ReportService $reportService,
        protected ExportService $exportService,
    ) {}

    /**
     * Create a scheduled report
     */
    public function scheduleReport(ScheduleReportDTO $dto): ScheduledReport
    {
        // Validate cron expression
        if (! CronExpression::isValidExpression($dto->scheduleCron)) {
            throw new \InvalidArgumentException('Invalid cron expression');
        }

        $data = $dto->toArray();
        $data['tenant_id'] = auth()->user()->tenant_id;

        // Calculate next run time
        $cron = new CronExpression($dto->scheduleCron);
        $data['next_run_at'] = $cron->getNextRunDate()->format('Y-m-d H:i:s');

        $scheduledReport = ScheduledReport::create($data);

        event(new ReportScheduled($scheduledReport));

        $this->logInfo('Report scheduled', [
            'report_id' => $dto->reportId,
            'schedule_cron' => $dto->scheduleCron,
            'next_run_at' => $data['next_run_at'],
        ]);

        return $scheduledReport;
    }

    /**
     * Update a scheduled report
     */
    public function updateSchedule(int $scheduleId, array $data): bool
    {
        $scheduledReport = ScheduledReport::findOrFail($scheduleId);

        // If cron changed, validate and recalculate next run
        if (isset($data['schedule_cron']) && $data['schedule_cron'] !== $scheduledReport->schedule_cron) {
            if (! CronExpression::isValidExpression($data['schedule_cron'])) {
                throw new \InvalidArgumentException('Invalid cron expression');
            }

            $cron = new CronExpression($data['schedule_cron']);
            $data['next_run_at'] = $cron->getNextRunDate()->format('Y-m-d H:i:s');
        }

        return $scheduledReport->update($data);
    }

    /**
     * Delete a scheduled report
     */
    public function deleteSchedule(int $scheduleId): bool
    {
        $scheduledReport = ScheduledReport::findOrFail($scheduleId);

        return $scheduledReport->delete();
    }

    /**
     * Get due scheduled reports
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getDueReports()
    {
        return ScheduledReport::where('is_active', true)
            ->where('next_run_at', '<=', now())
            ->with('report')
            ->get();
    }

    /**
     * Run scheduled report
     */
    public function runScheduledReport(ScheduledReport $scheduledReport): void
    {
        try {
            // Execute the report
            $executeDto = new \App\Modules\Reporting\DTOs\ExecuteReportDTO(
                reportId: $scheduledReport->report_id,
                parameters: [],
            );

            $result = $this->reportService->executeReport($executeDto);

            // Export the report
            $exportDto = new \App\Modules\Reporting\DTOs\ExportReportDTO(
                reportId: $scheduledReport->report_id,
                format: $scheduledReport->format,
                parameters: [],
                filename: $scheduledReport->report->name.'_'.date('Y-m-d_His'),
            );

            // Note: In production, generate the export and send via email
            // For now, we'll just log it
            $this->logInfo('Scheduled report executed', [
                'schedule_id' => $scheduledReport->id,
                'report_id' => $scheduledReport->report_id,
                'recipients' => $scheduledReport->recipients,
            ]);

            // Update last run and calculate next run
            $cron = new CronExpression($scheduledReport->schedule_cron);
            $scheduledReport->update([
                'last_run_at' => now(),
                'next_run_at' => $cron->getNextRunDate()->format('Y-m-d H:i:s'),
            ]);

        } catch (\Exception $e) {
            $this->logError('Scheduled report execution failed', [
                'schedule_id' => $scheduledReport->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Process all due reports
     */
    public function processDueReports(): array
    {
        $dueReports = $this->getDueReports();
        $results = [
            'processed' => 0,
            'failed' => 0,
        ];

        foreach ($dueReports as $scheduledReport) {
            try {
                $this->runScheduledReport($scheduledReport);
                $results['processed']++;
            } catch (\Exception $e) {
                $results['failed']++;
            }
        }

        return $results;
    }

    /**
     * Get all scheduled reports
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAllSchedules()
    {
        return ScheduledReport::with('report')->get();
    }

    /**
     * Get scheduled reports for a report
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getSchedulesForReport(int $reportId)
    {
        return ScheduledReport::where('report_id', $reportId)->get();
    }
}
