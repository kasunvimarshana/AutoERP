<?php

namespace App\Contracts\Services;

use App\Models\StockMovement;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface InventoryServiceInterface
{
    public function paginateStock(string $tenantId, array $filters = [], int $perPage = 15): LengthAwarePaginator;

    public function adjust(
        string $tenantId,
        string $warehouseId,
        string $productId,
        string $quantity,
        string $movementType,
        ?string $variantId = null,
        ?string $notes = null,
        ?string $referenceType = null,
        ?string $referenceId = null,
        ?string $batchNumber = null,
        ?string $lotNumber = null,
        ?string $serialNumber = null,
        ?\DateTimeInterface $expiryDate = null,
        string $valuationMethod = 'fifo'
    ): StockMovement;

    /**
     * Return stock items below their reorder point for the given tenant.
     *
     * @return Collection<int, \App\Models\StockItem>
     */
    public function getLowStockItems(string $tenantId, ?string $warehouseId = null): Collection;

    /**
     * Return stock batches expiring on or before $daysAhead days from today.
     *
     * @return Collection<int, \App\Models\StockBatch>
     */
    public function getExpiringBatches(string $tenantId, int $daysAhead = 30, ?string $warehouseId = null): Collection;

    /**
     * Calculate the weighted-average cost of a stock item using FIFO cost layers.
     */
    public function getFifoCost(string $tenantId, string $warehouseId, string $productId, ?string $variantId = null): string;
}
