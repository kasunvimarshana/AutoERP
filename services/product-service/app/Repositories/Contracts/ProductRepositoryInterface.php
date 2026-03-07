<?php

namespace App\Repositories\Contracts;

interface ProductRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Find a product by its SKU within the current tenant scope.
     */
    public function findBySku(string $sku): ?\Illuminate\Database\Eloquent\Model;

    /**
     * Search products by name or SKU using a LIKE query.
     */
    public function searchByNameOrSku(string $term): \Illuminate\Database\Eloquent\Collection;

    /**
     * Retrieve all products belonging to a specific category.
     */
    public function findByCategory(int|string $categoryId): \Illuminate\Database\Eloquent\Collection;

    /**
     * Retrieve all products with their category eagerly loaded.
     */
    public function getWithCategory(): \Illuminate\Database\Eloquent\Collection;

    /**
     * Retrieve products whose current stock is below the reorder point.
     * Delegates to the inventory service for stock level data when needed.
     */
    public function getLowStock(): \Illuminate\Database\Eloquent\Collection;

    /**
     * Retrieve all products belonging to a specific tenant.
     */
    public function getByTenant(int|string $tenantId): \Illuminate\Database\Eloquent\Collection;

    /**
     * Retrieve products by an array of primary-key IDs (cross-service batch fetch).
     *
     * @param  array<int|string>  $ids
     */
    public function findByIds(array $ids): \Illuminate\Database\Eloquent\Collection;
}
