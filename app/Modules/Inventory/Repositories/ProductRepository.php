<?php

namespace App\Modules\Inventory\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Modules\Inventory\Models\Product;
use Illuminate\Database\Eloquent\Collection;

/**
 * Product Repository
 * 
 * Handles data access operations for products
 */
class ProductRepository extends BaseRepository
{
    /**
     * Specify the model class name
     *
     * @return string
     */
    protected function model(): string
    {
        return Product::class;
    }

    /**
     * Find product by SKU
     *
     * @param string $sku
     * @return Product|null
     */
    public function findBySku(string $sku): ?Product
    {
        return $this->model->where('sku', $sku)->first();
    }

    /**
     * Get products with low stock levels
     *
     * @return Collection
     */
    public function lowStockProducts(): Collection
    {
        return $this->model
            ->whereColumn('current_stock', '<=', 'min_stock_level')
            ->get();
    }

    /**
     * Get active products
     *
     * @return Collection
     */
    public function getActiveProducts(): Collection
    {
        return $this->model
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    /**
     * Search products by name or SKU
     *
     * @param string $search
     * @return Collection
     */
    public function searchProducts(string $search): Collection
    {
        return $this->model
            ->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            })
            ->get();
    }

    protected function getFilterableColumns(): array
    {
        return ['category_id', 'status', 'type', 'is_active'];
    }
}
