<?php

namespace App\Modules\Product\Repositories;

use App\Modules\Product\Models\Product;
use Illuminate\Database\Eloquent\Collection;

interface ProductRepositoryInterface
{
    /**
     * Find product by SKU
     *
     * @param string $sku
     * @return Product|null
     */
    public function findBySku(string $sku): ?Product;

    /**
     * Find product by slug
     *
     * @param string $slug
     * @return Product|null
     */
    public function findBySlug(string $slug): ?Product;

    /**
     * Get active products
     *
     * @return Collection
     */
    public function getActiveProducts(): Collection;

    /**
     * Get products by category
     *
     * @param int $categoryId
     * @return Collection
     */
    public function getByCategory(int $categoryId): Collection;

    /**
     * Get products by type
     *
     * @param string $type
     * @return Collection
     */
    public function getByType(string $type): Collection;

    /**
     * Search products
     *
     * @param string $query
     * @param array $filters
     * @return Collection
     */
    public function search(string $query, array $filters = []): Collection;

    /**
     * Get low stock products
     *
     * @return Collection
     */
    public function getLowStockProducts(): Collection;

    /**
     * Get out of stock products
     *
     * @return Collection
     */
    public function getOutOfStockProducts(): Collection;

    /**
     * Get products with expiring items
     *
     * @param int $daysThreshold
     * @return Collection
     */
    public function getProductsWithExpiringItems(int $daysThreshold = 30): Collection;
}
