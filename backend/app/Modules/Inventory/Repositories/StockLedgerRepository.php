<?php

namespace App\Modules\Inventory\Repositories;

use App\Base\BaseRepository;
use App\Modules\Inventory\Models\StockLedger;
use Illuminate\Database\Eloquent\Collection;

class StockLedgerRepository extends BaseRepository
{
    public function __construct(StockLedger $model)
    {
        parent::__construct($model);
    }

    public function getByVariant(string $variantId, array $relations = []): Collection
    {
        return $this->model->with($relations)
            ->where('product_variant_id', $variantId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getByBranch(string $branchId, array $relations = []): Collection
    {
        return $this->model->with($relations)
            ->where('branch_id', $branchId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getByDateRange(string $from, string $to, array $relations = []): Collection
    {
        return $this->model->with($relations)
            ->whereBetween('created_at', [$from, $to])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function calculateStock(string $variantId, string $branchId): int
    {
        $inTransactions = $this->model
            ->where('product_variant_id', $variantId)
            ->where('branch_id', $branchId)
            ->where('transaction_type', 'in')
            ->sum('quantity');

        $outTransactions = $this->model
            ->where('product_variant_id', $variantId)
            ->where('branch_id', $branchId)
            ->where('transaction_type', 'out')
            ->sum('quantity');

        $adjustments = $this->model
            ->where('product_variant_id', $variantId)
            ->where('branch_id', $branchId)
            ->where('transaction_type', 'adjustment')
            ->sum('quantity');

        return $inTransactions - $outTransactions + $adjustments;
    }

    public function getByBatch(string $batchNumber, array $relations = []): Collection
    {
        return $this->model->with($relations)
            ->where('batch_number', $batchNumber)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getByInventory(string $inventoryId, array $relations = []): Collection
    {
        return $this->model->with($relations)
            ->where('inventory_id', $inventoryId)
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
