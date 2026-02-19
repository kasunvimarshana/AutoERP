<?php

declare(strict_types=1);

namespace Modules\Reporting\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Core\Repositories\BaseRepository;
use Modules\Reporting\Models\Dashboard;

class DashboardRepository extends BaseRepository
{
    /**
     * Get the model class name.
     */
    protected function getModelClass(): string
    {
        return Dashboard::class;
    }

    /**
     * Find dashboard by ID
     */
    public function findById(int $id): ?Dashboard
    {
        return $this->model->with('widgets')->find($id);
    }

    /**
     * Get all dashboards with optional filters
     */
    public function getAll(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->query()->with('widgets');

        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (isset($filters['is_default'])) {
            $query->where('is_default', $filters['is_default']);
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
     * Get user's dashboards
     */
    public function getUserDashboards(int $userId): Collection
    {
        return $this->model->where('user_id', $userId)
            ->with('widgets')
            ->get();
    }

    /**
     * Get user's default dashboard
     */
    public function getUserDefaultDashboard(int $userId): ?Dashboard
    {
        return $this->model->where('user_id', $userId)
            ->where('is_default', true)
            ->with('widgets')
            ->first();
    }

    /**
     * Update dashboard
     */
    public function updateDashboard(Dashboard $dashboard, array $data): bool
    {
        return $dashboard->update($data);
    }

    /**
     * Delete dashboard
     */
    public function deleteDashboard(Dashboard $dashboard): bool
    {
        return $dashboard->delete();
    }

    /**
     * Set as default
     */
    public function setAsDefault(Dashboard $dashboard): void
    {
        $dashboard->setAsDefault();
    }
}
