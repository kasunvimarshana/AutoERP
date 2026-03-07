<?php

namespace App\Repositories\Interfaces;

use App\Models\InventoryItem;
use Illuminate\Pagination\LengthAwarePaginator;

interface InventoryRepositoryInterface
{
    public function allForTenant(int $tenantId, array $filters = [], int $perPage = 15): LengthAwarePaginator;

    public function findById(int $id, int $tenantId): ?InventoryItem;

    public function findByProductAndWarehouse(int $productId, int $warehouseId, int $tenantId): ?InventoryItem;

    public function create(array $data): InventoryItem;

    public function update(InventoryItem $item, array $data): InventoryItem;

    public function delete(InventoryItem $item): bool;

    /** Increment quantity atomically. Returns updated item. */
    public function incrementQuantity(InventoryItem $item, int $amount): InventoryItem;

    /** Decrement quantity atomically. Returns updated item. */
    public function decrementQuantity(InventoryItem $item, int $amount): InventoryItem;

    /** Set quantity to an absolute value atomically. Returns updated item. */
    public function setQuantity(InventoryItem $item, int $quantity): InventoryItem;

    /** Increment reserved_quantity atomically. Returns updated item. */
    public function incrementReserved(InventoryItem $item, int $amount): InventoryItem;

    /** Decrement reserved_quantity atomically. Returns updated item. */
    public function decrementReserved(InventoryItem $item, int $amount): InventoryItem;

    /** Retrieve all items at or below their reorder_point for a tenant. */
    public function getLowStockItems(int $tenantId): \Illuminate\Database\Eloquent\Collection;

    /** Soft-delete all inventory items for a given product (used in saga compensation). */
    public function deleteByProduct(int $productId, int $tenantId): int;
}
