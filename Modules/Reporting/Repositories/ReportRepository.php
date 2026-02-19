<?php

declare(strict_types=1);

namespace Modules\Reporting\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Core\Repositories\BaseRepository;
use Modules\Reporting\Enums\ReportStatus;
use Modules\Reporting\Enums\ReportType;
use Modules\Reporting\Models\Report;

class ReportRepository extends BaseRepository
{
    /**
     * Get the model class name.
     */
    protected function getModelClass(): string
    {
        return Report::class;
    }

    /**
     * Find report by ID
     */
    public function findById(int $id): ?Report
    {
        return $this->find($id);
    }

    /**
     * Get all reports with optional filters
     */
    public function getAll(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->query();

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['is_template'])) {
            $query->where('is_template', $filters['is_template']);
        }

        if (isset($filters['is_shared'])) {
            $query->where('is_shared', $filters['is_shared']);
        }

        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', "%{$filters['search']}%")
                    ->orWhere('description', 'like', "%{$filters['search']}%");
            });
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Get reports by type
     */
    public function getByType(ReportType $type): Collection
    {
        return $this->model->where('type', $type)
            ->where('status', ReportStatus::PUBLISHED)
            ->get();
    }

    /**
     * Get templates
     */
    public function getTemplates(): Collection
    {
        return $this->model->where('is_template', true)
            ->where('status', ReportStatus::PUBLISHED)
            ->get();
    }

    /**
     * Get user's reports
     */
    public function getUserReports(int $userId): Collection
    {
        return $this->model->where('user_id', $userId)->get();
    }

    /**
     * Update report
     */
    public function updateReport(Report $report, array $data): bool
    {
        return $report->update($data);
    }

    /**
     * Delete report
     */
    public function deleteReport(Report $report): bool
    {
        return $report->delete();
    }

    /**
     * Publish report
     */
    public function publish(Report $report): void
    {
        $report->publish();
    }

    /**
     * Archive report
     */
    public function archive(Report $report): void
    {
        $report->archive();
    }
}
