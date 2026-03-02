<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Contracts;

use Modules\Inventory\Domain\Entities\StockLedgerEntry;

interface InventoryRepositoryInterface
{
    /**
     * Returns the current stock level for a product in a warehouse using BCMath sum.
     */
    public function getStockLevel(int $productId, int $warehouseId, int $tenantId): string;

    /**
     * Records an immutable stock ledger entry.
     */
    public function recordEntry(StockLedgerEntry $entry): StockLedgerEntry;

    /**
     * Returns ledger history for a product/warehouse combination.
     *
     * @return StockLedgerEntry[]
     */
    public function getHistory(int $tenantId, int $productId, int $warehouseId, int $page, int $perPage): array;

    /**
     * Returns the current stock using a pessimistic lock (lockForUpdate) for concurrency safety.
     */
    public function getStockLevelForUpdate(int $productId, int $warehouseId, int $tenantId): string;
}
