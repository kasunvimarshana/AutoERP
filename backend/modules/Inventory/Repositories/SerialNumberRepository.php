<?php

declare(strict_types=1);

namespace Modules\Inventory\Repositories;

use Illuminate\Support\Collection;
use Modules\Core\Repositories\BaseRepository;
use Modules\Inventory\Models\SerialNumber;
use Modules\Inventory\Enums\SerialNumberStatus;

/**
 * Serial Number Repository
 *
 * Data access layer for serial number management.
 */
class SerialNumberRepository extends BaseRepository
{
    /**
     * Get the model class
     *
     * @return string
     */
    protected function getModelClass(): string
    {
        return SerialNumber::class;
    }

    /**
     * Find available serial numbers for a product
     *
     * @param string $productId
     * @param string|null $variantId
     * @param string|null $warehouseId
     * @return Collection<SerialNumber>
     */
    public function findAvailableByProduct(
        string $productId,
        ?string $variantId = null,
        ?string $warehouseId = null
    ): Collection {
        $query = $this->model->newQuery()
            ->where('product_id', $productId)
            ->available();

        if ($variantId) {
            $query->where('variant_id', $variantId);
        }

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        return $query->orderBy('created_at', 'ASC')->get();
    }

    /**
     * Find serial number by serial number string
     *
     * @param string $serialNumber
     * @return SerialNumber|null
     */
    public function findBySerialNumber(string $serialNumber): ?SerialNumber
    {
        return $this->model->newQuery()
            ->where('serial_number', $serialNumber)
            ->first();
    }

    /**
     * Check if serial number exists
     *
     * @param string $serialNumber
     * @return bool
     */
    public function existsBySerialNumber(string $serialNumber): bool
    {
        return $this->model->newQuery()
            ->where('serial_number', $serialNumber)
            ->exists();
    }

    /**
     * Find serial numbers by product
     *
     * @param string $productId
     * @return Collection<SerialNumber>
     */
    public function findByProduct(string $productId): Collection
    {
        return $this->model->newQuery()
            ->where('product_id', $productId)
            ->orderBy('created_at', 'DESC')
            ->get();
    }

    /**
     * Find serial numbers with expiring warranty
     *
     * @param int $days
     * @return Collection<SerialNumber>
     */
    public function findWithExpiringWarranty(int $days = 30): Collection
    {
        return $this->model->newQuery()
            ->whereNotNull('warranty_end_date')
            ->whereBetween('warranty_end_date', [now(), now()->addDays($days)])
            ->where(function ($q) {
                $q->whereNull('warranty_start_date')
                    ->orWhere('warranty_start_date', '<=', now());
            })
            ->orderBy('warranty_end_date', 'ASC')
            ->get();
    }

    /**
     * Find serial numbers by batch
     *
     * @param string $batchId
     * @return Collection<SerialNumber>
     */
    public function findByBatch(string $batchId): Collection
    {
        return $this->model->newQuery()
            ->where('batch_id', $batchId)
            ->orderBy('created_at', 'DESC')
            ->get();
    }

    /**
     * Find serial numbers by status
     *
     * @param SerialNumberStatus $status
     * @return Collection<SerialNumber>
     */
    public function findByStatus(SerialNumberStatus $status): Collection
    {
        return $this->model->newQuery()
            ->where('status', $status)
            ->orderBy('created_at', 'DESC')
            ->get();
    }

    /**
     * Find sold serial numbers for a customer
     *
     * @param string $customerId
     * @return Collection<SerialNumber>
     */
    public function findByCustomer(string $customerId): Collection
    {
        return $this->model->newQuery()
            ->where('customer_id', $customerId)
            ->where('status', SerialNumberStatus::SOLD)
            ->orderBy('sale_date', 'DESC')
            ->get();
    }

    /**
     * Count available serial numbers for a product
     *
     * @param string $productId
     * @param string|null $variantId
     * @param string|null $warehouseId
     * @return int
     */
    public function countAvailableByProduct(
        string $productId,
        ?string $variantId = null,
        ?string $warehouseId = null
    ): int {
        $query = $this->model->newQuery()
            ->where('product_id', $productId)
            ->available();

        if ($variantId) {
            $query->where('variant_id', $variantId);
        }

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        return $query->count();
    }
}
