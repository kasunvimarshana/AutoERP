<?php

namespace App\Modules\ReportingManagement\Repositories;

use App\Core\Base\BaseRepository;
use App\Modules\ReportingManagement\Models\KpiMetric;

class KpiMetricRepository extends BaseRepository
{
    public function __construct(KpiMetric $model)
    {
        parent::__construct($model);
    }

    /**
     * Search KPI metrics by various criteria
     */
    public function search(array $criteria)
    {
        $query = $this->model->query();

        if (!empty($criteria['search'])) {
            $search = $criteria['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('metric_code', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if (!empty($criteria['metric_type'])) {
            $query->where('metric_type', $criteria['metric_type']);
        }

        if (!empty($criteria['category'])) {
            $query->where('category', $criteria['category']);
        }

        if (!empty($criteria['is_active'])) {
            $query->where('is_active', $criteria['is_active']);
        }

        if (!empty($criteria['tenant_id'])) {
            $query->where('tenant_id', $criteria['tenant_id']);
        }

        return $query->orderBy('name')->paginate($criteria['per_page'] ?? 15);
    }

    /**
     * Find KPI metric by metric code
     */
    public function findByMetricCode(string $metricCode): ?KpiMetric
    {
        return $this->model->where('metric_code', $metricCode)->first();
    }

    /**
     * Get metrics by type
     */
    public function getByType(string $type)
    {
        return $this->model->where('metric_type', $type)->get();
    }

    /**
     * Get metrics by category
     */
    public function getByCategory(string $category)
    {
        return $this->model->where('category', $category)->get();
    }

    /**
     * Get active metrics
     */
    public function getActive()
    {
        return $this->model->where('is_active', true)->get();
    }

    /**
     * Get metrics with targets
     */
    public function getWithTargets()
    {
        return $this->model->whereNotNull('target_value')->get();
    }

    /**
     * Get metrics by date range
     */
    public function getByDateRange($startDate, $endDate)
    {
        return $this->model->whereBetween('measurement_date', [$startDate, $endDate])
            ->orderBy('measurement_date', 'desc')
            ->get();
    }

    /**
     * Get metrics above target
     */
    public function getAboveTarget()
    {
        return $this->model->whereNotNull('target_value')
            ->whereColumn('current_value', '>', 'target_value')
            ->get();
    }

    /**
     * Get metrics below target
     */
    public function getBelowTarget()
    {
        return $this->model->whereNotNull('target_value')
            ->whereColumn('current_value', '<', 'target_value')
            ->get();
    }

    /**
     * Get trending metrics
     */
    public function getTrending()
    {
        return $this->model->where('is_trending', true)
            ->where('is_active', true)
            ->get();
    }

    /**
     * Get dashboard metrics
     */
    public function getDashboardMetrics()
    {
        return $this->model->where('show_on_dashboard', true)
            ->where('is_active', true)
            ->orderBy('display_order')
            ->get();
    }
}
