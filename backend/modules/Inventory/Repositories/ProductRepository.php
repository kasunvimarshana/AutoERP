<?php

declare(strict_types=1);

namespace Modules\Inventory\Repositories;

use Modules\Core\Repositories\BaseRepository;
use Modules\Inventory\Models\Product;

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
     * Find product by SKU.
     */
    public function findBySKU(string $sku): ?Product
    {
        return $this->newQuery()->where('sku', $sku)->first();
    }

    /**
     * Find product by barcode.
     */
    public function findByBarcode(string $barcode): ?Product
    {
        return $this->newQuery()->where('barcode', $barcode)->first();
    }

    /**
     * Get products by category.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function findByCategory(string $categoryId)
    {
        return $this->newQuery()->where('category_id', $categoryId)->get();
    }

    /**
     * Search products by name or SKU.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function search(string $search)
    {
        return $this->newQuery()
            ->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('barcode', 'like', "%{$search}%");
            })
            ->get();
    }
}
