<?php

declare(strict_types=1);

namespace Modules\Reporting\Services;

use Modules\Reporting\Models\Dashboard;
use Modules\Reporting\Repositories\DashboardRepository;
use Modules\Reporting\Repositories\WidgetRepository;

/**
 * DashboardService
 *
 * Manages dashboards and their widgets
 */
class DashboardService
{
    public function __construct(
        private DashboardRepository $dashboardRepository,
        private WidgetRepository $widgetRepository,
        private ReportBuilderService $reportBuilderService
    ) {}

    /**
     * Create new dashboard
     */
    public function createDashboard(array $data): Dashboard
    {
        return $this->dashboardRepository->create($data);
    }

    /**
     * Update dashboard
     */
    public function updateDashboard(Dashboard $dashboard, array $data): bool
    {
        return $this->dashboardRepository->updateDashboard($dashboard, $data);
    }

    /**
     * Delete dashboard
     */
    public function deleteDashboard(Dashboard $dashboard): bool
    {
        return $this->dashboardRepository->deleteDashboard($dashboard);
    }

    /**
     * Add widget to dashboard
     */
    public function addWidget(int $dashboardId, array $widgetConfig): \Modules\Reporting\Models\DashboardWidget
    {
        $widgetConfig['dashboard_id'] = $dashboardId;
        $widgetConfig['tenant_id'] = auth()->user()->tenant_id;
        $widgetConfig['organization_id'] = auth()->user()->organization_id;

        // Set order to last position
        $maxOrder = $this->widgetRepository->getDashboardWidgets($dashboardId)->max('order') ?? 0;
        $widgetConfig['order'] = $maxOrder + 1;

        return $this->widgetRepository->create($widgetConfig);
    }

    /**
     * Remove widget from dashboard
     */
    public function removeWidget(int $widgetId): bool
    {
        $widget = $this->widgetRepository->findById($widgetId);

        if (! $widget) {
            return false;
        }

        return $this->widgetRepository->deleteWidget($widget);
    }

    /**
     * Update widget configuration
     */
    public function updateWidget(int $widgetId, array $config): bool
    {
        $widget = $this->widgetRepository->findById($widgetId);

        if (! $widget) {
            return false;
        }

        return $this->widgetRepository->updateWidget($widget, $config);
    }

    /**
     * Reorder widgets on dashboard
     */
    public function reorderWidgets(int $dashboardId, array $orderMap): void
    {
        $this->widgetRepository->reorderWidgets($dashboardId, $orderMap);
    }

    /**
     * Render dashboard with all widget data
     */
    public function renderDashboard(int $dashboardId): array
    {
        $dashboard = $this->dashboardRepository->findById($dashboardId);

        if (! $dashboard) {
            throw new \InvalidArgumentException('Dashboard not found');
        }

        $widgets = $this->widgetRepository->getDashboardWidgets($dashboardId);
        $renderedWidgets = [];

        foreach ($widgets as $widget) {
            $renderedWidgets[] = $this->renderWidget($widget);
        }

        return [
            'dashboard' => $dashboard,
            'widgets' => $renderedWidgets,
        ];
    }

    /**
     * Render individual widget with data
     */
    private function renderWidget(\Modules\Reporting\Models\DashboardWidget $widget): array
    {
        $widgetData = [
            'id' => $widget->id,
            'type' => $widget->type,
            'chart_type' => $widget->chart_type,
            'title' => $widget->title,
            'description' => $widget->description,
            'configuration' => $widget->configuration,
            'position' => [
                'x' => $widget->position_x,
                'y' => $widget->position_y,
            ],
            'size' => [
                'width' => $widget->width,
                'height' => $widget->height,
            ],
            'order' => $widget->order,
        ];

        // Load data if widget is based on a report
        if ($widget->report_id) {
            try {
                $reportResult = $this->reportBuilderService->execute(
                    $widget->report,
                    $widget->data_source['filters'] ?? []
                );
                $widgetData['data'] = $reportResult['data'];
                $widgetData['count'] = $reportResult['count'];
            } catch (\Exception $e) {
                $widgetData['error'] = $e->getMessage();
            }
        }

        return $widgetData;
    }

    /**
     * Set dashboard as default
     */
    public function setAsDefault(int $dashboardId): void
    {
        $dashboard = $this->dashboardRepository->findById($dashboardId);

        if (! $dashboard) {
            throw new \InvalidArgumentException('Dashboard not found');
        }

        $this->dashboardRepository->setAsDefault($dashboard);
    }

    /**
     * Get user's default dashboard
     */
    public function getUserDefaultDashboard(int $userId): ?Dashboard
    {
        return $this->dashboardRepository->getUserDefaultDashboard($userId);
    }

    /**
     * Clone dashboard
     */
    public function cloneDashboard(int $dashboardId, string $newName): Dashboard
    {
        $original = $this->dashboardRepository->findById($dashboardId);

        if (! $original) {
            throw new \InvalidArgumentException('Dashboard not found');
        }

        // Create new dashboard
        $newDashboard = $this->dashboardRepository->create([
            'tenant_id' => $original->tenant_id,
            'organization_id' => $original->organization_id,
            'user_id' => auth()->id(),
            'name' => $newName,
            'description' => $original->description,
            'layout' => $original->layout,
            'is_default' => false,
            'is_shared' => false,
            'metadata' => $original->metadata,
        ]);

        // Clone widgets
        foreach ($original->widgets as $widget) {
            $this->widgetRepository->create([
                'tenant_id' => $widget->tenant_id,
                'organization_id' => $widget->organization_id,
                'dashboard_id' => $newDashboard->id,
                'report_id' => $widget->report_id,
                'type' => $widget->type,
                'chart_type' => $widget->chart_type,
                'title' => $widget->title,
                'description' => $widget->description,
                'configuration' => $widget->configuration,
                'data_source' => $widget->data_source,
                'refresh_interval' => $widget->refresh_interval,
                'order' => $widget->order,
                'width' => $widget->width,
                'height' => $widget->height,
                'position_x' => $widget->position_x,
                'position_y' => $widget->position_y,
            ]);
        }

        return $newDashboard;
    }
}
