<?php

namespace App\Modules\Inventory\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Modules\Inventory\Models\Product;

class ProductRepository extends BaseRepository
{
    /**
     * ProductRepository constructor.
     */
    public function __construct(Product $model)
    {
        parent::__construct($model);
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
     */
    public function getActive(array $with = [])
    {
        $query = $this->model->where('is_active', true);

        if (!empty($with)) {
            $query->with($with);
        }

        return $query->get();
    }

    /**
     * Get products by category
     */
    public function getByCategory(string $category, array $with = [])
    {
        $query = $this->model->where('category', $category);

        if (!empty($with)) {
            $query->with($with);
        }

        return $query->get();
    }

    /**
     * Get products by type
     */
    public function getByType(string $type, array $with = [])
    {
        $query = $this->model->where('type', $type);

        if (!empty($with)) {
            $query->with($with);
        }

        return $query->get();
    }

    /**
     * Get variant products
     */
    public function getVariantProducts(array $with = [])
    {
        $query = $this->model->where('is_variant_product', true);

        if (!empty($with)) {
            $query->with($with);
        }

        return $query->get();
    }

    /**
     * Get products that need reordering
     */
    public function getNeedingReorder(int $branchId = null)
    {
        $query = $this->model
            ->where('track_stock', true)
            ->whereNotNull('reorder_level');

        // We'll need to join with stock ledger to get current balance
        // This is a simplified version - actual implementation would be more complex
        
        return $query->get()->filter(function ($product) use ($branchId) {
            return $product->needsReorder($branchId);
        });
    }

    /**
     * Get low stock products
     */
    public function getLowStock(int $branchId = null)
    {
        $query = $this->model
            ->where('track_stock', true)
            ->whereNotNull('min_stock_level');

        return $query->get()->filter(function ($product) use ($branchId) {
            $balance = $product->getStockBalance($branchId);
            return $product->min_stock_level && $balance <= $product->min_stock_level;
        });
    }

    /**
     * Search products
     */
    public function search(string $term, array $filters = [], array $with = [])
    {
        $query = $this->model->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
              ->orWhere('sku', 'like', "%{$term}%")
              ->orWhere('barcode', 'like', "%{$term}%")
              ->orWhere('description', 'like', "%{$term}%");
        });

        // Apply filters
        if (isset($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['brand'])) {
            $query->where('brand', $filters['brand']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (!empty($with)) {
            $query->with($with);
        }

        return $query->get();
    }

    /**
     * Get products with stock balance
     */
    public function getWithStockBalance(int $branchId = null, array $with = [])
    {
        $query = $this->model->where('track_stock', true);

        if (!empty($with)) {
            $query->with($with);
        }

        $products = $query->get();

        return $products->map(function ($product) use ($branchId) {
            $product->stock_balance = $product->getStockBalance($branchId);
            return $product;
        });
    }
}
