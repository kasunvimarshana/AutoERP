<?php

namespace App\Modules\Reporting\Repositories;

use App\Modules\Reporting\Models\Dashboard;
use App\Repositories\BaseRepository;

/**
 * Dashboard Repository
 *
 * Handles data access for dashboards.
 */
class DashboardRepository extends BaseRepository
{
    /**
     * Specify Model class name
     */
    protected function model(): string
    {
        return Dashboard::class;
    }

    /**
     * Get dashboards by user
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByUser(int $userId)
    {
        return $this->model->where('user_id', $userId)->get();
    }

    /**
     * Get default dashboard for user
     */
    public function getDefaultDashboard(int $userId): ?Dashboard
    {
        return $this->model
            ->where('user_id', $userId)
            ->where('is_default', true)
            ->first();
    }

    /**
     * Get dashboard with widgets
     */
    public function getWithWidgets(int $id): Dashboard
    {
        return $this->model->with('widgets')->findOrFail($id);
    }

    /**
     * Set dashboard as default
     */
    public function setAsDefault(int $dashboardId, int $userId): bool
    {
        $this->beginTransaction();

        try {
            // Unset all other dashboards as default
            $this->model
                ->where('user_id', $userId)
                ->update(['is_default' => false]);

            // Set the specified dashboard as default
            $dashboard = $this->findOrFail($dashboardId);
            $dashboard->is_default = true;
            $dashboard->save();

            $this->commit();

            return true;
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }
    }
}
