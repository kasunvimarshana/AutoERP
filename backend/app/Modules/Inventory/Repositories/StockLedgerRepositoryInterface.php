<?php

namespace App\Modules\Inventory\Repositories;

use App\Modules\Inventory\Models\StockLedger;
use Illuminate\Database\Eloquent\Collection;

interface StockLedgerRepositoryInterface
{
    /**
     * Record stock movement
     *
     * @param array $data
     * @return StockLedger
     */
    public function recordMovement(array $data): StockLedger;

    /**
     * Get current balance for a product
     *
     * @param int $productId
     * @param int|null $branchId
     * @param int|null $locationId
     * @param int|null $variantId
     * @return float
     */
    public function getCurrentBalance(
        int $productId,
        ?int $branchId = null,
        ?int $locationId = null,
        ?int $variantId = null
    ): float;

    /**
     * Get stock movements for a product
     *
     * @param int $productId
     * @param \DateTime $startDate
     * @param \DateTime $endDate
     * @param int|null $branchId
     * @return Collection
     */
    public function getMovements(
        int $productId,
        \DateTime $startDate,
        \DateTime $endDate,
        ?int $branchId = null
    ): Collection;

    /**
     * Get expiring items
     *
     * @param int $daysThreshold
     * @param int|null $branchId
     * @return Collection
     */
    public function getExpiringItems(int $daysThreshold = 30, ?int $branchId = null): Collection;

    /**
     * Get stock by batch
     *
     * @param string $batchNumber
     * @return Collection
     */
    public function getByBatch(string $batchNumber): Collection;

    /**
     * Get stock by serial
     *
     * @param string $serialNumber
     * @return Collection
     */
    public function getBySerial(string $serialNumber): Collection;

    /**
     * Calculate average cost
     *
     * @param int $productId
     * @param int|null $branchId
     * @return float
     */
    public function calculateAverageCost(int $productId, ?int $branchId = null): float;

    /**
     * Get stock by location
     *
     * @param int $locationId
     * @return Collection
     */
    public function getByLocation(int $locationId): Collection;

    /**
     * Get FIFO batches for a product
     *
     * @param int $productId
     * @param int|null $branchId
     * @return Collection
     */
    public function getFIFOBatches(int $productId, ?int $branchId = null): Collection;
}
