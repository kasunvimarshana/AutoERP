<?php

namespace App\Modules\Inventory\Repositories;

use App\Modules\Inventory\Models\Product;
use App\Repositories\BaseRepository;

/**
 * Product Repository
 *
 * Handles data access for products.
 */
class ProductRepository extends BaseRepository
{
    /**
     * Specify Model class name
     */
    protected function model(): string
    {
        return Product::class;
    }

    /**
     * Find product by SKU
     */
    public function findBySku(string $sku): ?Product
    {
        return $this->model->where('sku', $sku)->first();
    }

    /**
     * Find product by barcode
     */
    public function findByBarcode(string $barcode): ?Product
    {
        return $this->model->where('barcode', $barcode)->first();
    }

    /**
     * Get active products
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getActiveProducts()
    {
        return $this->model->where('is_active', true)->get();
    }

    /**
     * Get products by type
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByType(string $type)
    {
        return $this->model->where('type', $type)->get();
    }

    /**
     * Get products below reorder level
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getBelowReorderLevel()
    {
        return $this->model
            ->where('track_inventory', true)
            ->whereNotNull('reorder_level')
            ->get()
            ->filter(function ($product) {
                return $product->isBelowReorderLevel();
            });
    }

    /**
     * Search products
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function search(string $search)
    {
        return $this->model
            ->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('barcode', 'like', "%{$search}%");
            })
            ->get();
    }
}
