<?php

namespace App\Modules\Manufacturing\Repositories;

use App\Modules\Manufacturing\Enums\WorkOrderStatus;
use App\Modules\Manufacturing\Models\WorkOrder;
use App\Repositories\BaseRepository;

/**
 * Work Order Repository
 *
 * Handles data access for work orders.
 */
class WorkOrderRepository extends BaseRepository
{
    /**
     * Specify Model class name
     */
    protected function model(): string
    {
        return WorkOrder::class;
    }

    /**
     * Find work order by number
     */
    public function findByNumber(string $number): ?WorkOrder
    {
        return $this->model->where('work_order_number', $number)->first();
    }

    /**
     * Get work orders by status
     */
    public function getByStatus(WorkOrderStatus $status)
    {
        return $this->model
            ->byStatus($status)
            ->with(['productionOrder.product', 'assignedUser'])
            ->orderBy('scheduled_start', 'asc')
            ->get();
    }

    /**
     * Get pending work orders
     */
    public function getPending()
    {
        return $this->model
            ->pending()
            ->with(['productionOrder.product', 'assignedUser'])
            ->orderBy('scheduled_start', 'asc')
            ->get();
    }

    /**
     * Get in-progress work orders
     */
    public function getInProgress()
    {
        return $this->model
            ->inProgress()
            ->with(['productionOrder.product', 'assignedUser'])
            ->orderBy('actual_start', 'asc')
            ->get();
    }

    /**
     * Get work orders for production order
     */
    public function getByProductionOrder(int $productionOrderId)
    {
        return $this->model
            ->where('production_order_id', $productionOrderId)
            ->with(['assignedUser', 'starter', 'completer'])
            ->orderBy('scheduled_start', 'asc')
            ->get();
    }

    /**
     * Get work orders assigned to user
     */
    public function getAssignedToUser(int $userId)
    {
        return $this->model
            ->where('assigned_to', $userId)
            ->whereIn('status', [
                WorkOrderStatus::PENDING->value,
                WorkOrderStatus::IN_PROGRESS->value,
            ])
            ->with(['productionOrder.product'])
            ->orderBy('scheduled_start', 'asc')
            ->get();
    }

    /**
     * Find with relationships loaded
     */
    public function findWithRelations(int $id): WorkOrder
    {
        return $this->model
            ->with([
                'productionOrder.product',
                'productionOrder.billOfMaterial',
                'assignedUser',
                'starter',
                'completer',
            ])
            ->findOrFail($id);
    }

    /**
     * Search work orders
     */
    public function search(string $search)
    {
        return $this->model
            ->where(function ($query) use ($search) {
                $query->where('work_order_number', 'like', "%{$search}%")
                    ->orWhere('workstation', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%")
                    ->orWhereHas('productionOrder', function ($q) use ($search) {
                        $q->where('production_order_number', 'like', "%{$search}%");
                    });
            })
            ->with(['productionOrder.product', 'assignedUser'])
            ->get();
    }

    /**
     * Get scheduled work orders for date range
     */
    public function getScheduledBetween(string $startDate, string $endDate)
    {
        return $this->model
            ->whereBetween('scheduled_start', [$startDate, $endDate])
            ->with(['productionOrder.product', 'assignedUser'])
            ->orderBy('scheduled_start', 'asc')
            ->get();
    }

    /**
     * Get overdue work orders
     */
    public function getOverdue()
    {
        return $this->model
            ->where('scheduled_end', '<', now())
            ->whereIn('status', [
                WorkOrderStatus::PENDING->value,
                WorkOrderStatus::IN_PROGRESS->value,
            ])
            ->with(['productionOrder.product', 'assignedUser'])
            ->orderBy('scheduled_end', 'asc')
            ->get();
    }
}
