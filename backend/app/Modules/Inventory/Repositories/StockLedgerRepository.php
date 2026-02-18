<?php

namespace App\Modules\Inventory\Repositories;

use App\Modules\Inventory\Models\StockLedger;
use App\Repositories\BaseRepository;
use Illuminate\Support\Facades\DB;

/**
 * Stock Ledger Repository
 *
 * Handles data access for stock ledger entries.
 */
class StockLedgerRepository extends BaseRepository
{
    /**
     * Specify Model class name
     */
    protected function model(): string
    {
        return StockLedger::class;
    }

    /**
     * Get stock ledger entries for a product
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getProductLedger(int $productId, ?int $warehouseId = null)
    {
        $query = $this->model
            ->where('product_id', $productId)
            ->with(['creator', 'reference']);

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        return $query->orderBy('transaction_date', 'desc')
            ->orderBy('id', 'desc')
            ->get();
    }

    /**
     * Calculate current stock balance for a product
     */
    public function calculateStockBalance(int $productId, ?int $warehouseId = null): float
    {
        $query = $this->model->where('product_id', $productId);

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        return $query->sum('quantity');
    }

    /**
     * Get stock by batch
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getStockByBatch(int $productId, string $batchNumber, ?int $warehouseId = null)
    {
        $query = $this->model
            ->where('product_id', $productId)
            ->where('batch_number', $batchNumber);

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        return $query->get();
    }

    /**
     * Get expiring stock
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getExpiringStock(int $productId, int $days = 30)
    {
        $expiryDate = now()->addDays($days);

        return $this->model
            ->where('product_id', $productId)
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '<=', $expiryDate)
            ->where('expiry_date', '>', now())
            ->orderBy('expiry_date')
            ->get();
    }

    /**
     * Get expired stock
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getExpiredStock(int $productId)
    {
        return $this->model
            ->where('product_id', $productId)
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '<', now())
            ->orderBy('expiry_date')
            ->get();
    }

    /**
     * Get stock movement summary
     */
    public function getMovementSummary(int $productId, string $startDate, string $endDate): array
    {
        return $this->model
            ->where('product_id', $productId)
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->select(
                'movement_type',
                DB::raw('SUM(quantity) as total_quantity'),
                DB::raw('SUM(total_cost) as total_cost'),
                DB::raw('COUNT(*) as transaction_count')
            )
            ->groupBy('movement_type')
            ->get()
            ->toArray();
    }

    /**
     * Get stock valuation
     */
    public function getStockValuation(int $productId, ?int $warehouseId = null): float
    {
        $query = $this->model
            ->where('product_id', $productId)
            ->whereNotNull('unit_cost');

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        return $query->sum(DB::raw('quantity * unit_cost'));
    }
}
