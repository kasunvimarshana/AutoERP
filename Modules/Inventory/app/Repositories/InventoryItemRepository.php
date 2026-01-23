<?php

declare(strict_types=1);

namespace Modules\Inventory\Repositories;

use App\Core\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Modules\Inventory\Models\InventoryItem;

/**
 * Inventory Item Repository
 *
 * Handles data access for InventoryItem model
 */
class InventoryItemRepository extends BaseRepository
{
    /**
     * {@inheritDoc}
     */
    protected function makeModel(): Model
    {
        return new InventoryItem;
    }

    /**
     * Find item by item code and branch
     */
    public function findByItemCodeAndBranch(string $itemCode, int $branchId): ?InventoryItem
    {
        /** @var InventoryItem|null */
        return $this->model->where('item_code', $itemCode)
            ->where('branch_id', $branchId)
            ->first();
    }

    /**
     * Get items for a specific branch
     */
    public function getByBranch(int $branchId): Collection
    {
        return $this->model->forBranch($branchId)->get();
    }

    /**
     * Get low stock items
     */
    public function getLowStockItems(?int $branchId = null): Collection
    {
        $query = $this->model->lowStock();

        if ($branchId) {
            $query->forBranch($branchId);
        }

        return $query->get();
    }

    /**
     * Get items by category
     */
    public function getByCategory(string $category, ?int $branchId = null): Collection
    {
        $query = $this->model->where('category', $category);

        if ($branchId) {
            $query->forBranch($branchId);
        }

        return $query->get();
    }

    /**
     * Search items
     *
     * @param  array<string, mixed>  $filters
     */
    public function search(array $filters): Collection
    {
        $query = $this->model->newQuery();

        if (! empty($filters['search'])) {
            $query->search($filters['search']);
        }

        if (! empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (! empty($filters['branch_id'])) {
            $query->forBranch($filters['branch_id']);
        }

        if (isset($filters['low_stock']) && $filters['low_stock']) {
            $query->lowStock();
        }

        return $query->get();
    }

    /**
     * Update stock quantity
     */
    public function updateStock(int $itemId, int $quantity): bool
    {
        return $this->model->where('id', $itemId)
            ->update(['stock_on_hand' => $quantity]);
    }

    /**
     * Increment stock
     */
    public function incrementStock(int $itemId, int $quantity): int
    {
        return $this->model->where('id', $itemId)
            ->increment('stock_on_hand', $quantity);
    }

    /**
     * Decrement stock
     */
    public function decrementStock(int $itemId, int $quantity): int
    {
        return $this->model->where('id', $itemId)
            ->decrement('stock_on_hand', $quantity);
    }

    /**
     * Check if item code exists for branch
     */
    public function itemCodeExistsForBranch(string $itemCode, int $branchId, ?int $excludeId = null): bool
    {
        $query = $this->model->where('item_code', $itemCode)
            ->where('branch_id', $branchId);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }
}
