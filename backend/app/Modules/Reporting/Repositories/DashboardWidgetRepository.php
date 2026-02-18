<?php

namespace App\Modules\Reporting\Repositories;

use App\Modules\Reporting\Models\DashboardWidget;
use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;

/**
 * DashboardWidgetRepository
 *
 * Repository for managing dashboard widget data access.
 */
class DashboardWidgetRepository extends BaseRepository
{
    /**
     * Specify Model class name
     */
    protected function model(): string
    {
        return DashboardWidget::class;
    }

    /**
     * Get widgets by dashboard ID
     */
    public function getByDashboard(int $dashboardId): Collection
    {
        return $this->query()
            ->where('dashboard_id', $dashboardId)
            ->orderBy('position_y')
            ->orderBy('position_x')
            ->get();
    }

    /**
     * Get widget by dashboard and position
     */
    public function getByPosition(int $dashboardId, int $positionX, int $positionY): ?DashboardWidget
    {
        return $this->query()
            ->where('dashboard_id', $dashboardId)
            ->where('position_x', $positionX)
            ->where('position_y', $positionY)
            ->first();
    }

    /**
     * Reorder widgets for a dashboard
     *
     * @param  array  $widgetPositions  [['id' => 1, 'position_x' => 0, 'position_y' => 0], ...]
     */
    public function reorderWidgets(int $dashboardId, array $widgetPositions): bool
    {
        try {
            $this->beginTransaction();

            foreach ($widgetPositions as $position) {
                $this->update($position['id'], [
                    'position_x' => $position['position_x'],
                    'position_y' => $position['position_y'],
                ]);
            }

            $this->commit();

            return true;
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }
    }

    /**
     * Duplicate widget to another dashboard
     */
    public function duplicateWidget(int $widgetId, int $targetDashboardId): DashboardWidget
    {
        $widget = $this->findOrFail($widgetId);

        return $this->create([
            'dashboard_id' => $targetDashboardId,
            'widget_type' => $widget->widget_type,
            'title' => $widget->title,
            'config' => $widget->config,
            'position_x' => $widget->position_x,
            'position_y' => $widget->position_y,
            'width' => $widget->width,
            'height' => $widget->height,
        ]);
    }
}
