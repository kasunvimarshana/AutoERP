<?php

namespace App\Modules\InventoryManagement\Services;

use App\Core\Base\BaseService;
use App\Modules\InventoryManagement\Events\StockAdjusted;
use App\Modules\InventoryManagement\Events\LowStockAlert;
use App\Modules\InventoryManagement\Repositories\InventoryItemRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class InventoryItemService extends BaseService
{
    public function __construct(InventoryItemRepository $repository)
    {
        parent::__construct($repository);
    }

    /**
     * Adjust stock quantity
     */
    public function adjustStock(int $itemId, int $quantity, string $reason, ?string $reference = null): Model
    {
        try {
            DB::beginTransaction();

            $item = $this->repository->findOrFail($itemId);
            $oldQuantity = $item->quantity_in_stock;
            $item->quantity_in_stock += $quantity;
            $item->save();

            event(new StockAdjusted($item, $oldQuantity, $item->quantity_in_stock, $reason, $reference));

            if ($item->quantity_in_stock <= $item->reorder_level) {
                event(new LowStockAlert($item));
            }

            DB::commit();

            return $item;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Increase stock
     */
    public function increaseStock(int $itemId, int $quantity, string $reason = 'stock_in', ?string $reference = null): Model
    {
        return $this->adjustStock($itemId, $quantity, $reason, $reference);
    }

    /**
     * Decrease stock
     */
    public function decreaseStock(int $itemId, int $quantity, string $reason = 'stock_out', ?string $reference = null): Model
    {
        return $this->adjustStock($itemId, -$quantity, $reason, $reference);
    }

    /**
     * Check if item is low on stock
     */
    public function isLowStock(int $itemId): bool
    {
        $item = $this->repository->findOrFail($itemId);
        return $item->quantity_in_stock <= $item->reorder_level;
    }

    /**
     * Get low stock items
     */
    public function getLowStockItems()
    {
        return $this->repository->getLowStockItems();
    }

    /**
     * Get out of stock items
     */
    public function getOutOfStockItems()
    {
        return $this->repository->getOutOfStockItems();
    }

    /**
     * Search items by SKU or name
     */
    public function search(string $query)
    {
        return $this->repository->search($query);
    }

    /**
     * Get items by category
     */
    public function getByCategory(int $categoryId)
    {
        return $this->repository->getByCategory($categoryId);
    }

    /**
     * Update reorder level
     */
    public function updateReorderLevel(int $itemId, int $reorderLevel): Model
    {
        return $this->update($itemId, ['reorder_level' => $reorderLevel]);
    }

    /**
     * Calculate total stock value
     */
    public function calculateTotalValue(): float
    {
        return $this->repository->calculateTotalValue();
    }

    /**
     * Get stock value by category
     */
    public function getStockValueByCategory(): array
    {
        return $this->repository->getStockValueByCategory();
    }
}
