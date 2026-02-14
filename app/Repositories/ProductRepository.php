<?php

namespace App\Repositories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;

/**
 * Product Repository
 * 
 * Handles data access for Product model.
 * Demonstrates how to extend BaseRepository with custom methods.
 */
class ProductRepository extends BaseRepository
{
    /**
     * Constructor
     *
     * @param Product $model
     */
    public function __construct(Product $model)
    {
        parent::__construct($model);
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
     * Find product by SKU or fail
     *
     * @param string $sku
     * @return Product
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findBySkuOrFail(string $sku): Product
    {
        return $this->model->where('sku', $sku)->firstOrFail();
    }

    /**
     * Get active products
     *
     * @param array $config
     * @return Collection
     */
    public function getActive(array $config = []): Collection
    {
        $this->resetQuery();
        $this->query->where('status', 'active');
        $this->applyConfig($config);
        
        return $this->query->get();
    }

    /**
     * Get products by category
     *
     * @param int $categoryId
     * @param array $config
     * @return Collection
     */
    public function getByCategory(int $categoryId, array $config = []): Collection
    {
        $this->resetQuery();
        $this->query->where('category_id', $categoryId);
        $this->applyConfig($config);
        
        return $this->query->get();
    }

    /**
     * Get low stock products
     *
     * @param int $threshold
     * @return Collection
     */
    public function getLowStock(int $threshold = 10): Collection
    {
        return $this->model
            ->whereHas('inventoryItems', function ($query) use ($threshold) {
                $query->where('quantity', '<=', $threshold);
            })
            ->with('inventoryItems')
            ->get();
    }

    /**
     * Check if SKU exists
     *
     * @param string $sku
     * @param int|null $excludeId
     * @return bool
     */
    public function skuExists(string $sku, ?int $excludeId = null): bool
    {
        return $this->exists('sku', $sku, $excludeId);
    }
}
