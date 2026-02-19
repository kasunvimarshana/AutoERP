<?php

declare(strict_types=1);

namespace Modules\Organization\Http\Controllers;

use App\Core\Traits\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Organization\Requests\StoreBranchRequest;
use Modules\Organization\Requests\UpdateBranchRequest;
use Modules\Organization\Resources\BranchResource;
use Modules\Organization\Services\BranchService;

/**
 * Branch Controller
 *
 * Handles HTTP requests for Branch operations
 * Follows Controller → Service → Repository pattern
 */
class BranchController extends Controller
{
    use ApiResponse;

    /**
     * BranchController constructor
     */
    public function __construct(
        private readonly BranchService $branchService
    ) {}

    /**
     * Display a listing of branches
     */
    public function index(Request $request): JsonResponse
    {
        $filters = [
            'paginate' => $request->boolean('paginate', true),
            'per_page' => $request->integer('per_page', 15),
        ];

        $branches = $this->branchService->getAll($filters);

        return $this->successResponse(
            BranchResource::collection($branches),
            __('organization::messages.branches_retrieved')
        );
    }

    /**
     * Store a newly created branch
     */
    public function store(StoreBranchRequest $request): JsonResponse
    {
        $branch = $this->branchService->create($request->validated());

        return $this->createdResponse(
            new BranchResource($branch),
            __('organization::messages.branch_created')
        );
    }

    /**
     * Display the specified branch
     */
    public function show(int $id): JsonResponse
    {
        $branch = $this->branchService->getById($id);

        return $this->successResponse(
            new BranchResource($branch),
            __('organization::messages.branch_retrieved')
        );
    }

    /**
     * Update the specified branch
     */
    public function update(UpdateBranchRequest $request, int $id): JsonResponse
    {
        $branch = $this->branchService->update($id, $request->validated());

        return $this->successResponse(
            new BranchResource($branch),
            __('organization::messages.branch_updated')
        );
    }

    /**
     * Remove the specified branch
     */
    public function destroy(int $id): JsonResponse
    {
        $this->branchService->delete($id);

        return $this->successResponse(
            null,
            __('organization::messages.branch_deleted')
        );
    }

    /**
     * Get branches by organization
     */
    public function byOrganization(int $organizationId): JsonResponse
    {
        $branches = $this->branchService->getByOrganization($organizationId);

        return $this->successResponse(
            BranchResource::collection($branches),
            __('organization::messages.branches_retrieved')
        );
    }

    /**
     * Search branches
     */
    public function search(Request $request): JsonResponse
    {
        $query = $request->input('query', '');
        $branches = $this->branchService->search($query);

        return $this->successResponse(
            BranchResource::collection($branches),
            __('organization::messages.search_results')
        );
    }

    /**
     * Get nearby branches
     */
    public function nearby(Request $request): JsonResponse
    {
        $latitude = $request->input('latitude');
        $longitude = $request->input('longitude');
        $radius = $request->input('radius', 10);

        $branches = $this->branchService->getNearby(
            (float) $latitude,
            (float) $longitude,
            (float) $radius
        );

        return $this->successResponse(
            BranchResource::collection($branches),
            __('organization::messages.nearby_branches_retrieved')
        );
    }

    /**
     * Check branch capacity
     */
    public function checkCapacity(int $id, Request $request): JsonResponse
    {
        $currentVehicles = $request->integer('current_vehicles', 0);
        $capacity = $this->branchService->checkCapacity($id, $currentVehicles);

        return $this->successResponse(
            $capacity,
            __('organization::messages.capacity_checked')
        );
    }
}
