<?php

namespace App\Modules\POS\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Modules\POS\Models\POSTransaction;
use Illuminate\Database\Eloquent\Collection;

class POSTransactionRepository extends BaseRepository
{
    /**
     * Specify Model class name
     */
    protected function model(): string
    {
        return POSTransaction::class;
    }

    /**
     * Get transactions by status
     */
    public function getByStatus(string $status): Collection
    {
        return $this->model->where('status', $status)->get();
    }

    /**
     * Get transactions by date range
     */
    public function getByDateRange(string $startDate, string $endDate): Collection
    {
        return $this->model->whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get transactions by cashier
     */
    public function getByCashier(int $cashierId): Collection
    {
        return $this->model->where('cashier_id', $cashierId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get transactions by branch
     */
    public function getByBranch(int $branchId): Collection
    {
        return $this->model->where('branch_id', $branchId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get daily sales total
     */
    public function getDailySalesTotal(string $date, ?int $branchId = null): float
    {
        $query = $this->model->whereDate('created_at', $date)
            ->where('status', 'completed');

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        return $query->sum('total_amount');
    }
}
