<?php

declare(strict_types=1);

namespace Modules\Reporting\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Modules\Core\Repositories\BaseRepository;
use Modules\Reporting\Models\DashboardWidget;

class WidgetRepository extends BaseRepository
{
    /**
     * Get the model class name.
     */
    protected function getModelClass(): string
    {
        return DashboardWidget::class;
    }

    /**
     * Find widget by ID
     */
    public function findById(int $id): ?DashboardWidget
    {
        return $this->find($id);
    }

    /**
     * Get dashboard widgets
     */
    public function getDashboardWidgets(int $dashboardId): Collection
    {
        return $this->model->where('dashboard_id', $dashboardId)
            ->orderBy('order')
            ->get();
    }

    /**
     * Update widget
     */
    public function updateWidget(DashboardWidget $widget, array $data): bool
    {
        return $widget->update($data);
    }

    /**
     * Delete widget
     */
    public function deleteWidget(DashboardWidget $widget): bool
    {
        return $widget->delete();
    }

    /**
     * Update widget order
     */
    public function updateOrder(DashboardWidget $widget, int $order): void
    {
        $widget->updateOrder($order);
    }

    /**
     * Update widget position
     */
    public function updatePosition(DashboardWidget $widget, int $x, int $y): void
    {
        $widget->updatePosition($x, $y);
    }

    /**
     * Reorder widgets
     */
    public function reorderWidgets(int $dashboardId, array $orderMap): void
    {
        foreach ($orderMap as $widgetId => $order) {
            $this->model->where('id', $widgetId)
                ->where('dashboard_id', $dashboardId)
                ->update(['order' => $order]);
        }
    }
}
