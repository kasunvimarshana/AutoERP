<?php

namespace App\Modules\Product\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Modules\Product\Models\Product;
use Illuminate\Database\Eloquent\Collection;

class ProductRepository extends BaseRepository implements ProductRepositoryInterface
{
    /**
     * ProductRepository constructor.
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
     * Find product by slug
     *
     * @param string $slug
     * @return Product|null
     */
    public function findBySlug(string $slug): ?Product
    {
        return $this->model->where('slug', $slug)->first();
    }

    /**
     * Get active products
     *
     * @return Collection
     */
    public function getActiveProducts(): Collection
    {
        return $this->model->where('is_active', true)->get();
    }

    /**
     * Get products by category
     *
     * @param int $categoryId
     * @return Collection
     */
    public function getByCategory(int $categoryId): Collection
    {
        return $this->model->where('category_id', $categoryId)->get();
    }

    /**
     * Get products by type
     *
     * @param string $type
     * @return Collection
     */
    public function getByType(string $type): Collection
    {
        return $this->model->where('type', $type)->get();
    }

    /**
     * Search products
     *
     * @param string $query
     * @param array $filters
     * @return Collection
     */
    public function search(string $query, array $filters = []): Collection
    {
        $queryBuilder = $this->model->query();

        // Search by name, SKU, or description
        if (!empty($query)) {
            $queryBuilder->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('sku', 'like', "%{$query}%")
                  ->orWhere('description', 'like', "%{$query}%")
                  ->orWhere('barcode', 'like', "%{$query}%");
            });
        }

        // Apply filters
        if (isset($filters['category_id'])) {
            $queryBuilder->where('category_id', $filters['category_id']);
        }

        if (isset($filters['type'])) {
            $queryBuilder->where('type', $filters['type']);
        }

        if (isset($filters['is_active'])) {
            $queryBuilder->where('is_active', $filters['is_active']);
        }

        if (isset($filters['min_price'])) {
            $queryBuilder->where('selling_price', '>=', $filters['min_price']);
        }

        if (isset($filters['max_price'])) {
            $queryBuilder->where('selling_price', '<=', $filters['max_price']);
        }

        return $queryBuilder->get();
    }

    /**
     * Get low stock products
     *
     * @return Collection
     */
    public function getLowStockProducts(): Collection
    {
        return $this->model
            ->where('track_inventory', true)
            ->whereColumn('reorder_level', '>', 'min_stock_level')
            ->get();
    }

    /**
     * Get out of stock products
     *
     * @return Collection
     */
    public function getOutOfStockProducts(): Collection
    {
        return $this->model
            ->where('track_inventory', true)
            ->where('min_stock_level', '<=', 0)
            ->get();
    }

    /**
     * Get products with expiring items
     *
     * @param int $daysThreshold
     * @return Collection
     */
    public function getProductsWithExpiringItems(int $daysThreshold = 30): Collection
    {
        return $this->model
            ->where('has_expiry', true)
            ->where('expiry_alert_days', '<=', $daysThreshold)
            ->get();
    }
}
