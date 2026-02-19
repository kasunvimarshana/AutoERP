<?php

declare(strict_types=1);

namespace Modules\Reporting\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Core\Repositories\BaseRepository;
use Modules\Reporting\Models\ReportExecution;

class ExecutionRepository extends BaseRepository
{
    /**
     * Get the model class name.
     */
    protected function getModelClass(): string
    {
        return ReportExecution::class;
    }

    /**
     * Find execution by ID
     */
    public function findById(int $id): ?ReportExecution
    {
        return $this->model->with('report')->find($id);
    }

    /**
     * Get all executions with optional filters
     */
    public function getAll(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->query()->with('report', 'user');

        if (isset($filters['report_id'])) {
            $query->where('report_id', $filters['report_id']);
        }

        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (isset($filters['schedule_id'])) {
            $query->where('schedule_id', $filters['schedule_id']);
        }

        if (isset($filters['success'])) {
            if ($filters['success']) {
                $query->whereNotNull('completed_at')->whereNull('failed_at');
            } else {
                $query->whereNotNull('failed_at');
            }
        }

        return $query->orderBy('started_at', 'desc')->paginate($perPage);
    }

    /**
     * Get report execution history
     */
    public function getReportHistory(int $reportId, int $limit = 10): Collection
    {
        return $this->model->where('report_id', $reportId)
            ->orderBy('started_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Update execution
     */
    public function updateExecution(ReportExecution $execution, array $data): bool
    {
        return $execution->update($data);
    }

    /**
     * Mark as completed
     */
    public function markAsCompleted(ReportExecution $execution, int $resultCount, float $executionTime): void
    {
        $execution->markAsCompleted($resultCount, $executionTime);
    }

    /**
     * Mark as failed
     */
    public function markAsFailed(ReportExecution $execution, string $errorMessage): void
    {
        $execution->markAsFailed($errorMessage);
    }
}
