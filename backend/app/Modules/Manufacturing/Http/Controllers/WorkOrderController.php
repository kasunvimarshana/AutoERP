<?php

namespace App\Modules\Manufacturing\Http\Controllers;

use App\Http\Controllers\BaseController;
use App\Modules\Manufacturing\DTOs\CompleteWorkOrderDTO;
use App\Modules\Manufacturing\Enums\WorkOrderStatus;
use App\Modules\Manufacturing\Services\WorkOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Work Order API Controller
 *
 * Handles HTTP requests for work order management.
 */
class WorkOrderController extends BaseController
{
    public function __construct(
        protected WorkOrderService $workOrderService
    ) {}

    /**
     * Display a listing of work orders
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $workOrders = $this->workOrderService->getAllWorkOrders($perPage);

        return $this->successResponse($workOrders, 'Work orders retrieved successfully');
    }

    /**
     * Store a newly created work order
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'work_order_number' => 'required|string|unique:work_orders,work_order_number',
            'production_order_id' => 'required|integer|exists:production_orders,id',
            'workstation' => 'nullable|string',
            'description' => 'nullable|string',
            'scheduled_start' => 'nullable|date',
            'scheduled_end' => 'nullable|date|after_or_equal:scheduled_start',
            'status' => 'nullable|in:pending,in_progress',
            'notes' => 'nullable|string',
            'assigned_to' => 'nullable|integer|exists:users,id',
        ]);

        $workOrder = $this->workOrderService->createWorkOrder($validated);

        return $this->createdResponse($workOrder, 'Work order created successfully');
    }

    /**
     * Display the specified work order
     */
    public function show(int $id): JsonResponse
    {
        $workOrder = $this->workOrderService->getWorkOrderById($id);

        return $this->successResponse($workOrder, 'Work order retrieved successfully');
    }

    /**
     * Update the specified work order
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'work_order_number' => 'required|string|unique:work_orders,work_order_number,'.$id,
            'production_order_id' => 'required|integer|exists:production_orders,id',
            'workstation' => 'nullable|string',
            'description' => 'nullable|string',
            'scheduled_start' => 'nullable|date',
            'scheduled_end' => 'nullable|date|after_or_equal:scheduled_start',
            'notes' => 'nullable|string',
            'assigned_to' => 'nullable|integer|exists:users,id',
        ]);

        $workOrder = $this->workOrderService->updateWorkOrder($id, $validated);

        return $this->successResponse($workOrder, 'Work order updated successfully');
    }

    /**
     * Remove the specified work order
     */
    public function destroy(int $id): JsonResponse
    {
        $this->workOrderService->deleteWorkOrder($id);

        return $this->successResponse(null, 'Work order deleted successfully');
    }

    /**
     * Start a work order
     */
    public function start(int $id): JsonResponse
    {
        $workOrder = $this->workOrderService->start($id);

        return $this->successResponse($workOrder, 'Work order started successfully');
    }

    /**
     * Complete a work order
     */
    public function complete(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'actual_end' => 'nullable|date',
            'completed_by' => 'nullable|integer|exists:users,id',
            'notes' => 'nullable|string',
        ]);

        $validated['work_order_id'] = $id;
        $dto = CompleteWorkOrderDTO::fromArray($validated);
        $workOrder = $this->workOrderService->complete($id, $dto);

        return $this->successResponse($workOrder, 'Work order completed successfully');
    }

    /**
     * Cancel a work order
     */
    public function cancel(int $id): JsonResponse
    {
        $workOrder = $this->workOrderService->cancel($id);

        return $this->successResponse($workOrder, 'Work order cancelled successfully');
    }

    /**
     * Get work orders by status
     */
    public function byStatus(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,in_progress,completed,cancelled',
        ]);

        $status = WorkOrderStatus::from($validated['status']);
        $workOrders = $this->workOrderService->getByStatus($status);

        return $this->successResponse($workOrders, 'Work orders retrieved successfully');
    }

    /**
     * Get pending work orders
     */
    public function pending(): JsonResponse
    {
        $workOrders = $this->workOrderService->getPending();

        return $this->successResponse($workOrders, 'Pending work orders retrieved successfully');
    }

    /**
     * Get in-progress work orders
     */
    public function inProgress(): JsonResponse
    {
        $workOrders = $this->workOrderService->getInProgress();

        return $this->successResponse($workOrders, 'In-progress work orders retrieved successfully');
    }

    /**
     * Get work orders for a production order
     */
    public function byProductionOrder(int $productionOrderId): JsonResponse
    {
        $workOrders = $this->workOrderService->getByProductionOrder($productionOrderId);

        return $this->successResponse($workOrders, 'Work orders retrieved successfully');
    }

    /**
     * Get work orders assigned to current user
     */
    public function myWorkOrders(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $workOrders = $this->workOrderService->getAssignedToUser($userId);

        return $this->successResponse($workOrders, 'Your work orders retrieved successfully');
    }

    /**
     * Get overdue work orders
     */
    public function overdue(): JsonResponse
    {
        $workOrders = $this->workOrderService->getOverdue();

        return $this->successResponse($workOrders, 'Overdue work orders retrieved successfully');
    }

    /**
     * Search work orders
     */
    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'query' => 'required|string|min:1',
        ]);

        $workOrders = $this->workOrderService->searchWorkOrders($validated['query']);

        return $this->successResponse($workOrders, 'Search results retrieved successfully');
    }
}
