<?php

namespace App\Repositories;

use App\Models\Product;
use App\Repositories\Contracts\ProductRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class ProductRepository extends BaseRepository implements ProductRepositoryInterface
{
    public function __construct(Product $model)
    {
        parent::__construct($model);
    }

    /**
     * Find a product by its SKU.
     */
    public function findBySku(string $sku): ?Model
    {
        return $this->newQuery()->where('sku', $sku)->first();
    }

    /**
     * Search products by name or SKU using the BaseRepository search helper.
     */
    public function searchByNameOrSku(string $term): Collection
    {
        return $this->search($term, ['name', 'sku']);
    }

    /**
     * Retrieve all products belonging to a specific category.
     */
    public function findByCategory(int|string $categoryId): Collection
    {
        return $this->newQuery()->where('category_id', $categoryId)->get();
    }

    /**
     * Retrieve all products with their category eagerly loaded.
     */
    public function getWithCategory(): Collection
    {
        return $this->newQuery()->with('category')->get();
    }

    /**
     * Retrieve products whose reorder_point is set and whose min_stock_level
     * is below the reorder_point threshold (products needing restock attention).
     *
     * Note: Actual real-time stock quantities live in the Inventory Service.
     * This method returns products flagged as low-stock based on configuration
     * fields stored in the Product model itself.
     */
    public function getLowStock(): Collection
    {
        return $this->newQuery()
            ->whereNotNull('reorder_point')
            ->whereNotNull('min_stock_level')
            ->whereColumn('min_stock_level', '<=', 'reorder_point')
            ->where('is_active', true)
            ->with('category')
            ->get();
    }

    /**
     * Retrieve all products belonging to a specific tenant.
     */
    public function getByTenant(int|string $tenantId): Collection
    {
        return $this->newQuery()->where('tenant_id', $tenantId)->get();
    }

    /**
     * Retrieve products by an array of primary-key IDs (for cross-service batch calls).
     *
     * @param  array<int|string>  $ids
     */
    public function findByIds(array $ids): Collection
    {
        return $this->newQuery()->whereIn('id', $ids)->with('category')->get();
    }
}
