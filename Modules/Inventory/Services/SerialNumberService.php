<?php

declare(strict_types=1);

namespace Modules\Inventory\Services;

use Modules\Core\Helpers\TransactionHelper;
use Modules\Inventory\Enums\SerialNumberStatus;
use Modules\Inventory\Events\SerialNumberAllocated;
use Modules\Inventory\Events\SerialNumberDeallocated;
use Modules\Inventory\Exceptions\InvalidSerialNumberException;
use Modules\Inventory\Models\SerialNumber;
use Modules\Inventory\Repositories\SerialNumberRepository;

/**
 * Serial Number Service
 *
 * Manages serial number lifecycle including allocation, deallocation,
 * tracking, and validation. Handles serialized inventory items.
 */
class SerialNumberService
{
    public function __construct(
        private SerialNumberRepository $serialNumberRepository
    ) {}

    /**
     * Create a new serial number record.
     *
     * @param  array  $data  Serial number data
     */
    public function create(array $data): SerialNumber
    {
        // Validate serial number is unique
        $existing = $this->serialNumberRepository->findBySerialNumber($data['serial_number']);
        if ($existing) {
            throw new InvalidSerialNumberException(
                "Serial number {$data['serial_number']} already exists"
            );
        }

        // Set default status
        $data['status'] = $data['status'] ?? SerialNumberStatus::IN_STOCK;
        $data['received_date'] = $data['received_date'] ?? now();

        return $this->serialNumberRepository->create($data);
    }

    /**
     * Allocate serial number (mark as allocated/sold).
     *
     * @param  string  $serialNumber  Serial number string
     * @param  array  $allocationData  Allocation details (reference_type, reference_id, etc.)
     */
    public function allocate(string $serialNumber, array $allocationData): SerialNumber
    {
        return TransactionHelper::execute(function () use ($serialNumber, $allocationData) {
            $serial = $this->serialNumberRepository->findBySerialNumberOrFail($serialNumber);

            // Validate serial is available
            if ($serial->status !== SerialNumberStatus::IN_STOCK) {
                throw new InvalidSerialNumberException(
                    "Serial number {$serialNumber} is not available for allocation (status: {$serial->status->value})"
                );
            }

            // Update serial number status
            $serial = $this->serialNumberRepository->updateAndReturn($serial->id, [
                'status' => SerialNumberStatus::RESERVED,
                'reference_type' => $allocationData['reference_type'] ?? null,
                'reference_id' => $allocationData['reference_id'] ?? null,
                'warehouse_id' => $allocationData['warehouse_id'] ?? $serial->warehouse_id,
                'location_id' => $allocationData['location_id'] ?? $serial->location_id,
            ]);

            // Fire event
            event(new SerialNumberAllocated($serial));

            return $serial;
        });
    }

    /**
     * Deallocate serial number (return to available status).
     *
     * @param  string  $serialNumber  Serial number string
     * @param  array  $data  Additional data (warehouse_id, notes, etc.)
     */
    public function deallocate(string $serialNumber, array $data = []): SerialNumber
    {
        return TransactionHelper::execute(function () use ($serialNumber, $data) {
            $serial = $this->serialNumberRepository->findBySerialNumberOrFail($serialNumber);

            // Validate serial is allocated
            if ($serial->status !== SerialNumberStatus::RESERVED) {
                throw new InvalidSerialNumberException(
                    "Serial number {$serialNumber} is not reserved (status: {$serial->status->value})"
                );
            }

            // Update serial number status
            $serial = $this->serialNumberRepository->updateAndReturn($serial->id, [
                'status' => SerialNumberStatus::IN_STOCK,
                'reference_type' => null,
                'reference_id' => null,
                'warehouse_id' => $data['warehouse_id'] ?? $serial->warehouse_id,
                'location_id' => $data['location_id'] ?? $serial->location_id,
                'notes' => $data['notes'] ?? $serial->notes,
            ]);

            // Fire event
            event(new SerialNumberDeallocated($serial));

            return $serial;
        });
    }

    /**
     * Mark serial number as sold.
     *
     * @param  string  $serialNumber  Serial number string
     * @param  array  $salesData  Sales information
     */
    public function markAsSold(string $serialNumber, array $salesData): SerialNumber
    {
        return TransactionHelper::execute(function () use ($serialNumber, $salesData) {
            $serial = $this->serialNumberRepository->findBySerialNumberOrFail($serialNumber);

            // Validate serial can be sold
            if (! in_array($serial->status, [SerialNumberStatus::IN_STOCK, SerialNumberStatus::RESERVED], true)) {
                throw new InvalidSerialNumberException(
                    "Serial number {$serialNumber} cannot be sold in {$serial->status->value} status"
                );
            }

            // Update serial number
            return $this->serialNumberRepository->updateAndReturn($serial->id, [
                'status' => SerialNumberStatus::SOLD,
                'sold_date' => $salesData['sold_date'] ?? now(),
                'reference_type' => $salesData['reference_type'] ?? 'sales_order',
                'reference_id' => $salesData['reference_id'] ?? null,
                'warehouse_id' => null,
                'location_id' => null,
            ]);
        });
    }

    /**
     * Mark serial number as defective.
     *
     * @param  string  $serialNumber  Serial number string
     * @param  string|null  $reason  Defect reason
     */
    public function markAsDefective(string $serialNumber, ?string $reason = null): SerialNumber
    {
        $serial = $this->serialNumberRepository->findBySerialNumberOrFail($serialNumber);

        $notes = $this->appendNotes($serial->notes, $reason ? "Defect: {$reason}" : null);

        return $this->serialNumberRepository->updateAndReturn($serial->id, [
            'status' => SerialNumberStatus::SCRAPPED,
            'notes' => $notes,
        ]);
    }

    /**
     * Mark serial number as returned.
     *
     * @param  string  $serialNumber  Serial number string
     * @param  array  $returnData  Return information
     */
    public function markAsReturned(string $serialNumber, array $returnData): SerialNumber
    {
        return TransactionHelper::execute(function () use ($serialNumber, $returnData) {
            $serial = $this->serialNumberRepository->findBySerialNumberOrFail($serialNumber);

            // Validate serial was sold
            if ($serial->status !== SerialNumberStatus::SOLD) {
                throw new InvalidSerialNumberException(
                    "Serial number {$serialNumber} cannot be returned (not sold)"
                );
            }

            // Update serial number
            return $this->serialNumberRepository->updateAndReturn($serial->id, [
                'status' => SerialNumberStatus::RETURNED,
                'warehouse_id' => $returnData['warehouse_id'] ?? null,
                'location_id' => $returnData['location_id'] ?? null,
                'reference_type' => $returnData['reference_type'] ?? 'sales_return',
                'reference_id' => $returnData['reference_id'] ?? null,
                'notes' => $returnData['notes'] ?? $serial->notes,
            ]);
        });
    }

    /**
     * Validate serial number is available for use.
     *
     * @param  string  $serialNumber  Serial number string
     */
    public function isAvailable(string $serialNumber): bool
    {
        $serial = $this->serialNumberRepository->findBySerialNumber($serialNumber);

        return $serial && $serial->status === SerialNumberStatus::IN_STOCK;
    }

    /**
     * Validate multiple serial numbers are available.
     *
     * @param  array  $serialNumbers  Array of serial number strings
     * @return array ['valid' => [], 'invalid' => []]
     */
    public function validateAvailability(array $serialNumbers): array
    {
        $valid = [];
        $invalid = [];

        foreach ($serialNumbers as $serialNumber) {
            if ($this->isAvailable($serialNumber)) {
                $valid[] = $serialNumber;
            } else {
                $invalid[] = $serialNumber;
            }
        }

        return [
            'valid' => $valid,
            'invalid' => $invalid,
        ];
    }

    /**
     * Get available serial numbers for a product.
     *
     * @param  string  $productId  Product ID
     * @param  string|null  $warehouseId  Optional warehouse filter
     * @param  int  $limit  Maximum number to return
     */
    public function getAvailableSerialNumbers(string $productId, ?string $warehouseId = null, int $limit = 100): array
    {
        return $this->serialNumberRepository->getAvailable($productId, $warehouseId);
    }

    /**
     * Track serial number lifecycle.
     *
     * @param  string  $serialNumber  Serial number string
     * @return array Lifecycle history
     */
    public function getLifecycleHistory(string $serialNumber): array
    {
        $serial = $this->serialNumberRepository->findBySerialNumberOrFail($serialNumber);

        return [
            'serial_number' => $serial->serial_number,
            'product_id' => $serial->product_id,
            'product_name' => $serial->product->name ?? null,
            'current_status' => $serial->status,
            'current_warehouse' => $serial->warehouse->name ?? null,
            'current_location' => $serial->location->name ?? null,
            'received_date' => $serial->received_date,
            'sold_date' => $serial->sold_date,
            'cost' => $serial->cost,
            'warranty_months' => $serial->warranty_months,
            'warranty_expiry_date' => $serial->warranty_expiry_date,
            'reference_type' => $serial->reference_type,
            'reference_id' => $serial->reference_id,
            'notes' => $serial->notes,
        ];
    }

    /**
     * Allocate multiple serial numbers in batch.
     *
     * @param  array  $serialNumbers  Array of serial number strings
     * @param  array  $allocationData  Allocation details
     * @return array ['success' => [], 'failed' => []]
     */
    public function batchAllocate(array $serialNumbers, array $allocationData): array
    {
        $success = [];
        $failed = [];

        foreach ($serialNumbers as $serialNumber) {
            try {
                $serial = $this->allocate($serialNumber, $allocationData);
                $success[] = $serial->serial_number;
            } catch (\Exception $e) {
                $failed[] = [
                    'serial_number' => $serialNumber,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return [
            'success' => $success,
            'failed' => $failed,
        ];
    }

    /**
     * Deallocate multiple serial numbers in batch.
     *
     * @param  array  $serialNumbers  Array of serial number strings
     * @param  array  $data  Additional data
     * @return array ['success' => [], 'failed' => []]
     */
    public function batchDeallocate(array $serialNumbers, array $data = []): array
    {
        $success = [];
        $failed = [];

        foreach ($serialNumbers as $serialNumber) {
            try {
                $serial = $this->deallocate($serialNumber, $data);
                $success[] = $serial->serial_number;
            } catch (\Exception $e) {
                $failed[] = [
                    'serial_number' => $serialNumber,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return [
            'success' => $success,
            'failed' => $failed,
        ];
    }

    /**
     * Update serial number details.
     *
     * @param  string  $serialNumber  Serial number string
     * @param  array  $data  Update data
     */
    public function update(string $serialNumber, array $data): SerialNumber
    {
        $serial = $this->serialNumberRepository->findBySerialNumberOrFail($serialNumber);

        // Prevent changing serial number itself
        unset($data['serial_number']);

        return $this->serialNumberRepository->updateAndReturn($serial->id, $data);
    }

    /**
     * Check if warranty is still valid.
     *
     * @param  string  $serialNumber  Serial number string
     */
    public function isWarrantyValid(string $serialNumber): bool
    {
        $serial = $this->serialNumberRepository->findBySerialNumberOrFail($serialNumber);

        if ($serial->warranty_expiry_date === null) {
            return false;
        }

        return now()->lessThanOrEqualTo($serial->warranty_expiry_date);
    }

    /**
     * Append additional notes to existing notes.
     */
    private function appendNotes(?string $existingNotes, ?string $additionalNotes): ?string
    {
        if (! $additionalNotes) {
            return $existingNotes;
        }

        if (! $existingNotes) {
            return $additionalNotes;
        }

        return $existingNotes."\n\n".$additionalNotes;
    }
}
