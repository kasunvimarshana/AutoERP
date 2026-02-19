<?php

declare(strict_types=1);

namespace Modules\Inventory\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Modules\Core\Repositories\BaseRepository;
use Modules\Inventory\Enums\StockCountStatus;
use Modules\Inventory\Exceptions\StockCountNotFoundException;
use Modules\Inventory\Models\StockCount;

/**
 * Stock Count Repository
 *
 * Handles data access operations for stock count records.
 * Provides CRUD operations and specialized queries for physical inventory counts.
 */
class StockCountRepository extends BaseRepository
{
    /**
     * Create a new repository instance.
     */
    protected function makeModel(): Model
    {
        return new StockCount;
    }

    /**
     * Find stock count by count number.
     *
     * @param  string  $countNumber  Count number
     */
    public function findByCountNumber(string $countNumber): ?StockCount
    {
        return $this->model->where('count_number', $countNumber)->first();
    }

    /**
     * Find stock count by count number or fail.
     *
     * @param  string  $countNumber  Count number
     *
     * @throws StockCountNotFoundException
     */
    public function findByCountNumberOrFail(string $countNumber): StockCount
    {
        $stockCount = $this->findByCountNumber($countNumber);

        if (! $stockCount) {
            throw new StockCountNotFoundException("Stock count with number {$countNumber} not found");
        }

        return $stockCount;
    }

    /**
     * Get stock counts by status.
     *
     * @param  StockCountStatus  $status  Count status
     * @param  int  $perPage  Results per page
     */
    public function getByStatus(StockCountStatus $status, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->where('status', $status)
            ->with(['warehouse', 'items'])
            ->latest('count_date')
            ->paginate($perPage);
    }

    /**
     * Get stock counts by warehouse.
     *
     * @param  string  $warehouseId  Warehouse ID
     * @param  int  $perPage  Results per page
     */
    public function getByWarehouse(string $warehouseId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->where('warehouse_id', $warehouseId)
            ->with(['warehouse', 'items'])
            ->latest('count_date')
            ->paginate($perPage);
    }

    /**
     * Get planned stock counts.
     *
     * @param  int  $perPage  Results per page
     */
    public function getPlanned(int $perPage = 15): LengthAwarePaginator
    {
        return $this->getByStatus(StockCountStatus::PLANNED, $perPage);
    }

    /**
     * Get in-progress stock counts.
     *
     * @param  int  $perPage  Results per page
     */
    public function getInProgress(int $perPage = 15): LengthAwarePaginator
    {
        return $this->getByStatus(StockCountStatus::IN_PROGRESS, $perPage);
    }

    /**
     * Get completed stock counts.
     *
     * @param  int  $perPage  Results per page
     */
    public function getCompleted(int $perPage = 15): LengthAwarePaginator
    {
        return $this->getByStatus(StockCountStatus::COMPLETED, $perPage);
    }

    /**
     * Get reconciled stock counts.
     *
     * @param  int  $perPage  Results per page
     */
    public function getReconciled(int $perPage = 15): LengthAwarePaginator
    {
        return $this->getByStatus(StockCountStatus::RECONCILED, $perPage);
    }

    /**
     * Get cancelled stock counts.
     *
     * @param  int  $perPage  Results per page
     */
    public function getCancelled(int $perPage = 15): LengthAwarePaginator
    {
        return $this->getByStatus(StockCountStatus::CANCELLED, $perPage);
    }

    /**
     * Get stock counts by date range.
     *
     * @param  string  $fromDate  Start date (Y-m-d format)
     * @param  string  $toDate  End date (Y-m-d format)
     * @param  int  $perPage  Results per page
     */
    public function getByDateRange(string $fromDate, string $toDate, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->whereBetween('count_date', [$fromDate, $toDate])
            ->with(['warehouse', 'items'])
            ->latest('count_date')
            ->paginate($perPage);
    }

    /**
     * Get scheduled counts due.
     *
     * @param  string|null  $dueDate  Due date (defaults to today)
     * @param  int  $perPage  Results per page
     */
    public function getScheduledDue(?string $dueDate = null, int $perPage = 15): LengthAwarePaginator
    {
        $dueDate = $dueDate ?? now()->format('Y-m-d');

        return $this->model
            ->where('status', StockCountStatus::PLANNED)
            ->where('scheduled_date', '<=', $dueDate)
            ->with(['warehouse', 'items'])
            ->orderBy('scheduled_date')
            ->paginate($perPage);
    }

    /**
     * Get overdue stock counts.
     *
     * @param  int  $perPage  Results per page
     */
    public function getOverdue(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->where('status', StockCountStatus::PLANNED)
            ->where('scheduled_date', '<', now()->format('Y-m-d'))
            ->with(['warehouse', 'items'])
            ->orderBy('scheduled_date')
            ->paginate($perPage);
    }

    /**
     * Search stock counts with filters.
     *
     * @param  array  $filters  Search filters
     * @param  int  $perPage  Results per page
     */
    public function searchStockCounts(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->query()->with(['warehouse', 'items']);

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('count_number', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%")
                    ->orWhere('counted_by', 'like', "%{$search}%")
                    ->orWhereHas('warehouse', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('code', 'like', "%{$search}%");
                    });
            });
        }

        if (! empty($filters['status'])) {
            if (is_array($filters['status'])) {
                $query->whereIn('status', $filters['status']);
            } else {
                $query->where('status', $filters['status']);
            }
        }

        if (! empty($filters['warehouse_id'])) {
            $query->where('warehouse_id', $filters['warehouse_id']);
        }

        if (! empty($filters['from_date'])) {
            $query->where('count_date', '>=', $filters['from_date']);
        }

        if (! empty($filters['to_date'])) {
            $query->where('count_date', '<=', $filters['to_date']);
        }

        if (! empty($filters['scheduled_from'])) {
            $query->where('scheduled_date', '>=', $filters['scheduled_from']);
        }

        if (! empty($filters['scheduled_to'])) {
            $query->where('scheduled_date', '<=', $filters['scheduled_to']);
        }

        if (! empty($filters['counted_by'])) {
            $query->where('counted_by', $filters['counted_by']);
        }

        if (! empty($filters['approved_by'])) {
            $query->where('approved_by', $filters['approved_by']);
        }

        if (! empty($filters['created_by'])) {
            $query->where('created_by', $filters['created_by']);
        }

        if (isset($filters['overdue']) && $filters['overdue']) {
            $query->where('status', StockCountStatus::PLANNED)
                ->where('scheduled_date', '<', now()->format('Y-m-d'));
        }

        $sortField = $filters['sort_by'] ?? 'count_date';
        $sortDirection = $filters['sort_direction'] ?? 'desc';

        return $query->orderBy($sortField, $sortDirection)->paginate($perPage);
    }

    /**
     * Get stock count statistics for date range.
     *
     * @param  string  $fromDate  Start date
     * @param  string  $toDate  End date
     * @return array Statistics data
     */
    public function getStatistics(string $fromDate, string $toDate): array
    {
        $counts = $this->model
            ->whereBetween('count_date', [$fromDate, $toDate])
            ->get();

        return [
            'total_counts' => $counts->count(),
            'planned' => $counts->where('status', StockCountStatus::PLANNED)->count(),
            'in_progress' => $counts->where('status', StockCountStatus::IN_PROGRESS)->count(),
            'completed' => $counts->where('status', StockCountStatus::COMPLETED)->count(),
            'reconciled' => $counts->where('status', StockCountStatus::RECONCILED)->count(),
            'cancelled' => $counts->where('status', StockCountStatus::CANCELLED)->count(),
        ];
    }

    /**
     * Get stock count summary by warehouse.
     *
     * @param  string  $fromDate  Start date
     * @param  string  $toDate  End date
     */
    public function getSummaryByWarehouse(string $fromDate, string $toDate): Collection
    {
        return $this->model
            ->whereBetween('count_date', [$fromDate, $toDate])
            ->selectRaw('
                warehouse_id,
                COUNT(*) as total_counts,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as planned,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as in_progress,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as reconciled,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as cancelled
            ', [
                StockCountStatus::PLANNED->value,
                StockCountStatus::IN_PROGRESS->value,
                StockCountStatus::COMPLETED->value,
                StockCountStatus::RECONCILED->value,
                StockCountStatus::CANCELLED->value,
            ])
            ->groupBy('warehouse_id')
            ->with('warehouse')
            ->get();
    }

    /**
     * Get recent stock counts.
     *
     * @param  int  $limit  Number of records to return
     */
    public function getRecentCounts(int $limit = 10): Collection
    {
        return $this->model
            ->with(['warehouse'])
            ->latest('count_date')
            ->limit($limit)
            ->get();
    }

    /**
     * Update stock count and return the updated model.
     *
     * @param  int|string  $id  Stock count ID
     * @param  array  $data  Data to update
     */
    public function updateAndReturn(int|string $id, array $data): StockCount
    {
        $stockCount = $this->findOrFail($id);
        $stockCount->update($data);

        return $stockCount->fresh(['warehouse', 'items']);
    }

    /**
     * Start stock count.
     *
     * @param  int|string  $id  Stock count ID
     */
    public function start(int|string $id): bool
    {
        return $this->update($id, [
            'status' => StockCountStatus::IN_PROGRESS,
            'started_at' => now(),
        ]);
    }

    /**
     * Complete stock count.
     *
     * @param  int|string  $id  Stock count ID
     */
    public function complete(int|string $id): bool
    {
        return $this->update($id, [
            'status' => StockCountStatus::COMPLETED,
            'completed_at' => now(),
        ]);
    }

    /**
     * Reconcile stock count.
     *
     * @param  int|string  $id  Stock count ID
     */
    public function reconcile(int|string $id): bool
    {
        return $this->update($id, [
            'status' => StockCountStatus::RECONCILED,
            'reconciled_at' => now(),
        ]);
    }

    /**
     * Cancel stock count.
     *
     * @param  int|string  $id  Stock count ID
     */
    public function cancel(int|string $id): bool
    {
        return $this->update($id, [
            'status' => StockCountStatus::CANCELLED,
        ]);
    }
}
