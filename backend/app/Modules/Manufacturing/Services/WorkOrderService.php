<?php

namespace App\Modules\Manufacturing\Services;

use App\Modules\Manufacturing\DTOs\CompleteWorkOrderDTO;
use App\Modules\Manufacturing\Enums\WorkOrderStatus;
use App\Modules\Manufacturing\Events\WorkOrderCompleted;
use App\Modules\Manufacturing\Events\WorkOrderStarted;
use App\Modules\Manufacturing\Models\WorkOrder;
use App\Modules\Manufacturing\Repositories\WorkOrderRepository;
use App\Services\BaseService;
use Illuminate\Support\Facades\Auth;

/**
 * Work Order Service
 *
 * Handles business logic for work order scheduling and completion.
 */
class WorkOrderService extends BaseService
{
    public function __construct(
        protected WorkOrderRepository $workOrderRepository
    ) {}

    /**
     * Get all work orders with pagination
     */
    public function getAllWorkOrders(int $perPage = 15)
    {
        return $this->workOrderRepository->paginate($perPage);
    }

    /**
     * Create a new work order
     */
    public function createWorkOrder(array $data): WorkOrder
    {
        return $this->transaction(function () use ($data) {
            $this->logInfo('Creating new work order', ['wo_number' => $data['work_order_number']]);

            // Create work order
            $workOrder = $this->workOrderRepository->create([
                'work_order_number' => $data['work_order_number'],
                'production_order_id' => $data['production_order_id'],
                'workstation' => $data['workstation'] ?? null,
                'description' => $data['description'] ?? null,
                'scheduled_start' => $data['scheduled_start'] ?? null,
                'scheduled_end' => $data['scheduled_end'] ?? null,
                'status' => $data['status'] ?? WorkOrderStatus::PENDING->value,
                'notes' => $data['notes'] ?? null,
                'assigned_to' => $data['assigned_to'] ?? null,
            ]);

            // Load relationships
            $workOrder->load(['productionOrder.product', 'assignedUser']);

            $this->logInfo('Work order created successfully', ['id' => $workOrder->id]);

            return $workOrder;
        });
    }

    /**
     * Update a work order
     */
    public function updateWorkOrder(int $id, array $data): WorkOrder
    {
        return $this->transaction(function () use ($id, $data) {
            $this->logInfo('Updating work order', ['id' => $id]);

            // Update work order
            $this->workOrderRepository->update($id, [
                'work_order_number' => $data['work_order_number'],
                'production_order_id' => $data['production_order_id'],
                'workstation' => $data['workstation'] ?? null,
                'description' => $data['description'] ?? null,
                'scheduled_start' => $data['scheduled_start'] ?? null,
                'scheduled_end' => $data['scheduled_end'] ?? null,
                'notes' => $data['notes'] ?? null,
                'assigned_to' => $data['assigned_to'] ?? null,
            ]);

            $workOrder = $this->workOrderRepository->findOrFail($id);
            $workOrder->load(['productionOrder.product', 'assignedUser']);

            $this->logInfo('Work order updated successfully', ['id' => $id]);

            return $workOrder;
        });
    }

    /**
     * Delete a work order
     */
    public function deleteWorkOrder(int $id): bool
    {
        $this->logInfo('Deleting work order', ['id' => $id]);

        $result = $this->workOrderRepository->delete($id);

        if ($result) {
            $this->logInfo('Work order deleted successfully', ['id' => $id]);
        }

        return $result;
    }

    /**
     * Get a work order by ID
     */
    public function getWorkOrderById(int $id): WorkOrder
    {
        return $this->workOrderRepository->findWithRelations($id);
    }

    /**
     * Start a work order
     */
    public function start(int $id): WorkOrder
    {
        return $this->transaction(function () use ($id) {
            $workOrder = $this->workOrderRepository->findOrFail($id);

            // Check if can be started
            if (! $workOrder->status->canStart()) {
                throw new \InvalidArgumentException(
                    "Work order with status '{$workOrder->status->label()}' cannot be started"
                );
            }

            $this->logInfo('Starting work order', ['id' => $id]);

            // Update status
            $this->workOrderRepository->update($id, [
                'status' => WorkOrderStatus::IN_PROGRESS->value,
                'actual_start' => now(),
                'started_by' => Auth::id(),
            ]);

            $workOrder->refresh();
            $workOrder->load(['productionOrder.product', 'assignedUser']);

            // Dispatch event
            event(new WorkOrderStarted($workOrder));

            $this->logInfo('Work order started successfully', ['id' => $id]);

            return $workOrder;
        });
    }

    /**
     * Complete a work order
     */
    public function complete(int $id, ?CompleteWorkOrderDTO $dto = null): WorkOrder
    {
        return $this->transaction(function () use ($id, $dto) {
            $workOrder = $this->workOrderRepository->findOrFail($id);

            // Check if can be completed
            if (! $workOrder->status->canComplete()) {
                throw new \InvalidArgumentException(
                    "Work order with status '{$workOrder->status->label()}' cannot be completed"
                );
            }

            $this->logInfo('Completing work order', ['id' => $id]);

            $updateData = [
                'status' => WorkOrderStatus::COMPLETED->value,
                'actual_end' => $dto?->actualEnd ?? now(),
                'completed_by' => $dto?->completedBy ?? Auth::id(),
            ];

            if ($dto?->notes) {
                $updateData['notes'] = $dto->notes;
            }

            $this->workOrderRepository->update($id, $updateData);

            $workOrder->refresh();
            $workOrder->load(['productionOrder.product', 'assignedUser']);

            // Dispatch event
            event(new WorkOrderCompleted($workOrder));

            $this->logInfo('Work order completed successfully', ['id' => $id]);

            return $workOrder;
        });
    }

    /**
     * Cancel a work order
     */
    public function cancel(int $id): WorkOrder
    {
        return $this->transaction(function () use ($id) {
            $workOrder = $this->workOrderRepository->findOrFail($id);

            // Check if can be cancelled
            if (! $workOrder->status->canCancel()) {
                throw new \InvalidArgumentException(
                    "Work order with status '{$workOrder->status->label()}' cannot be cancelled"
                );
            }

            $this->logInfo('Cancelling work order', ['id' => $id]);

            $this->workOrderRepository->update($id, [
                'status' => WorkOrderStatus::CANCELLED->value,
            ]);

            $workOrder->refresh();
            $workOrder->load(['productionOrder.product', 'assignedUser']);

            $this->logInfo('Work order cancelled successfully', ['id' => $id]);

            return $workOrder;
        });
    }

    /**
     * Get work orders by status
     */
    public function getByStatus(WorkOrderStatus $status)
    {
        return $this->workOrderRepository->getByStatus($status);
    }

    /**
     * Get pending work orders
     */
    public function getPending()
    {
        return $this->workOrderRepository->getPending();
    }

    /**
     * Get in-progress work orders
     */
    public function getInProgress()
    {
        return $this->workOrderRepository->getInProgress();
    }

    /**
     * Get work orders for production order
     */
    public function getByProductionOrder(int $productionOrderId)
    {
        return $this->workOrderRepository->getByProductionOrder($productionOrderId);
    }

    /**
     * Get work orders assigned to user
     */
    public function getAssignedToUser(int $userId)
    {
        return $this->workOrderRepository->getAssignedToUser($userId);
    }

    /**
     * Search work orders
     */
    public function searchWorkOrders(string $search)
    {
        return $this->workOrderRepository->search($search);
    }

    /**
     * Get overdue work orders
     */
    public function getOverdue()
    {
        return $this->workOrderRepository->getOverdue();
    }
}
