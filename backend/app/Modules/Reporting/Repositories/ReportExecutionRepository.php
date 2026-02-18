<?php

namespace App\Modules\Reporting\Repositories;

use App\Modules\Reporting\Enums\ReportExecutionStatus;
use App\Modules\Reporting\Models\ReportExecution;
use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * ReportExecutionRepository
 *
 * Repository for managing report execution data access.
 */
class ReportExecutionRepository extends BaseRepository
{
    /**
     * Specify Model class name
     */
    protected function model(): string
    {
        return ReportExecution::class;
    }

    /**
     * Get executions by report ID
     */
    public function getByReport(int $reportId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->query()
            ->where('report_id', $reportId)
            ->with(['executedBy'])
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Get executions by user ID
     */
    public function getByUser(int $userId): Collection
    {
        return $this->query()
            ->where('executed_by_id', $userId)
            ->with(['report'])
            ->latest()
            ->get();
    }

    /**
     * Get executions by status
     */
    public function getByStatus(ReportExecutionStatus $status): Collection
    {
        return $this->query()
            ->where('status', $status)
            ->with(['report', 'executedBy'])
            ->latest()
            ->get();
    }

    /**
     * Get running executions
     */
    public function getRunning(): Collection
    {
        return $this->getByStatus(ReportExecutionStatus::RUNNING);
    }

    /**
     * Get failed executions
     */
    public function getFailed(): Collection
    {
        return $this->getByStatus(ReportExecutionStatus::FAILED);
    }

    /**
     * Get completed executions
     */
    public function getCompleted(): Collection
    {
        return $this->getByStatus(ReportExecutionStatus::COMPLETED);
    }

    /**
     * Get recent executions
     */
    public function getRecent(int $limit = 10): Collection
    {
        return $this->query()
            ->with(['report', 'executedBy'])
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * Get average execution time for a report
     */
    public function getAverageExecutionTime(int $reportId): float
    {
        return $this->query()
            ->where('report_id', $reportId)
            ->where('status', ReportExecutionStatus::COMPLETED)
            ->avg('execution_time') ?? 0.0;
    }

    /**
     * Get execution statistics for a report
     */
    public function getStatistics(int $reportId): array
    {
        // Use database aggregation for better performance
        $stats = $this->query()
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as failed,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as running,
                AVG(CASE WHEN status = ? THEN execution_time ELSE NULL END) as average_time
            ', [
                ReportExecutionStatus::COMPLETED->value,
                ReportExecutionStatus::FAILED->value,
                ReportExecutionStatus::RUNNING->value,
                ReportExecutionStatus::COMPLETED->value,
            ])
            ->where('report_id', $reportId)
            ->first();

        $lastExecution = $this->query()
            ->where('report_id', $reportId)
            ->with(['executedBy'])
            ->latest()
            ->first();

        return [
            'total' => (int) $stats->total,
            'completed' => (int) $stats->completed,
            'failed' => (int) $stats->failed,
            'running' => (int) $stats->running,
            'average_time' => (float) ($stats->average_time ?? 0.0),
            'last_execution' => $lastExecution,
        ];
    }

    /**
     * Clean up old executions
     *
     * @return int Number of deleted records
     */
    public function cleanupOldExecutions(int $daysOld = 90): int
    {
        return $this->query()
            ->where('created_at', '<', now()->subDays($daysOld))
            ->delete();
    }
}
