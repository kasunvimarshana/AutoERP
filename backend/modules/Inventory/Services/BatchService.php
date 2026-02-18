<?php

declare(strict_types=1);

namespace Modules\Inventory\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Core\Services\BaseService;
use Modules\Inventory\Models\Batch;
use Modules\Inventory\Models\Product;
use Modules\Inventory\Repositories\BatchRepository;
use Modules\Inventory\Exceptions\InsufficientStockException;

/**
 * Batch Service
 *
 * Business logic for batch tracking and management.
 * Handles batch creation, expiry tracking, and FEFO (First Expired, First Out) logic.
 */
class BatchService extends BaseService
{
    public function __construct(
        protected BatchRepository $batchRepository
    ) {
        parent::__construct($batchRepository);
    }

    /**
     * Create a new batch
     *
     * @param array $data
     * @return Batch
     */
    public function createBatch(array $data): Batch
    {
        return DB::transaction(function () use ($data) {
            // Generate batch number if not provided
            if (empty($data['batch_number'])) {
                $data['batch_number'] = $this->generateBatchNumber();
            }

            // Set received quantity as available initially
            if (!isset($data['available_quantity'])) {
                $data['available_quantity'] = $data['received_quantity'] ?? 0;
            }

            return $this->batchRepository->create($data);
        });
    }

    /**
     * Allocate quantity from batch
     *
     * @param Batch $batch
     * @param float $quantity
     * @return Batch
     * @throws InsufficientStockException
     */
    public function allocateFromBatch(Batch $batch, float $quantity): Batch
    {
        if ($batch->available_quantity < $quantity) {
            throw new InsufficientStockException(
                "Insufficient quantity in batch {$batch->batch_number}. " .
                "Available: {$batch->available_quantity}, Requested: {$quantity}"
            );
        }

        return DB::transaction(function () use ($batch, $quantity) {
            $batch->available_quantity -= $quantity;
            $batch->save();
            return $batch->fresh();
        });
    }

    /**
     * Return quantity to batch
     *
     * @param Batch $batch
     * @param float $quantity
     * @return Batch
     */
    public function returnToBatch(Batch $batch, float $quantity): Batch
    {
        return DB::transaction(function () use ($batch, $quantity) {
            $batch->available_quantity += $quantity;
            $batch->save();
            return $batch->fresh();
        });
    }

    /**
     * Get available batches for a product using FEFO strategy
     *
     * @param string $productId
     * @param string|null $variantId
     * @return Collection<Batch>
     */
    public function getAvailableBatches(string $productId, ?string $variantId = null): Collection
    {
        return $this->batchRepository->findAvailableByProduct(
            $productId,
            $variantId,
            'fefo' // First Expired, First Out
        );
    }

    /**
     * Get batches near expiry
     *
     * @param int $days
     * @return Collection<Batch>
     */
    public function getBatchesNearExpiry(int $days = 30): Collection
    {
        return $this->batchRepository->findNearExpiry($days);
    }

    /**
     * Get expired batches
     *
     * @return Collection<Batch>
     */
    public function getExpiredBatches(): Collection
    {
        return $this->batchRepository->findExpired();
    }

    /**
     * Allocate quantity using FEFO strategy
     *
     * @param string $productId
     * @param float $quantity
     * @param string|null $variantId
     * @return array Array of ['batch_id' => allocated_quantity]
     * @throws InsufficientStockException
     */
    public function allocateQuantityFEFO(
        string $productId,
        float $quantity,
        ?string $variantId = null
    ): array {
        $batches = $this->getAvailableBatches($productId, $variantId);
        $remainingQuantity = $quantity;
        $allocations = [];

        foreach ($batches as $batch) {
            if ($remainingQuantity <= 0) {
                break;
            }

            $allocatedQty = min($batch->available_quantity, $remainingQuantity);
            $this->allocateFromBatch($batch, $allocatedQty);

            $allocations[$batch->id] = $allocatedQty;
            $remainingQuantity -= $allocatedQty;
        }

        if ($remainingQuantity > 0) {
            throw new InsufficientStockException(
                "Insufficient stock for product {$productId}. " .
                "Requested: {$quantity}, Available: " . ($quantity - $remainingQuantity)
            );
        }

        return $allocations;
    }

    /**
     * Generate unique batch number
     *
     * @return string
     */
    protected function generateBatchNumber(): string
    {
        $prefix = 'BTH';
        $date = now()->format('Ymd');
        $sequence = $this->batchRepository->getNextSequence();

        return sprintf('%s-%s-%04d', $prefix, $date, $sequence);
    }

    /**
     * Get batch history for a product
     *
     * @param string $productId
     * @return Collection<Batch>
     */
    public function getBatchHistory(string $productId): Collection
    {
        return $this->batchRepository->findByProduct($productId);
    }

    /**
     * Check if batch is expiring soon
     *
     * @param Batch $batch
     * @param int $days
     * @return bool
     */
    public function isBatchExpiringSoon(Batch $batch, int $days = 30): bool
    {
        return $batch->isNearExpiry($days);
    }
}
