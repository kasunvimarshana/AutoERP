<?php

declare(strict_types=1);

namespace Modules\Reporting\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Core\Repositories\BaseRepository;
use Modules\Reporting\Models\SavedReport;

class SavedReportRepository extends BaseRepository
{
    /**
     * Get the model class name.
     */
    protected function getModelClass(): string
    {
        return SavedReport::class;
    }

    /**
     * Find saved report by ID
     */
    public function findById(int $id): ?SavedReport
    {
        return $this->find($id);
    }

    /**
     * Get all saved reports with optional filters
     */
    public function getAll(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->query()->with('report');

        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (isset($filters['report_id'])) {
            $query->where('report_id', $filters['report_id']);
        }

        if (isset($filters['is_favorite'])) {
            $query->where('is_favorite', $filters['is_favorite']);
        }

        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', "%{$filters['search']}%")
                    ->orWhere('description', 'like', "%{$filters['search']}%");
            });
        }

        return $query->orderBy('last_accessed_at', 'desc')->paginate($perPage);
    }

    /**
     * Get user's saved reports
     */
    public function getUserSavedReports(int $userId): Collection
    {
        return $this->model->where('user_id', $userId)
            ->with('report')
            ->orderBy('last_accessed_at', 'desc')
            ->get();
    }

    /**
     * Get user's favorite reports
     */
    public function getUserFavorites(int $userId): Collection
    {
        return $this->model->where('user_id', $userId)
            ->where('is_favorite', true)
            ->with('report')
            ->get();
    }

    /**
     * Update saved report
     */
    public function updateSavedReport(SavedReport $savedReport, array $data): bool
    {
        return $savedReport->update($data);
    }

    /**
     * Delete saved report
     */
    public function deleteSavedReport(SavedReport $savedReport): bool
    {
        return $savedReport->delete();
    }

    /**
     * Mark as favorite
     */
    public function markAsFavorite(SavedReport $savedReport): void
    {
        $savedReport->markAsFavorite();
    }

    /**
     * Remove from favorites
     */
    public function removeFromFavorites(SavedReport $savedReport): void
    {
        $savedReport->removeFromFavorites();
    }

    /**
     * Update last accessed
     */
    public function updateLastAccessed(SavedReport $savedReport): void
    {
        $savedReport->updateLastAccessed();
    }
}
