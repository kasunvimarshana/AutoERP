<?php

declare(strict_types=1);

namespace Modules\Inventory\Repositories;

use Illuminate\Support\Collection;
use Modules\Core\Repositories\BaseRepository;
use Modules\Inventory\Models\Batch;

/**
 * Batch Repository
 *
 * Data access layer for batch management.
 */
class BatchRepository extends BaseRepository
{
    /**
     * Get the model class
     *
     * @return string
     */
    protected function getModelClass(): string
    {
        return Batch::class;
    }

    /**
     * Find available batches for a product
     *
     * @param string $productId
     * @param string|null $variantId
     * @param string $strategy 'fefo' or 'fifo'
     * @return Collection<Batch>
     */
    public function findAvailableByProduct(
        string $productId,
        ?string $variantId = null,
        string $strategy = 'fefo'
    ): Collection {
        $query = $this->model->newQuery()
            ->where('product_id', $productId)
            ->available()
            ->nonExpired();

        if ($variantId) {
            $query->where('variant_id', $variantId);
        }

        // Apply sorting strategy
        if ($strategy === 'fefo') {
            // First Expired, First Out - prioritize batches expiring soonest
            $query->orderByRaw('COALESCE(expiry_date, \'9999-12-31\') ASC');
        } else {
            // First In, First Out - prioritize oldest batches
            $query->orderBy('created_at', 'ASC');
        }

        return $query->get();
    }

    /**
     * Find batches near expiry
     *
     * @param int $days
     * @return Collection<Batch>
     */
    public function findNearExpiry(int $days = 30): Collection
    {
        return $this->model->newQuery()
            ->nearExpiry($days)
            ->available()
            ->orderBy('expiry_date', 'ASC')
            ->get();
    }

    /**
     * Find expired batches
     *
     * @return Collection<Batch>
     */
    public function findExpired(): Collection
    {
        return $this->model->newQuery()
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '<', now())
            ->orderBy('expiry_date', 'ASC')
            ->get();
    }

    /**
     * Find batches by product
     *
     * @param string $productId
     * @return Collection<Batch>
     */
    public function findByProduct(string $productId): Collection
    {
        return $this->model->newQuery()
            ->where('product_id', $productId)
            ->orderBy('created_at', 'DESC')
            ->get();
    }

    /**
     * Get next sequence number for batch number generation
     *
     * @return int
     */
    public function getNextSequence(): int
    {
        $today = now()->format('Y-m-d');

        $lastBatch = $this->model->newQuery()
            ->whereDate('created_at', $today)
            ->orderBy('created_at', 'DESC')
            ->first();

        if (!$lastBatch) {
            return 1;
        }

        // Extract sequence from batch number (format: BTH-YYYYMMDD-0001)
        $parts = explode('-', $lastBatch->batch_number);
        $sequence = isset($parts[2]) ? intval($parts[2]) : 0;

        return $sequence + 1;
    }

    /**
     * Find batch by batch number
     *
     * @param string $batchNumber
     * @return Batch|null
     */
    public function findByBatchNumber(string $batchNumber): ?Batch
    {
        return $this->model->newQuery()
            ->where('batch_number', $batchNumber)
            ->first();
    }

    /**
     * Get total available quantity for a product across all batches
     *
     * @param string $productId
     * @param string|null $variantId
     * @return float
     */
    public function getTotalAvailableQuantity(string $productId, ?string $variantId = null): float
    {
        $query = $this->model->newQuery()
            ->where('product_id', $productId)
            ->available()
            ->nonExpired();

        if ($variantId) {
            $query->where('variant_id', $variantId);
        }

        return (float) $query->sum('available_quantity');
    }
}
