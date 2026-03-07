<?php

declare(strict_types=1);

namespace App\Repositories\Interfaces;

use App\Models\Inventory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface InventoryRepositoryInterface
{
    /**
     * Retrieve a paginated list of inventory items with optional filters.
     *
     * @param  array<string, mixed> $filters
     */
    public function getAll(array $filters = []): LengthAwarePaginator;

    /**
     * Find an inventory record by its primary key.
     */
    public function findById(int $id): ?Inventory;

    /**
     * Find inventory record(s) by product ID.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Inventory>
     */
    public function findByProductId(int $productId): \Illuminate\Database\Eloquent\Collection;

    /**
     * Find the first inventory record for a product ID, or null.
     */
    public function findFirstByProductId(int $productId): ?Inventory;

    /**
     * Create a new inventory record.
     *
     * @param  array<string, mixed> $data
     */
    public function create(array $data): Inventory;

    /**
     * Update an existing inventory record.
     *
     * @param  array<string, mixed> $data
     */
    public function update(int $id, array $data): ?Inventory;

    /**
     * Soft-delete an inventory record.
     */
    public function delete(int $id): bool;

    /**
     * Soft-delete all inventory records for a given product.
     */
    public function deleteByProductId(int $productId): int;

    /**
     * Pessimistic lock on inventory row for ACID stock operations.
     */
    public function lockForUpdate(int $id): ?Inventory;
}
