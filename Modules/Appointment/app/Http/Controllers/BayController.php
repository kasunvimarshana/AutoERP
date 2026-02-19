<?php

declare(strict_types=1);

namespace Modules\Appointment\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Appointment\Requests\StoreBayRequest;
use Modules\Appointment\Requests\UpdateBayRequest;
use Modules\Appointment\Resources\BayResource;
use Modules\Appointment\Services\BayService;

/**
 * Bay Controller
 *
 * Handles HTTP requests for Bay operations
 * Follows Controller → Service → Repository pattern
 */
class BayController extends Controller
{
    /**
     * BayController constructor
     */
    public function __construct(
        private readonly BayService $bayService
    ) {}

    /**
     * Display a listing of bays
     */
    public function index(Request $request): JsonResponse
    {
        $filters = [
            'paginate' => $request->boolean('paginate', true),
            'per_page' => $request->integer('per_page', 15),
        ];

        $bays = $this->bayService->getAll($filters);

        return $this->successResponse(
            BayResource::collection($bays),
            __('appointment::messages.bays_retrieved')
        );
    }

    /**
     * Store a newly created bay
     */
    public function store(StoreBayRequest $request): JsonResponse
    {
        $bay = $this->bayService->create($request->validated());

        return $this->createdResponse(
            new BayResource($bay),
            __('appointment::messages.bay_created')
        );
    }

    /**
     * Display the specified bay
     */
    public function show(int $id): JsonResponse
    {
        $bay = $this->bayService->getWithSchedules($id);

        return $this->successResponse(
            new BayResource($bay),
            __('appointment::messages.bay_retrieved')
        );
    }

    /**
     * Update the specified bay
     */
    public function update(UpdateBayRequest $request, int $id): JsonResponse
    {
        $bay = $this->bayService->update($id, $request->validated());

        return $this->successResponse(
            new BayResource($bay),
            __('appointment::messages.bay_updated')
        );
    }

    /**
     * Remove the specified bay
     */
    public function destroy(int $id): JsonResponse
    {
        $this->bayService->delete($id);

        return $this->successResponse(
            null,
            __('appointment::messages.bay_deleted')
        );
    }

    /**
     * Get available bays for a branch
     */
    public function availableForBranch(Request $request): JsonResponse
    {
        $branchId = $request->integer('branch_id');
        $bays = $this->bayService->getAvailableForBranch($branchId);

        return $this->successResponse(
            BayResource::collection($bays),
            __('appointment::messages.bays_retrieved')
        );
    }

    /**
     * Get available bays for time range
     */
    public function availableForTimeRange(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'start_time' => ['required', 'date'],
            'end_time' => ['required', 'date', 'after:start_time'],
        ]);

        $bays = $this->bayService->getAvailableForTimeRange(
            $validated['branch_id'],
            $validated['start_time'],
            $validated['end_time']
        );

        return $this->successResponse(
            BayResource::collection($bays),
            __('appointment::messages.bays_retrieved')
        );
    }
}
