<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\InventoryItem;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Repository contract for inventory-item persistence.
 *
 * Decouples the storage layer from business logic,
 * making it trivial to substitute PostgreSQL for an
 * in-memory store in tests or switch to a different ORM.
 */
interface InventoryRepositoryInterface
{
    /**
     * List all inventory items for a given tenant.
     *
     * @return LengthAwarePaginator<InventoryItem>
     */
    public function listForTenant(string $tenantId, int $perPage = 15): LengthAwarePaginator;

    /**
     * Find a specific inventory item by its UUID.
     */
    public function findById(string $id): ?InventoryItem;

    /**
     * Find an inventory item by product ID within a tenant.
     */
    public function findByProductId(string $productId, string $tenantId): ?InventoryItem;

    /**
     * Create a new inventory record.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): InventoryItem;

    /**
     * Update an existing inventory record.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(InventoryItem $item, array $data): InventoryItem;

    /**
     * Hard-delete an inventory record.
     */
    public function delete(InventoryItem $item): void;

    /**
     * Atomically reserve a quantity of stock.
     * Returns false if insufficient stock.
     */
    public function reserveStock(string $productId, string $tenantId, int $quantity): bool;

    /**
     * Release (unreserve) previously reserved stock.
     * Used as a compensating transaction in Saga rollback.
     */
    public function releaseStock(string $productId, string $tenantId, int $quantity): bool;
}
