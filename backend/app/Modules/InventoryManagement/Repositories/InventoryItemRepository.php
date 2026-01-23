<?php

namespace App\Modules\InventoryManagement\Repositories;

use App\Core\Base\BaseRepository;
use App\Modules\InventoryManagement\Models\InventoryItem;

class InventoryItemRepository extends BaseRepository
{
    public function __construct(InventoryItem $model)
    {
        parent::__construct($model);
    }

    /**
     * Search inventory items by various criteria
     */
    public function search(array $criteria)
    {
        $query = $this->model->query();

        if (!empty($criteria['search'])) {
            $search = $criteria['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('part_number', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if (!empty($criteria['item_type'])) {
            $query->where('item_type', $criteria['item_type']);
        }

        if (!empty($criteria['category'])) {
            $query->where('category', $criteria['category']);
        }

        if (!empty($criteria['brand'])) {
            $query->where('brand', $criteria['brand']);
        }

        if (!empty($criteria['is_active'])) {
            $query->where('is_active', $criteria['is_active']);
        }

        if (!empty($criteria['low_stock'])) {
            $query->lowStock();
        }

        if (!empty($criteria['out_of_stock'])) {
            $query->outOfStock();
        }

        if (!empty($criteria['tenant_id'])) {
            $query->where('tenant_id', $criteria['tenant_id']);
        }

        return $query->orderBy('name')->paginate($criteria['per_page'] ?? 15);
    }

    /**
     * Find inventory item by SKU
     */
    public function findBySku(string $sku): ?InventoryItem
    {
        return $this->model->where('sku', $sku)->first();
    }

    /**
     * Find inventory item by part number
     */
    public function findByPartNumber(string $partNumber): ?InventoryItem
    {
        return $this->model->where('part_number', $partNumber)->first();
    }

    /**
     * Get items by type
     */
    public function getByType(string $type)
    {
        return $this->model->byType($type)->get();
    }

    /**
     * Get low stock items
     */
    public function getLowStock()
    {
        return $this->model->lowStock()->get();
    }

    /**
     * Get out of stock items
     */
    public function getOutOfStock()
    {
        return $this->model->outOfStock()->get();
    }

    /**
     * Get active items
     */
    public function getActive()
    {
        return $this->model->active()->get();
    }

    /**
     * Get items by category
     */
    public function getByCategory(string $category)
    {
        return $this->model->where('category', $category)->get();
    }

    /**
     * Get items by brand
     */
    public function getByBrand(string $brand)
    {
        return $this->model->where('brand', $brand)->get();
    }

    /**
     * Get real items (not dummy)
     */
    public function getReal()
    {
        return $this->model->real()->get();
    }

    /**
     * Get dummy items
     */
    public function getDummy()
    {
        return $this->model->dummy()->get();
    }
}
