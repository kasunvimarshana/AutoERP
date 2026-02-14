<?php

namespace App\Modules\Inventory\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Modules\Inventory\Models\StockLedger;
use Illuminate\Database\Eloquent\Collection;

/**
 * Stock Ledger Repository
 * 
 * Handles data access operations for stock ledger entries
 */
class StockLedgerRepository extends BaseRepository
{
    /**
     * Specify the model class name
     *
     * @return string
     */
    protected function model(): string
    {
        return StockLedger::class;
    }

    /**
     * Get total stock for a product in a warehouse
     *
     * @param int $productId
     * @param int $warehouseId
     * @return float
     */
    public function getProductStock(int $productId, int $warehouseId): float
    {
        return $this->model
            ->where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->sum('quantity');
    }

    /**
     * Get FIFO batches for stock allocation
     *
     * @param int $productId
     * @param int $warehouseId
     * @return Collection
     */
    public function getFifoBatches(int $productId, int $warehouseId): Collection
    {
        return $this->model
            ->where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->where('quantity', '>', 0)
            ->orderBy('created_at', 'asc')
            ->get();
    }

    /**
     * Get stock movements for a product
     *
     * @param int $productId
     * @param int|null $warehouseId
     * @return Collection
     */
    public function getStockMovements(int $productId, ?int $warehouseId = null): Collection
    {
        $query = $this->model
            ->where('product_id', $productId)
            ->orderBy('created_at', 'desc');

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        return $query->get();
    }
}
