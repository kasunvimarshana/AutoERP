<?php

namespace App\Modules\Reporting\Http\Controllers;

use App\Http\Controllers\BaseController;
use App\Modules\Reporting\DTOs\AddWidgetDTO;
use App\Modules\Reporting\DTOs\CreateDashboardDTO;
use App\Modules\Reporting\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Dashboard Controller
 *
 * Handles HTTP requests for dashboard management.
 */
class DashboardController extends BaseController
{
    public function __construct(
        protected DashboardService $dashboardService,
    ) {}

    /**
     * Get user dashboards
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $userId = $request->user_id ?? auth()->id();
            $dashboards = $this->dashboardService->getUserDashboards($userId);

            return $this->successResponse($dashboards, 'Dashboards retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), null, 500);
        }
    }

    /**
     * Get default dashboard
     */
    public function getDefault(): JsonResponse
    {
        try {
            $dashboard = $this->dashboardService->getDefaultDashboard(auth()->id());

            if (! $dashboard) {
                return $this->notFoundResponse('No default dashboard found');
            }

            return $this->successResponse($dashboard, 'Default dashboard retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), null, 500);
        }
    }

    /**
     * Get a specific dashboard with widgets
     */
    public function show(int $id): JsonResponse
    {
        try {
            $dashboard = $this->dashboardService->getDashboardWithWidgets($id);

            return $this->successResponse($dashboard, 'Dashboard retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), null, 404);
        }
    }

    /**
     * Create a new dashboard
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'layout_config' => 'nullable|array',
                'is_default' => 'boolean',
            ]);

            $dto = CreateDashboardDTO::fromArray($validated);
            $dashboard = $this->dashboardService->createDashboard($dto);

            return $this->createdResponse($dashboard, 'Dashboard created successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), null, 500);
        }
    }

    /**
     * Update a dashboard
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'description' => 'nullable|string',
                'layout_config' => 'nullable|array',
                'is_default' => 'boolean',
            ]);

            $this->dashboardService->updateDashboard($id, $validated);
            $dashboard = $this->dashboardService->getDashboardWithWidgets($id);

            return $this->successResponse($dashboard, 'Dashboard updated successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), null, 500);
        }
    }

    /**
     * Delete a dashboard
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->dashboardService->deleteDashboard($id);

            return $this->successResponse(null, 'Dashboard deleted successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), null, 500);
        }
    }

    /**
     * Set dashboard as default
     */
    public function setAsDefault(int $id): JsonResponse
    {
        try {
            $this->dashboardService->setAsDefault($id, auth()->id());

            return $this->successResponse(null, 'Dashboard set as default successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), null, 500);
        }
    }

    /**
     * Add widget to dashboard
     */
    public function addWidget(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'dashboard_id' => 'required|integer|exists:dashboards,id',
                'widget_type' => 'required|string',
                'title' => 'required|string|max:255',
                'config' => 'required|array',
                'position_x' => 'integer|min:0',
                'position_y' => 'integer|min:0',
                'width' => 'integer|min:1',
                'height' => 'integer|min:1',
            ]);

            $dto = AddWidgetDTO::fromArray($validated);
            $widget = $this->dashboardService->addWidget($dto);

            return $this->createdResponse($widget, 'Widget added successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), null, 500);
        }
    }

    /**
     * Update widget
     */
    public function updateWidget(Request $request, int $widgetId): JsonResponse
    {
        try {
            $validated = $request->validate([
                'title' => 'sometimes|string|max:255',
                'config' => 'sometimes|array',
                'position_x' => 'integer|min:0',
                'position_y' => 'integer|min:0',
                'width' => 'integer|min:1',
                'height' => 'integer|min:1',
            ]);

            $this->dashboardService->updateWidget($widgetId, $validated);

            return $this->successResponse(null, 'Widget updated successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), null, 500);
        }
    }

    /**
     * Remove widget from dashboard
     */
    public function removeWidget(int $widgetId): JsonResponse
    {
        try {
            $this->dashboardService->removeWidget($widgetId);

            return $this->successResponse(null, 'Widget removed successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), null, 500);
        }
    }

    /**
     * Reorder widgets
     */
    public function reorderWidgets(Request $request, int $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'widgets' => 'required|array',
                'widgets.*.widget_id' => 'required|integer|exists:dashboard_widgets,id',
                'widgets.*.position_x' => 'required|integer|min:0',
                'widgets.*.position_y' => 'required|integer|min:0',
            ]);

            $this->dashboardService->reorderWidgets($id, $validated['widgets']);

            return $this->successResponse(null, 'Widgets reordered successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), null, 500);
        }
    }
}
