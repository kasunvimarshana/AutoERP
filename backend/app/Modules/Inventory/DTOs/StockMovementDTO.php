<?php

declare(strict_types=1);

namespace App\Modules\Inventory\DTOs;

use App\Core\DTOs\BaseDTO;

/**
 * Stock Movement Data Transfer Object
 * 
 * Type-safe data container for inventory movements
 */
class StockMovementDTO extends BaseDTO
{
    public function __construct(
        public readonly int $product_id,
        public readonly int $tenant_id,
        public readonly ?int $organization_id = null,
        public readonly ?int $branch_id = null,
        public readonly ?int $location_id = null,
        public readonly ?int $variant_id = null,
        public readonly string $movement_type, // in, out, adjustment, transfer
        public readonly float $quantity,
        public readonly ?int $unit_id = null,
        public readonly ?float $unit_cost = null,
        public readonly ?string $reference_type = null, // purchase_order, sales_order, etc.
        public readonly ?int $reference_id = null,
        public readonly ?string $batch_number = null,
        public readonly ?string $serial_number = null,
        public readonly ?string $lot_number = null,
        public readonly ?\DateTimeInterface $expiry_date = null,
        public readonly ?string $notes = null,
        public readonly ?int $from_branch_id = null,
        public readonly ?int $from_location_id = null,
        public readonly ?int $to_branch_id = null,
        public readonly ?int $to_location_id = null,
        public readonly ?int $created_by = null,
        public readonly ?array $metadata = null,
    ) {
        $this->validate();
    }

    /**
     * Validate DTO data
     *
     * @throws \InvalidArgumentException
     */
    public function validate(): void
    {
        if ($this->product_id <= 0) {
            throw new \InvalidArgumentException('Valid product_id is required');
        }

        if ($this->tenant_id <= 0) {
            throw new \InvalidArgumentException('Valid tenant_id is required');
        }

        if (!in_array($this->movement_type, ['in', 'out', 'adjustment', 'transfer'])) {
            throw new \InvalidArgumentException('Invalid movement type');
        }

        if ($this->quantity == 0) {
            throw new \InvalidArgumentException('Quantity cannot be zero');
        }

        if ($this->unit_cost !== null && $this->unit_cost < 0) {
            throw new \InvalidArgumentException('Unit cost cannot be negative');
        }

        if ($this->movement_type === 'transfer') {
            if ($this->from_branch_id === null || $this->to_branch_id === null) {
                throw new \InvalidArgumentException('Transfer requires both from_branch_id and to_branch_id');
            }

            if ($this->from_branch_id === $this->to_branch_id && 
                $this->from_location_id === $this->to_location_id) {
                throw new \InvalidArgumentException('Transfer source and destination cannot be the same');
            }
        }
    }

    /**
     * Get absolute quantity (for calculations)
     *
     * @return float
     */
    public function getAbsoluteQuantity(): float
    {
        return abs($this->quantity);
    }

    /**
     * Get signed quantity (positive for in, negative for out)
     *
     * @return float
     */
    public function getSignedQuantity(): float
    {
        return match ($this->movement_type) {
            'in' => abs($this->quantity),
            'out' => -abs($this->quantity),
            'adjustment' => $this->quantity,
            'transfer' => $this->quantity,
        };
    }

    /**
     * Check if this is a stock increase
     *
     * @return bool
     */
    public function isStockIncrease(): bool
    {
        return $this->getSignedQuantity() > 0;
    }

    /**
     * Check if this is a stock decrease
     *
     * @return bool
     */
    public function isStockDecrease(): bool
    {
        return $this->getSignedQuantity() < 0;
    }

    /**
     * Get total value of this movement
     *
     * @return float|null
     */
    public function getTotalValue(): ?float
    {
        if ($this->unit_cost === null) {
            return null;
        }

        return $this->getAbsoluteQuantity() * $this->unit_cost;
    }
}
