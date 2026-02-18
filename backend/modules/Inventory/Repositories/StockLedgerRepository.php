<?php

declare(strict_types=1);

namespace Modules\Inventory\Repositories;

use Modules\Core\Repositories\BaseRepository;
use Modules\Inventory\Models\StockLedger;

/**
 * Stock Ledger Repository
 *
 * Handles data access for stock ledger.
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
     * Get stock movements for a product.
     *
     * @param  array<string, mixed>  $filters
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getProductMovements(string $productId, array $filters = [])
    {
        $query = $this->newQuery()->where('product_id', $productId);

        if (isset($filters['warehouse_id'])) {
            $query->where('warehouse_id', $filters['warehouse_id']);
        }

        if (isset($filters['from_date'])) {
            $query->where('transaction_date', '>=', $filters['from_date']);
        }

        if (isset($filters['to_date'])) {
            $query->where('transaction_date', '<=', $filters['to_date']);
        }

        return $query->orderBy('transaction_date', 'desc')->get();
    }

    /**
     * Get stock balance for a product at a warehouse.
     */
    public function getStockBalance(string $productId, string $warehouseId): float
    {
        return $this->newQuery()
            ->where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->selectRaw('SUM(quantity * 
                CASE 
                    WHEN transaction_type IN ("purchase_receipt", "stock_return", "adjustment_in", "transfer_in", "production_in", "opening_stock") THEN 1
                    WHEN transaction_type IN ("sales_order", "sales_invoice", "customer_return", "adjustment_out", "transfer_out", "production_out", "damage", "disposal") THEN -1
                    ELSE 0
                END
            ) as balance')
            ->value('balance') ?? 0;
    }
}
