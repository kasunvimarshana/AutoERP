<?php

declare(strict_types=1);

namespace Modules\Reporting\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Core\Http\Responses\ApiResponse;
use Modules\Reporting\Events\DashboardCreated;
use Modules\Reporting\Http\Requests\StoreDashboardRequest;
use Modules\Reporting\Http\Requests\UpdateDashboardRequest;
use Modules\Reporting\Http\Resources\DashboardResource;
use Modules\Reporting\Models\Dashboard;
use Modules\Reporting\Repositories\DashboardRepository;
use Modules\Reporting\Services\DashboardService;

class DashboardController extends Controller
{
    public function __construct(
        private DashboardRepository $dashboardRepository,
        private DashboardService $dashboardService
    ) {}

    /**
     * Display a listing of dashboards
     */
    public function index(Request $request): JsonResponse
    {
        $filters = [
            'user_id' => $request->user_id ?? $request->user()->id,
            'is_default' => $request->boolean('is_default'),
            'is_shared' => $request->boolean('is_shared'),
            'search' => $request->search,
        ];

        $perPage = $request->get('per_page', 15);
        $dashboards = $this->dashboardRepository->getAll(array_filter($filters), $perPage);

        return ApiResponse::paginated(
            $dashboards->setCollection(
                $dashboards->getCollection()->map(fn ($dashboard) => new DashboardResource($dashboard))
            ),
            'Dashboards retrieved successfully'
        );
    }

    /**
     * Store a newly created dashboard
     */
    public function store(StoreDashboardRequest $request): JsonResponse
    {
        $dashboard = DB::transaction(function () use ($request) {
            $data = $request->validated();
            $data['tenant_id'] = $request->user()->tenant_id;
            $data['organization_id'] = $request->user()->organization_id;
            $data['user_id'] = $request->user()->id;

            return $this->dashboardService->createDashboard($data);
        });

        event(new DashboardCreated($dashboard));

        return ApiResponse::created(
            new DashboardResource($dashboard),
            'Dashboard created successfully'
        );
    }

    /**
     * Display the specified dashboard
     */
    public function show(Dashboard $dashboard): JsonResponse
    {
        $this->authorize('view', $dashboard);

        $dashboard->load('widgets');

        return ApiResponse::success(
            new DashboardResource($dashboard),
            'Dashboard retrieved successfully'
        );
    }

    /**
     * Update the specified dashboard
     */
    public function update(UpdateDashboardRequest $request, Dashboard $dashboard): JsonResponse
    {
        $this->authorize('update', $dashboard);

        DB::transaction(function () use ($request, $dashboard) {
            $this->dashboardService->updateDashboard($dashboard, $request->validated());
        });

        return ApiResponse::success(
            new DashboardResource($dashboard->fresh()),
            'Dashboard updated successfully'
        );
    }

    /**
     * Remove the specified dashboard
     */
    public function destroy(Dashboard $dashboard): JsonResponse
    {
        $this->authorize('delete', $dashboard);

        DB::transaction(function () use ($dashboard) {
            $this->dashboardService->deleteDashboard($dashboard);
        });

        return ApiResponse::success(
            null,
            'Dashboard deleted successfully'
        );
    }

    /**
     * Render dashboard with all widget data
     */
    public function render(Dashboard $dashboard): JsonResponse
    {
        $this->authorize('view', $dashboard);

        $rendered = $this->dashboardService->renderDashboard($dashboard->id);

        return ApiResponse::success(
            $rendered,
            'Dashboard rendered successfully'
        );
    }

    /**
     * Set dashboard as default
     */
    public function setDefault(Dashboard $dashboard): JsonResponse
    {
        $this->authorize('update', $dashboard);

        DB::transaction(function () use ($dashboard) {
            $this->dashboardService->setAsDefault($dashboard->id);
        });

        return ApiResponse::success(
            new DashboardResource($dashboard->fresh()),
            'Dashboard set as default successfully'
        );
    }

    /**
     * Get user's default dashboard
     */
    public function getDefault(Request $request): JsonResponse
    {
        $dashboard = $this->dashboardService->getUserDefaultDashboard($request->user()->id);

        if (! $dashboard) {
            return ApiResponse::error('No default dashboard found', 404);
        }

        return ApiResponse::success(
            new DashboardResource($dashboard),
            'Default dashboard retrieved successfully'
        );
    }

    /**
     * Clone dashboard
     */
    public function clone(Request $request, Dashboard $dashboard): JsonResponse
    {
        $this->authorize('view', $dashboard);

        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $cloned = DB::transaction(function () use ($request, $dashboard) {
            return $this->dashboardService->cloneDashboard($dashboard->id, $request->name);
        });

        return ApiResponse::created(
            new DashboardResource($cloned),
            'Dashboard cloned successfully'
        );
    }
}
