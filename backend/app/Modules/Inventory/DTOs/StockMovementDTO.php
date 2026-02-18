<?php

namespace App\Modules\Inventory\DTOs;

use App\Enums\StockMovementType;

/**
 * Stock Movement Data Transfer Object
 *
 * Encapsulates data for stock movement operations.
 */
class StockMovementDTO
{
    public function __construct(
        public readonly int $productId,
        public readonly StockMovementType $movementType,
        public readonly float $quantity,
        public readonly ?float $unitCost = null,
        public readonly ?int $warehouseId = null,
        public readonly ?int $locationId = null,
        public readonly ?string $batchNumber = null,
        public readonly ?string $lotNumber = null,
        public readonly ?string $serialNumber = null,
        public readonly ?string $manufacturingDate = null,
        public readonly ?string $expiryDate = null,
        public readonly ?string $referenceType = null,
        public readonly ?int $referenceId = null,
        public readonly ?string $notes = null,
        public readonly ?array $metadata = null,
        public readonly ?\DateTime $transactionDate = null,
    ) {}

    /**
     * Create DTO from array
     */
    public static function fromArray(array $data): self
    {
        return new self(
            productId: $data['product_id'],
            movementType: StockMovementType::from($data['movement_type']),
            quantity: $data['quantity'],
            unitCost: $data['unit_cost'] ?? null,
            warehouseId: $data['warehouse_id'] ?? null,
            locationId: $data['location_id'] ?? null,
            batchNumber: $data['batch_number'] ?? null,
            lotNumber: $data['lot_number'] ?? null,
            serialNumber: $data['serial_number'] ?? null,
            manufacturingDate: $data['manufacturing_date'] ?? null,
            expiryDate: $data['expiry_date'] ?? null,
            referenceType: $data['reference_type'] ?? null,
            referenceId: $data['reference_id'] ?? null,
            notes: $data['notes'] ?? null,
            metadata: $data['metadata'] ?? null,
            transactionDate: isset($data['transaction_date'])
                ? new \DateTime($data['transaction_date'])
                : null,
        );
    }

    /**
     * Convert DTO to array
     */
    public function toArray(): array
    {
        return [
            'product_id' => $this->productId,
            'movement_type' => $this->movementType->value,
            'quantity' => $this->quantity,
            'unit_cost' => $this->unitCost,
            'warehouse_id' => $this->warehouseId,
            'location_id' => $this->locationId,
            'batch_number' => $this->batchNumber,
            'lot_number' => $this->lotNumber,
            'serial_number' => $this->serialNumber,
            'manufacturing_date' => $this->manufacturingDate,
            'expiry_date' => $this->expiryDate,
            'reference_type' => $this->referenceType,
            'reference_id' => $this->referenceId,
            'notes' => $this->notes,
            'metadata' => $this->metadata,
            'transaction_date' => $this->transactionDate?->format('Y-m-d H:i:s'),
        ];
    }
}
