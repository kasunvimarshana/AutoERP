<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Manufacturing;

use App\Http\Controllers\BaseController;
use App\Modules\Manufacturing\Services\WorkOrderService;
use App\Http\Requests\Manufacturing\StoreWorkOrderRequest;
use App\Http\Requests\Manufacturing\UpdateWorkOrderRequest;
use App\Http\Requests\Manufacturing\CompleteProductionRequest;
use App\Http\Resources\Manufacturing\WorkOrderResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * WorkOrderController
 * 
 * Handles HTTP requests for Work Order operations
 */
class WorkOrderController extends BaseController
{
    protected WorkOrderService $workOrderService;

    /**
     * WorkOrderController constructor.
     *
     * @param WorkOrderService $workOrderService
     */
    public function __construct(WorkOrderService $workOrderService)
    {
        $this->workOrderService = $workOrderService;
    }

    /**
     * Display a listing of work orders.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $request->input('per_page', 15);
            
            $filters = $request->only([
                'search',
                'status',
                'priority',
                'product_id',
                'branch_id',
                'assigned_to',
                'start_date',
                'end_date'
            ]);

            if (!empty($filters)) {
                $workOrders = $this->workOrderService->search($filters);
                return $this->successResponse(
                    WorkOrderResource::collection($workOrders),
                    'Work orders retrieved successfully'
                );
            }

            $workOrders = $this->workOrderService->getAll([], $perPage);

            return $this->paginatedResponse(
                WorkOrderResource::collection($workOrders),
                'Work orders retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve work orders: ' . $e->getMessage());
        }
    }

    /**
     * Store a newly created work order.
     *
     * @param StoreWorkOrderRequest $request
     * @return JsonResponse
     */
    public function store(StoreWorkOrderRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $workOrder = $this->workOrderService->create($validated);

            return $this->successResponse(
                new WorkOrderResource($workOrder),
                'Work order created successfully',
                201
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create work order: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified work order.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $workOrder = $this->workOrderService->getById($id);

            return $this->successResponse(
                new WorkOrderResource($workOrder),
                'Work order retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve work order: ' . $e->getMessage(), [], 404);
        }
    }

    /**
     * Update the specified work order.
     *
     * @param UpdateWorkOrderRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(UpdateWorkOrderRequest $request, int $id): JsonResponse
    {
        try {
            $validated = $request->validated();
            $workOrder = $this->workOrderService->update($id, $validated);

            return $this->successResponse(
                new WorkOrderResource($workOrder),
                'Work order updated successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update work order: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified work order.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->workOrderService->delete($id);

            return $this->successResponse(null, 'Work order deleted successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete work order: ' . $e->getMessage());
        }
    }

    /**
     * Start production for a work order.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function startProduction(int $id): JsonResponse
    {
        try {
            $workOrder = $this->workOrderService->startProduction($id);

            return $this->successResponse(
                new WorkOrderResource($workOrder),
                'Production started successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to start production: ' . $e->getMessage());
        }
    }

    /**
     * Complete production for a work order.
     *
     * @param CompleteProductionRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function completeProduction(CompleteProductionRequest $request, int $id): JsonResponse
    {
        try {
            $validated = $request->validated();
            $workOrder = $this->workOrderService->completeProduction($id, $validated);

            return $this->successResponse(
                new WorkOrderResource($workOrder),
                'Production completed successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to complete production: ' . $e->getMessage());
        }
    }

    /**
     * Cancel a work order.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function cancel(Request $request, int $id): JsonResponse
    {
        try {
            $reason = $request->input('reason', '');
            $workOrder = $this->workOrderService->cancel($id, $reason);

            return $this->successResponse(
                new WorkOrderResource($workOrder),
                'Work order cancelled successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to cancel work order: ' . $e->getMessage());
        }
    }

    /**
     * Get in-progress work orders.
     *
     * @return JsonResponse
     */
    public function inProgress(): JsonResponse
    {
        try {
            $workOrders = $this->workOrderService->getInProgress();

            return $this->successResponse(
                WorkOrderResource::collection($workOrders),
                'In-progress work orders retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve in-progress work orders: ' . $e->getMessage());
        }
    }

    /**
     * Get overdue work orders.
     *
     * @return JsonResponse
     */
    public function overdue(): JsonResponse
    {
        try {
            $workOrders = $this->workOrderService->getOverdue();

            return $this->successResponse(
                WorkOrderResource::collection($workOrders),
                'Overdue work orders retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve overdue work orders: ' . $e->getMessage());
        }
    }
}
