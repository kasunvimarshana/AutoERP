<?php

namespace App\Modules\Inventory\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Modules\Inventory\Models\StockLedger;
use Illuminate\Support\Facades\DB;

/**
 * Stock Ledger Repository
 *
 * Handles append-only stock ledger operations.
 * Supports FIFO/FEFO, batch/lot/serial tracking.
 */
class StockLedgerRepository extends BaseRepository
{
    public function __construct(StockLedger $model)
    {
        parent::__construct($model);
    }

    /**
     * Get current stock for a product at a branch.
     */
    public function getCurrentStock(int $productId, int $branchId, ?int $warehouseId = null): float
    {
        $query = DB::table('stock_summary')
            ->where('product_id', $productId)
            ->where('branch_id', $branchId);

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        return $query->sum('current_quantity') ?? 0;
    }

    /**
     * Get stock by batch/lot numbers (for FIFO).
     *
     * @return \Illuminate\Support\Collection
     */
    public function getStockByBatch(int $productId, int $branchId, ?int $warehouseId = null)
    {
        $query = DB::table('stock_summary')
            ->where('product_id', $productId)
            ->where('branch_id', $branchId)
            ->where('current_quantity', '>', 0);

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        return $query
            ->orderBy('batch_number')
            ->orderBy('lot_number')
            ->get();
    }

    /**
     * Get stock by expiry date (for FEFO).
     *
     * @return \Illuminate\Support\Collection
     */
    public function getStockByExpiry(int $productId, int $branchId, ?int $warehouseId = null)
    {
        $query = DB::table('stock_summary')
            ->where('product_id', $productId)
            ->where('branch_id', $branchId)
            ->where('current_quantity', '>', 0)
            ->whereNotNull('expiry_date');

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        return $query
            ->orderBy('expiry_date')
            ->get();
    }

    /**
     * Get expired stock.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getExpiredStock(?int $branchId = null)
    {
        $query = DB::table('stock_summary')
            ->where('current_quantity', '>', 0)
            ->where('expiry_date', '<', now());

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        return $query->get();
    }

    /**
     * Get near-expiry stock.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getNearExpiryStock(int $days = 30, ?int $branchId = null)
    {
        $expiryDate = now()->addDays($days);

        $query = DB::table('stock_summary')
            ->where('current_quantity', '>', 0)
            ->where('expiry_date', '<=', $expiryDate)
            ->where('expiry_date', '>=', now());

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        return $query->orderBy('expiry_date')->get();
    }

    /**
     * Record stock movement (append-only).
     */
    public function recordMovement(array $data): StockLedger
    {
        // Calculate total cost
        $data['total_cost'] = $data['quantity'] * $data['unit_cost'];

        // Set created_by if not provided
        if (! isset($data['created_by'])) {
            $data['created_by'] = auth()->id();
        }

        return $this->create($data);
    }
}
