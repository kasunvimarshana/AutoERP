<?php

declare(strict_types=1);

namespace App\Domain\Inventory\Contracts;

use App\Infrastructure\Repositories\BaseRepository;
use Illuminate\Support\Collection;

/**
 * Contract for the Inventory / Product repository.
 *
 * Extends the capabilities of BaseRepository with inventory-specific
 * query methods that all concrete implementations must satisfy.
 */
interface InventoryRepositoryInterface
{
    /**
     * Retrieve all products with optional filters, search, sort,
     * and conditional pagination.
     */
    public function all(array $filters = []): mixed;

    /** Find a product by its primary key or throw a ModelNotFoundException. */
    public function findOrFail(int|string $id): \Illuminate\Database\Eloquent\Model;

    /** Find a product by its primary key, or return null. */
    public function find(int|string $id): ?\Illuminate\Database\Eloquent\Model;

    /** Create a new product record. */
    public function create(array $attributes): \Illuminate\Database\Eloquent\Model;

    /** Update an existing product record. */
    public function update(int|string $id, array $attributes): \Illuminate\Database\Eloquent\Model;

    /** Soft-delete a product. */
    public function delete(int|string $id): bool;

    /**
     * Find a product by its unique SKU within the current tenant scope.
     */
    public function findBySku(string $sku): ?\Illuminate\Database\Eloquent\Model;

    /**
     * Adjust the stock level for a product by a signed delta.
     *
     * A positive delta increases stock; a negative delta decreases it.
     * Returns the updated product model.
     *
     * @throws \App\Exceptions\InsufficientStockException When resulting stock < 0.
     */
    public function adjustStock(int|string $productId, int $delta): \Illuminate\Database\Eloquent\Model;

    /**
     * Return all products whose stock quantity is at or below their reorder point.
     */
    public function findLowStock(): Collection;

    /**
     * Return all products belonging to the given category.
     */
    public function findByCategory(string $category): Collection;

    /**
     * Bulk-update the stock levels for multiple products in a single transaction.
     *
     * @param array<int|string, int> $stockMap  Map of product_id => new_stock_quantity.
     */
    public function bulkUpdateStock(array $stockMap): bool;
}
