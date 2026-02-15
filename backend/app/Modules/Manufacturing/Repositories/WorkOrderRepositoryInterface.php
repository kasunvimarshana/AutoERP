<?php

declare(strict_types=1);

namespace App\Modules\Manufacturing\Repositories;

use App\Modules\Manufacturing\Models\WorkOrder;
use Illuminate\Database\Eloquent\Collection;

interface WorkOrderRepositoryInterface
{
    /**
     * Find work order by number
     *
     * @param string $workOrderNumber
     * @return WorkOrder|null
     */
    public function findByNumber(string $workOrderNumber): ?WorkOrder;

    /**
     * Get work orders by status
     *
     * @param string $status
     * @return Collection
     */
    public function getByStatus(string $status): Collection;

    /**
     * Get work orders by product
     *
     * @param int $productId
     * @return Collection
     */
    public function getByProduct(int $productId): Collection;

    /**
     * Get work orders by date range
     *
     * @param string $startDate
     * @param string $endDate
     * @return Collection
     */
    public function getByDateRange(string $startDate, string $endDate): Collection;

    /**
     * Get in-progress work orders
     *
     * @return Collection
     */
    public function getInProgress(): Collection;

    /**
     * Get overdue work orders
     *
     * @return Collection
     */
    public function getOverdue(): Collection;

    /**
     * Get work orders by branch
     *
     * @param int $branchId
     * @return Collection
     */
    public function getByBranch(int $branchId): Collection;

    /**
     * Search work orders
     *
     * @param array $filters
     * @return Collection
     */
    public function search(array $filters = []): Collection;

    /**
     * Get work order with items
     *
     * @param int $id
     * @return WorkOrder|null
     */
    public function getWithItems(int $id): ?WorkOrder;

    /**
     * Get work orders by priority
     *
     * @param string $priority
     * @return Collection
     */
    public function getByPriority(string $priority): Collection;
}
