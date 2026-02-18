<?php

declare(strict_types=1);

namespace Modules\Inventory\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Modules\Inventory\Models\Product;

/**
 * Product Service Contract
 *
 * Defines operations for product management in the Inventory module.
 * Ensures consistent interface for product-related business logic.
 *
 * @package Modules\Inventory\Contracts
 */
interface ProductServiceContract
{
    /**
     * Create new product
     *
     * @param array<string, mixed> $data
     * @return Product
     * @throws \Illuminate\Validation\ValidationException
     */
    public function create(array $data): Product;

    /**
     * Update existing product
     *
     * @param int|string $id
     * @param array<string, mixed> $data
     * @return Product
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function update($id, array $data): Product;

    /**
     * Delete product
     *
     * @param int|string $id
     * @return bool
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function delete($id): bool;

    /**
     * Find product by ID
     *
     * @param int|string $id
     * @return Product|null
     */
    public function find($id): ?Product;

    /**
     * Get paginated product list
     *
     * @param array<string, mixed> $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Find product by SKU
     *
     * @param string $sku
     * @return Product|null
     */
    public function findBySku(string $sku): ?Product;

    /**
     * Find product by barcode
     *
     * @param string $barcode
     * @return Product|null
     */
    public function findByBarcode(string $barcode): ?Product;

    /**
     * Create product variant
     *
     * @param int $productId
     * @param array<string, mixed> $variantData
     * @return mixed Product variant model
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function createVariant(int $productId, array $variantData);

    /**
     * Update product variant
     *
     * @param int $productId
     * @param int $variantId
     * @param array<string, mixed> $variantData
     * @return mixed Updated product variant model
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function updateVariant(int $productId, int $variantId, array $variantData);

    /**
     * Delete product variant
     *
     * @param int $productId
     * @param int $variantId
     * @return bool
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function deleteVariant(int $productId, int $variantId): bool;

    /**
     * Get products by category
     *
     * @param int $categoryId
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getByCategory(int $categoryId, int $perPage = 15): LengthAwarePaginator;

    /**
     * Search products by name or SKU
     *
     * @param string $query
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function search(string $query, int $perPage = 15): LengthAwarePaginator;

    /**
     * Get products with expiring batches
     *
     * @param int $daysAhead
     * @return Collection
     */
    public function getProductsWithExpiringBatches(int $daysAhead = 30): Collection;

    /**
     * Activate product
     *
     * @param int|string $id
     * @return Product
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function activate($id): Product;

    /**
     * Deactivate product
     *
     * @param int|string $id
     * @return Product
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function deactivate($id): Product;
}
