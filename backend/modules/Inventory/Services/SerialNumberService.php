<?php

declare(strict_types=1);

namespace Modules\Inventory\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Core\Services\BaseService;
use Modules\Inventory\Models\SerialNumber;
use Modules\Inventory\Repositories\SerialNumberRepository;
use Modules\Inventory\Enums\SerialNumberStatus;
use Modules\Inventory\Exceptions\DuplicateSerialNumberException;
use Modules\Inventory\Exceptions\InvalidSerialNumberException;

/**
 * Serial Number Service
 *
 * Business logic for serial number tracking and management.
 * Handles serial number creation, allocation, and warranty tracking.
 */
class SerialNumberService extends BaseService
{
    public function __construct(
        protected SerialNumberRepository $serialNumberRepository
    ) {
        parent::__construct($serialNumberRepository);
    }

    /**
     * Register a new serial number
     *
     * @param array $data
     * @return SerialNumber
     * @throws DuplicateSerialNumberException
     */
    public function registerSerialNumber(array $data): SerialNumber
    {
        // Check for duplicate serial number
        if ($this->serialNumberRepository->existsBySerialNumber($data['serial_number'])) {
            throw new DuplicateSerialNumberException(
                "Serial number {$data['serial_number']} already exists"
            );
        }

        return DB::transaction(function () use ($data) {
            // Set default status if not provided
            if (!isset($data['status'])) {
                $data['status'] = SerialNumberStatus::IN_STOCK;
            }

            return $this->serialNumberRepository->create($data);
        });
    }

    /**
     * Bulk register serial numbers
     *
     * @param array $serialNumbers Array of serial number data
     * @return Collection<SerialNumber>
     */
    public function bulkRegisterSerialNumbers(array $serialNumbers): Collection
    {
        return DB::transaction(function () use ($serialNumbers) {
            $registered = collect();

            foreach ($serialNumbers as $serialData) {
                try {
                    $registered->push($this->registerSerialNumber($serialData));
                } catch (DuplicateSerialNumberException $e) {
                    // Log and continue with next serial number
                    logger()->warning("Skipped duplicate serial number", [
                        'serial_number' => $serialData['serial_number'] ?? 'unknown',
                        'error' => $e->getMessage()
                    ]);
                }
            }

            return $registered;
        });
    }

    /**
     * Allocate serial number for sale
     *
     * @param SerialNumber $serialNumber
     * @param array $saleData
     * @return SerialNumber
     * @throws InvalidSerialNumberException
     */
    public function allocateForSale(SerialNumber $serialNumber, array $saleData): SerialNumber
    {
        if (!$serialNumber->isInStock()) {
            throw new InvalidSerialNumberException(
                "Serial number {$serialNumber->serial_number} is not available for sale. " .
                "Current status: {$serialNumber->status->label()}"
            );
        }

        return DB::transaction(function () use ($serialNumber, $saleData) {
            $serialNumber->update([
                'status' => SerialNumberStatus::SOLD,
                'customer_id' => $saleData['customer_id'] ?? null,
                'sale_order_id' => $saleData['sale_order_id'] ?? null,
                'sale_date' => $saleData['sale_date'] ?? now(),
                'warranty_start_date' => $saleData['warranty_start_date'] ?? now(),
                'warranty_end_date' => $saleData['warranty_end_date'] ?? null,
            ]);

            return $serialNumber->fresh();
        });
    }

    /**
     * Return serial number to stock
     *
     * @param SerialNumber $serialNumber
     * @param array $returnData
     * @return SerialNumber
     */
    public function returnToStock(SerialNumber $serialNumber, array $returnData): SerialNumber
    {
        return DB::transaction(function () use ($serialNumber, $returnData) {
            $serialNumber->update([
                'status' => SerialNumberStatus::RETURNED,
                'notes' => $returnData['notes'] ?? $serialNumber->notes,
            ]);

            return $serialNumber->fresh();
        });
    }

    /**
     * Mark serial number as defective
     *
     * @param SerialNumber $serialNumber
     * @param string|null $reason
     * @return SerialNumber
     */
    public function markAsDefective(SerialNumber $serialNumber, ?string $reason = null): SerialNumber
    {
        return DB::transaction(function () use ($serialNumber, $reason) {
            $serialNumber->update([
                'status' => SerialNumberStatus::DEFECTIVE,
                'notes' => $reason ? ($serialNumber->notes . "\n" . $reason) : $serialNumber->notes,
            ]);

            return $serialNumber->fresh();
        });
    }

    /**
     * Get available serial numbers for a product
     *
     * @param string $productId
     * @param string|null $variantId
     * @param string|null $warehouseId
     * @return Collection<SerialNumber>
     */
    public function getAvailableSerialNumbers(
        string $productId,
        ?string $variantId = null,
        ?string $warehouseId = null
    ): Collection {
        return $this->serialNumberRepository->findAvailableByProduct(
            $productId,
            $variantId,
            $warehouseId
        );
    }

    /**
     * Get serial numbers with expiring warranties
     *
     * @param int $days
     * @return Collection<SerialNumber>
     */
    public function getSerialNumbersWithExpiringWarranty(int $days = 30): Collection
    {
        return $this->serialNumberRepository->findWithExpiringWarranty($days);
    }

    /**
     * Track serial number movement
     *
     * @param SerialNumber $serialNumber
     * @param string $warehouseId
     * @param string|null $locationId
     * @return SerialNumber
     */
    public function moveSerialNumber(
        SerialNumber $serialNumber,
        string $warehouseId,
        ?string $locationId = null
    ): SerialNumber {
        return DB::transaction(function () use ($serialNumber, $warehouseId, $locationId) {
            $serialNumber->update([
                'warehouse_id' => $warehouseId,
                'location_id' => $locationId,
                'status' => SerialNumberStatus::IN_TRANSIT,
            ]);

            return $serialNumber->fresh();
        });
    }

    /**
     * Complete serial number movement
     *
     * @param SerialNumber $serialNumber
     * @return SerialNumber
     */
    public function completeMovement(SerialNumber $serialNumber): SerialNumber
    {
        return DB::transaction(function () use ($serialNumber) {
            $serialNumber->update([
                'status' => SerialNumberStatus::IN_STOCK,
            ]);

            return $serialNumber->fresh();
        });
    }

    /**
     * Get serial number history for a product
     *
     * @param string $productId
     * @return Collection<SerialNumber>
     */
    public function getSerialNumberHistory(string $productId): Collection
    {
        return $this->serialNumberRepository->findByProduct($productId);
    }

    /**
     * Verify serial number is valid and available
     *
     * @param string $serialNumber
     * @return bool
     */
    public function verifySerialNumber(string $serialNumber): bool
    {
        $serial = $this->serialNumberRepository->findBySerialNumber($serialNumber);

        return $serial && $serial->isInStock();
    }

    /**
     * Get warranty information for serial number
     *
     * @param string $serialNumber
     * @return array|null
     */
    public function getWarrantyInfo(string $serialNumber): ?array
    {
        $serial = $this->serialNumberRepository->findBySerialNumber($serialNumber);

        if (!$serial || !$serial->warranty_end_date) {
            return null;
        }

        return [
            'serial_number' => $serial->serial_number,
            'warranty_start_date' => $serial->warranty_start_date,
            'warranty_end_date' => $serial->warranty_end_date,
            'is_active' => $serial->hasActiveWarranty(),
            'remaining_days' => $serial->getRemainingWarrantyDays(),
            'product' => $serial->product->name,
        ];
    }
}
