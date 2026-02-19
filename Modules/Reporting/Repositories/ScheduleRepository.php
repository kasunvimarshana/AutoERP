<?php

declare(strict_types=1);

namespace Modules\Reporting\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Core\Repositories\BaseRepository;
use Modules\Reporting\Models\ReportSchedule;

class ScheduleRepository extends BaseRepository
{
    /**
     * Get the model class name.
     */
    protected function getModelClass(): string
    {
        return ReportSchedule::class;
    }

    /**
     * Find schedule by ID
     */
    public function findById(int $id): ?ReportSchedule
    {
        return $this->model->with('report')->find($id);
    }

    /**
     * Get all schedules with optional filters
     */
    public function getAll(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->query()->with('report');

        if (isset($filters['report_id'])) {
            $query->where('report_id', $filters['report_id']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (isset($filters['frequency'])) {
            $query->where('frequency', $filters['frequency']);
        }

        return $query->orderBy('next_run_at')->paginate($perPage);
    }

    /**
     * Get active schedules
     */
    public function getActiveSchedules(): Collection
    {
        return $this->model->where('is_active', true)
            ->with('report')
            ->get();
    }

    /**
     * Get schedules due for execution
     */
    public function getDueSchedules(): Collection
    {
        return $this->model->where('is_active', true)
            ->where('next_run_at', '<=', now())
            ->with('report')
            ->get();
    }

    /**
     * Update schedule
     */
    public function updateSchedule(ReportSchedule $schedule, array $data): bool
    {
        return $schedule->update($data);
    }

    /**
     * Delete schedule
     */
    public function deleteSchedule(ReportSchedule $schedule): bool
    {
        return $schedule->delete();
    }

    /**
     * Update last run
     */
    public function updateLastRun(ReportSchedule $schedule): void
    {
        $schedule->updateLastRun();
    }
}
