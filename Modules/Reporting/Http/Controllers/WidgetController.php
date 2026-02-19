<?php

declare(strict_types=1);

namespace Modules\Reporting\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Core\Http\Responses\ApiResponse;
use Modules\Reporting\Http\Requests\StoreWidgetRequest;
use Modules\Reporting\Http\Requests\UpdateWidgetRequest;
use Modules\Reporting\Http\Resources\WidgetResource;
use Modules\Reporting\Models\DashboardWidget;
use Modules\Reporting\Repositories\WidgetRepository;
use Modules\Reporting\Services\DashboardService;

class WidgetController extends Controller
{
    public function __construct(
        private WidgetRepository $widgetRepository,
        private DashboardService $dashboardService
    ) {}

    /**
     * Store a newly created widget
     */
    public function store(StoreWidgetRequest $request): JsonResponse
    {
        $widget = DB::transaction(function () use ($request) {
            $data = $request->validated();

            return $this->dashboardService->addWidget($data['dashboard_id'], $data);
        });

        return ApiResponse::created(
            new WidgetResource($widget),
            'Widget created successfully'
        );
    }

    /**
     * Display the specified widget
     */
    public function show(DashboardWidget $widget): JsonResponse
    {
        $this->authorize('view', $widget);

        $widget->load(['dashboard', 'report']);

        return ApiResponse::success(
            new WidgetResource($widget),
            'Widget retrieved successfully'
        );
    }

    /**
     * Update the specified widget
     */
    public function update(UpdateWidgetRequest $request, DashboardWidget $widget): JsonResponse
    {
        $this->authorize('update', $widget);

        DB::transaction(function () use ($request, $widget) {
            $this->dashboardService->updateWidget($widget->id, $request->validated());
        });

        return ApiResponse::success(
            new WidgetResource($widget->fresh()),
            'Widget updated successfully'
        );
    }

    /**
     * Remove the specified widget
     */
    public function destroy(DashboardWidget $widget): JsonResponse
    {
        $this->authorize('delete', $widget);

        DB::transaction(function () use ($widget) {
            $this->dashboardService->removeWidget($widget->id);
        });

        return ApiResponse::success(
            null,
            'Widget deleted successfully'
        );
    }

    /**
     * Reorder widgets on dashboard
     */
    public function reorder(Request $request, int $dashboardId): JsonResponse
    {
        $request->validate([
            'widgets' => 'required|array',
            'widgets.*.id' => 'required|integer|exists:dashboard_widgets,id',
            'widgets.*.order' => 'required|integer|min:0',
        ]);

        DB::transaction(function () use ($request, $dashboardId) {
            $orderMap = collect($request->widgets)->pluck('order', 'id')->toArray();
            $this->dashboardService->reorderWidgets($dashboardId, $orderMap);
        });

        return ApiResponse::success(
            null,
            'Widgets reordered successfully'
        );
    }

    /**
     * Update widget position
     */
    public function updatePosition(Request $request, DashboardWidget $widget): JsonResponse
    {
        $this->authorize('update', $widget);

        $request->validate([
            'position_x' => 'required|integer|min:0',
            'position_y' => 'required|integer|min:0',
        ]);

        DB::transaction(function () use ($request, $widget) {
            $this->widgetRepository->updatePosition(
                $widget,
                $request->position_x,
                $request->position_y
            );
        });

        return ApiResponse::success(
            new WidgetResource($widget->fresh()),
            'Widget position updated successfully'
        );
    }
}
