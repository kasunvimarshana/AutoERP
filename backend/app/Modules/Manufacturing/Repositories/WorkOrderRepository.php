<?php

declare(strict_types=1);

namespace App\Modules\Manufacturing\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Modules\Manufacturing\Models\WorkOrder;
use Illuminate\Database\Eloquent\Collection;

class WorkOrderRepository extends BaseRepository implements WorkOrderRepositoryInterface
{
    /**
     * WorkOrderRepository constructor.
     *
     * @param WorkOrder $model
     */
    public function __construct(WorkOrder $model)
    {
        parent::__construct($model);
    }

    /**
     * Find work order by number
     *
     * @param string $workOrderNumber
     * @return WorkOrder|null
     */
    public function findByNumber(string $workOrderNumber): ?WorkOrder
    {
        return $this->model->where('work_order_number', $workOrderNumber)->first();
    }

    /**
     * Get work orders by status
     *
     * @param string $status
     * @return Collection
     */
    public function getByStatus(string $status): Collection
    {
        return $this->model->status($status)
            ->orderBy('priority', 'desc')
            ->orderBy('planned_start_date', 'asc')
            ->get();
    }

    /**
     * Get work orders by product
     *
     * @param int $productId
     * @return Collection
     */
    public function getByProduct(int $productId): Collection
    {
        return $this->model->where('product_id', $productId)
            ->orderBy('planned_start_date', 'desc')
            ->get();
    }

    /**
     * Get work orders by date range
     *
     * @param string $startDate
     * @param string $endDate
     * @return Collection
     */
    public function getByDateRange(string $startDate, string $endDate): Collection
    {
        return $this->model->dateRange($startDate, $endDate)->get();
    }

    /**
     * Get in-progress work orders
     *
     * @return Collection
     */
    public function getInProgress(): Collection
    {
        return $this->model->inProgress()
            ->orderBy('priority', 'desc')
            ->orderBy('planned_end_date', 'asc')
            ->get();
    }

    /**
     * Get overdue work orders
     *
     * @return Collection
     */
    public function getOverdue(): Collection
    {
        return $this->model
            ->whereIn('status', ['planned', 'released', 'in_progress'])
            ->where('planned_end_date', '<', now())
            ->orderBy('planned_end_date', 'asc')
            ->get();
    }

    /**
     * Get work orders by branch
     *
     * @param int $branchId
     * @return Collection
     */
    public function getByBranch(int $branchId): Collection
    {
        return $this->model->where('branch_id', $branchId)
            ->orderBy('planned_start_date', 'desc')
            ->get();
    }

    /**
     * Search work orders
     *
     * @param array $filters
     * @return Collection
     */
    public function search(array $filters = []): Collection
    {
        $query = $this->model->query();

        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('work_order_number', 'like', "%{$filters['search']}%")
                  ->orWhere('reference_number', 'like', "%{$filters['search']}%");
            });
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        if (isset($filters['product_id'])) {
            $query->where('product_id', $filters['product_id']);
        }

        if (isset($filters['branch_id'])) {
            $query->where('branch_id', $filters['branch_id']);
        }

        if (isset($filters['assigned_to'])) {
            $query->where('assigned_to', $filters['assigned_to']);
        }

        if (isset($filters['start_date']) && isset($filters['end_date'])) {
            $query->whereBetween('planned_start_date', [
                $filters['start_date'],
                $filters['end_date']
            ]);
        }

        return $query->with([
            'product',
            'bom',
            'branch',
            'assignedTo',
            'items.product'
        ])->get();
    }

    /**
     * Get work order with items
     *
     * @param int $id
     * @return WorkOrder|null
     */
    public function getWithItems(int $id): ?WorkOrder
    {
        return $this->model->with([
            'product',
            'bom.items.product',
            'branch',
            'location',
            'unit',
            'assignedTo',
            'items.product',
            'items.unit',
        ])->find($id);
    }

    /**
     * Get work orders by priority
     *
     * @param string $priority
     * @return Collection
     */
    public function getByPriority(string $priority): Collection
    {
        return $this->model->priority($priority)
            ->orderBy('planned_start_date', 'asc')
            ->get();
    }
}
