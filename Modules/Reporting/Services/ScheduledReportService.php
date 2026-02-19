<?php

declare(strict_types=1);

namespace Modules\Reporting\Services;

use Modules\Notification\Services\NotificationService;
use Modules\Reporting\Enums\ScheduleFrequency;
use Modules\Reporting\Models\ReportSchedule;
use Modules\Reporting\Repositories\ScheduleRepository;

/**
 * ScheduledReportService
 *
 * Manages scheduled report execution
 */
class ScheduledReportService
{
    public function __construct(
        private ScheduleRepository $scheduleRepository,
        private ReportBuilderService $reportBuilderService,
        private ReportExportService $exportService,
        private ?NotificationService $notificationService = null
    ) {}

    /**
     * Create new report schedule
     */
    public function schedule(int $reportId, array $data): ReportSchedule
    {
        $data['report_id'] = $reportId;
        $data['tenant_id'] = auth()->user()->tenant_id;
        $data['organization_id'] = auth()->user()->organization_id;

        // Calculate next run time
        if (! isset($data['next_run_at'])) {
            $frequency = ScheduleFrequency::from($data['frequency']);
            $data['next_run_at'] = $this->calculateNextRunTime($frequency);
        }

        // Set cron expression if not provided
        if (! isset($data['cron_expression']) && isset($data['frequency'])) {
            $frequency = ScheduleFrequency::from($data['frequency']);
            $data['cron_expression'] = $frequency->cronExpression();
        }

        return $this->scheduleRepository->create($data);
    }

    /**
     * Update schedule
     */
    public function updateSchedule(ReportSchedule $schedule, array $data): bool
    {
        // Recalculate next run time if frequency changed
        if (isset($data['frequency']) && $data['frequency'] !== $schedule->frequency->value) {
            $frequency = ScheduleFrequency::from($data['frequency']);
            $data['next_run_at'] = $this->calculateNextRunTime($frequency);
            $data['cron_expression'] = $frequency->cronExpression();
        }

        return $this->scheduleRepository->updateSchedule($schedule, $data);
    }

    /**
     * Execute scheduled reports
     */
    public function executeScheduled(): array
    {
        $schedules = $this->scheduleRepository->getDueSchedules();
        $results = [];

        foreach ($schedules as $schedule) {
            try {
                $result = $this->executeSchedule($schedule);
                $results[] = [
                    'schedule_id' => $schedule->id,
                    'success' => true,
                    'result' => $result,
                ];
            } catch (\Exception $e) {
                $results[] = [
                    'schedule_id' => $schedule->id,
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Execute a single schedule
     */
    public function executeSchedule(ReportSchedule $schedule): array
    {
        // Execute report
        $reportResult = $this->reportBuilderService->execute(
            $schedule->report,
            $schedule->parameters['filters'] ?? []
        );

        $exports = [];

        // Export in specified formats
        foreach ($schedule->export_formats as $format) {
            $formatEnum = \Modules\Reporting\Enums\ExportFormat::from($format);
            $filename = "{$schedule->report->name}_{$schedule->name}_".now()->format('YmdHis');

            try {
                $path = $this->exportService->export(
                    $reportResult['data']->toArray(),
                    $formatEnum,
                    $filename
                );
                $exports[] = [
                    'format' => $format,
                    'path' => $path,
                ];
            } catch (\Exception $e) {
                $exports[] = [
                    'format' => $format,
                    'error' => $e->getMessage(),
                ];
            }
        }

        // Send to recipients
        $this->notifyRecipients($schedule, $exports);

        // Update schedule
        $this->scheduleRepository->updateLastRun($schedule);

        return [
            'execution_id' => $reportResult['execution_id'],
            'result_count' => $reportResult['count'],
            'execution_time' => $reportResult['execution_time'],
            'exports' => $exports,
        ];
    }

    /**
     * Notify recipients about report
     */
    private function notifyRecipients(ReportSchedule $schedule, array $exports): void
    {
        if (! $this->notificationService || empty($schedule->recipients)) {
            return;
        }

        $exportLinks = collect($exports)
            ->filter(fn ($export) => ! isset($export['error']))
            ->map(fn ($export) => [
                'format' => $export['format'],
                'url' => $this->exportService->getDownloadUrl($export['path']),
            ])
            ->toArray();

        foreach ($schedule->recipients as $recipient) {
            try {
                $this->notificationService->send([
                    'user_id' => $recipient['user_id'] ?? null,
                    'type' => 'scheduled_report',
                    'subject' => "Scheduled Report: {$schedule->name}",
                    'body' => "Your scheduled report '{$schedule->name}' has been generated.",
                    'data' => [
                        'schedule_id' => $schedule->id,
                        'report_id' => $schedule->report_id,
                        'exports' => $exportLinks,
                    ],
                ]);
            } catch (\Exception $e) {
                // Log error but continue
                \Log::error("Failed to notify recipient for schedule {$schedule->id}: {$e->getMessage()}");
            }
        }
    }

    /**
     * Calculate next run time based on frequency
     */
    private function calculateNextRunTime(ScheduleFrequency $frequency): \DateTime
    {
        $now = now();

        return match ($frequency) {
            ScheduleFrequency::DAILY => $now->addDay(),
            ScheduleFrequency::WEEKLY => $now->addWeek(),
            ScheduleFrequency::MONTHLY => $now->addMonth(),
            ScheduleFrequency::QUARTERLY => $now->addMonths(3),
            ScheduleFrequency::YEARLY => $now->addYear(),
        };
    }

    /**
     * Activate schedule
     */
    public function activate(ReportSchedule $schedule): void
    {
        $schedule->activate();
    }

    /**
     * Deactivate schedule
     */
    public function deactivate(ReportSchedule $schedule): void
    {
        $schedule->deactivate();
    }

    /**
     * Delete schedule
     */
    public function deleteSchedule(ReportSchedule $schedule): bool
    {
        return $this->scheduleRepository->deleteSchedule($schedule);
    }
}
