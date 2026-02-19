<?php

declare(strict_types=1);

namespace Modules\Billing\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Billing\Http\Requests\StorePlanRequest;
use Modules\Billing\Http\Requests\UpdatePlanRequest;
use Modules\Billing\Http\Resources\PlanResource;
use Modules\Billing\Models\Plan;
use Modules\Billing\Repositories\PlanRepository;
use Modules\Core\Http\Responses\ApiResponse;

class PlanController extends Controller
{
    public function __construct(
        private PlanRepository $planRepository
    ) {}

    /**
     * Display a listing of plans.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Plan::class);

        $filters = [
            'type' => $request->type,
            'is_active' => $request->boolean('is_active'),
            'is_public' => $request->boolean('is_public'),
            'search' => $request->search,
        ];

        $perPage = $request->get('per_page', 15);
        $plans = $this->planRepository->searchPlans(
            array_filter($filters, fn ($value) => ! is_null($value)),
            $perPage
        );

        return ApiResponse::paginated(
            $plans->setCollection(
                $plans->getCollection()->map(fn ($plan) => new PlanResource($plan))
            ),
            'Plans retrieved successfully'
        );
    }

    /**
     * Get public plans (for customers to view).
     */
    public function publicPlans(): JsonResponse
    {
        $plans = $this->planRepository->getPublicPlans();

        return ApiResponse::success(
            PlanResource::collection($plans),
            'Public plans retrieved successfully'
        );
    }

    /**
     * Store a newly created plan.
     */
    public function store(StorePlanRequest $request): JsonResponse
    {
        $this->authorize('create', Plan::class);

        $plan = $this->planRepository->create($request->validated());

        return ApiResponse::created(
            new PlanResource($plan),
            'Plan created successfully'
        );
    }

    /**
     * Display the specified plan.
     */
    public function show(int $id): JsonResponse
    {
        $plan = $this->planRepository->findOrFail($id);
        $this->authorize('view', $plan);

        return ApiResponse::success(
            new PlanResource($plan),
            'Plan retrieved successfully'
        );
    }

    /**
     * Update the specified plan.
     */
    public function update(UpdatePlanRequest $request, int $id): JsonResponse
    {
        $plan = $this->planRepository->findOrFail($id);
        $this->authorize('update', $plan);

        $plan = $this->planRepository->update($id, $request->validated());

        return ApiResponse::success(
            new PlanResource($plan),
            'Plan updated successfully'
        );
    }

    /**
     * Remove the specified plan.
     */
    public function destroy(int $id): JsonResponse
    {
        $plan = $this->planRepository->findOrFail($id);
        $this->authorize('delete', $plan);

        $this->planRepository->delete($id);

        return ApiResponse::success(
            null,
            'Plan deleted successfully'
        );
    }

    /**
     * Activate a plan.
     */
    public function activate(int $id): JsonResponse
    {
        $plan = $this->planRepository->findOrFail($id);
        $this->authorize('update', $plan);

        $plan->update(['is_active' => true]);

        return ApiResponse::success(
            new PlanResource($plan->fresh()),
            'Plan activated successfully'
        );
    }

    /**
     * Deactivate a plan.
     */
    public function deactivate(int $id): JsonResponse
    {
        $plan = $this->planRepository->findOrFail($id);
        $this->authorize('update', $plan);

        $plan->update(['is_active' => false]);

        return ApiResponse::success(
            new PlanResource($plan->fresh()),
            'Plan deactivated successfully'
        );
    }
}
