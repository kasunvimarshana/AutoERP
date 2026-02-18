<?php

namespace App\Modules\Reporting\Services;

use App\Modules\Reporting\DTOs\AddWidgetDTO;
use App\Modules\Reporting\DTOs\CreateDashboardDTO;
use App\Modules\Reporting\Events\DashboardUpdated;
use App\Modules\Reporting\Models\Dashboard;
use App\Modules\Reporting\Models\DashboardWidget;
use App\Modules\Reporting\Repositories\DashboardRepository;
use App\Services\BaseService;

/**
 * Dashboard Service
 *
 * Handles dashboard CRUD and widget management.
 */
class DashboardService extends BaseService
{
    public function __construct(
        protected DashboardRepository $dashboardRepository,
    ) {}

    /**
     * Create a new dashboard
     */
    public function createDashboard(CreateDashboardDTO $dto): Dashboard
    {
        $data = $dto->toArray();
        $data['tenant_id'] = auth()->user()->tenant_id;
        $data['user_id'] = $dto->userId ?? auth()->id();

        return $this->dashboardRepository->create($data);
    }

    /**
     * Update a dashboard
     */
    public function updateDashboard(int $dashboardId, array $data): bool
    {
        $result = $this->dashboardRepository->update($dashboardId, $data);

        if ($result) {
            $dashboard = $this->dashboardRepository->findOrFail($dashboardId);
            event(new DashboardUpdated($dashboard));
        }

        return $result;
    }

    /**
     * Delete a dashboard
     */
    public function deleteDashboard(int $dashboardId): bool
    {
        return $this->dashboardRepository->delete($dashboardId);
    }

    /**
     * Add widget to dashboard
     */
    public function addWidget(AddWidgetDTO $dto): DashboardWidget
    {
        $data = $dto->toArray();
        $data['tenant_id'] = auth()->user()->tenant_id;

        $widget = DashboardWidget::create($data);

        $dashboard = $this->dashboardRepository->findOrFail($dto->dashboardId);
        event(new DashboardUpdated($dashboard));

        $this->logInfo('Widget added to dashboard', [
            'dashboard_id' => $dto->dashboardId,
            'widget_type' => $dto->widgetType->value,
        ]);

        return $widget;
    }

    /**
     * Update widget
     */
    public function updateWidget(int $widgetId, array $data): bool
    {
        $widget = DashboardWidget::findOrFail($widgetId);
        $result = $widget->update($data);

        if ($result) {
            event(new DashboardUpdated($widget->dashboard));
        }

        return $result;
    }

    /**
     * Remove widget from dashboard
     */
    public function removeWidget(int $widgetId): bool
    {
        $widget = DashboardWidget::findOrFail($widgetId);
        $dashboard = $widget->dashboard;

        $result = $widget->delete();

        if ($result) {
            event(new DashboardUpdated($dashboard));
        }

        return $result;
    }

    /**
     * Get dashboard with widgets
     */
    public function getDashboardWithWidgets(int $dashboardId): Dashboard
    {
        return $this->dashboardRepository->getWithWidgets($dashboardId);
    }

    /**
     * Get user dashboards
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getUserDashboards(int $userId)
    {
        return $this->dashboardRepository->getByUser($userId);
    }

    /**
     * Get default dashboard for user
     */
    public function getDefaultDashboard(int $userId): ?Dashboard
    {
        return $this->dashboardRepository->getDefaultDashboard($userId);
    }

    /**
     * Set dashboard as default
     */
    public function setAsDefault(int $dashboardId, int $userId): bool
    {
        return $this->dashboardRepository->setAsDefault($dashboardId, $userId);
    }

    /**
     * Reorder widgets
     */
    public function reorderWidgets(int $dashboardId, array $widgetsOrder): bool
    {
        return $this->transaction(function () use ($dashboardId, $widgetsOrder) {
            foreach ($widgetsOrder as $order) {
                DashboardWidget::where('id', $order['widget_id'])
                    ->where('dashboard_id', $dashboardId)
                    ->update([
                        'position_x' => $order['position_x'],
                        'position_y' => $order['position_y'],
                    ]);
            }

            $dashboard = $this->dashboardRepository->findOrFail($dashboardId);
            event(new DashboardUpdated($dashboard));

            return true;
        });
    }
}
