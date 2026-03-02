<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Modules\Core\Domain\Contracts\RepositoryContract;

/**
 * Inventory repository contract.
 *
 * Extends the base CRUD contract with inventory-specific query methods.
 */
interface InventoryRepositoryContract extends RepositoryContract
{
    /**
     * Find all stock items for a given product (tenant-scoped).
     */
    public function findByProduct(int $productId): Collection;

    /**
     * Find all stock items for a given warehouse (tenant-scoped).
     */
    public function findByWarehouse(int $warehouseId): Collection;

    /**
     * Find stock items for a product in a warehouse ordered by FEFO
     * (First-Expired, First-Out) — expiry_date ASC, excluding zero-available stock.
     *
     * Mandatory for pharmaceutical compliance mode.
     */
    public function findByFEFO(int $productId, int $warehouseId): Collection;

    /**
     * Delete a stock reservation by ID.
     */
    public function deleteReservation(int|string $id): bool;

    /**
     * Return paginated stock transactions for a product.
     */
    public function paginateTransactions(int $productId, int $perPage = 15): \Illuminate\Contracts\Pagination\LengthAwarePaginator;

    /**
     * Return a paginated list of all stock items (tenant-scoped).
     */
    public function paginateStockItems(int $perPage = 15): \Illuminate\Contracts\Pagination\LengthAwarePaginator;

    /**
     * Find stock items for a product in a warehouse ordered for FIFO deduction
     * (oldest batch first — ordered by created_at ASC).
     * Only includes items with available quantity > 0.
     */
    public function findByFIFO(int $productId, int $warehouseId): Collection;

    /**
     * Find stock items for a product in a warehouse ordered for LIFO deduction
     * (newest batch first — ordered by created_at DESC).
     * Only includes items with available quantity > 0.
     */
    public function findByLIFO(int $productId, int $warehouseId): Collection;

    /**
     * Find a single stock item (batch) by its primary key.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findStockItemById(int $id): \Modules\Inventory\Domain\Entities\StockItem;

    /**
     * Update a stock item record by primary key.
     *
     * @param array<string, mixed> $data
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function updateStockItem(int $id, array $data): \Modules\Inventory\Domain\Entities\StockItem;

    /**
     * Delete a stock item (batch) by primary key.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function deleteStockItem(int $id): bool;
}
