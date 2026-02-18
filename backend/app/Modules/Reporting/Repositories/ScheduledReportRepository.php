<?php

namespace App\Modules\Reporting\Repositories;

use App\Modules\Reporting\Models\ScheduledReport;
use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;

/**
 * ScheduledReportRepository
 *
 * Repository for managing scheduled report data access.
 */
class ScheduledReportRepository extends BaseRepository
{
    /**
     * Specify Model class name
     */
    protected function model(): string
    {
        return ScheduledReport::class;
    }

    /**
     * Get active scheduled reports
     */
    public function getActive(): Collection
    {
        return $this->query()
            ->where('is_active', true)
            ->with(['report'])
            ->get();
    }

    /**
     * Get scheduled reports by report ID
     */
    public function getByReport(int $reportId): Collection
    {
        return $this->query()
            ->where('report_id', $reportId)
            ->get();
    }

    /**
     * Get due scheduled reports
     */
    public function getDueReports(): Collection
    {
        return $this->query()
            ->where('is_active', true)
            ->where('next_run_at', '<=', now())
            ->with(['report'])
            ->get();
    }

    /**
     * Update last run timestamp
     */
    public function updateLastRun(int $id): bool
    {
        return $this->update($id, [
            'last_run_at' => now(),
        ]);
    }

    /**
     * Update next run timestamp
     */
    public function updateNextRun(int $id, \DateTime $nextRunAt): bool
    {
        return $this->update($id, [
            'next_run_at' => $nextRunAt,
        ]);
    }

    /**
     * Activate scheduled report
     */
    public function activate(int $id): bool
    {
        return $this->update($id, [
            'is_active' => true,
        ]);
    }

    /**
     * Deactivate scheduled report
     */
    public function deactivate(int $id): bool
    {
        return $this->update($id, [
            'is_active' => false,
        ]);
    }

    /**
     * Get scheduled reports by recipient email
     */
    public function getByRecipient(string $email): Collection
    {
        return $this->query()
            ->whereJsonContains('recipients', $email)
            ->with(['report'])
            ->get();
    }

    /**
     * Get upcoming scheduled reports
     *
     * @param  int  $hours  Number of hours to look ahead
     */
    public function getUpcoming(int $hours = 24): Collection
    {
        return $this->query()
            ->where('is_active', true)
            ->whereBetween('next_run_at', [now(), now()->addHours($hours)])
            ->with(['report'])
            ->orderBy('next_run_at')
            ->get();
    }
}
