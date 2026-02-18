<?php

declare(strict_types=1);

namespace Modules\Inventory\DTOs;

use Modules\Core\DTOs\BaseDTO;

/**
 * Stock Transaction DTO
 *
 * Data Transfer Object for stock transactions.
 */
class StockTransactionDTO extends BaseDTO
{
    public function __construct(
        public string $product_id,
        public string $warehouse_id,
        public string $transaction_type,
        public float $quantity,
        public ?string $variant_id = null,
        public ?string $location_id = null,
        public ?string $uom_id = null,
        public ?string $batch_number = null,
        public ?string $serial_number = null,
        public ?string $reference_type = null,
        public ?string $reference_id = null,
        public ?float $unit_cost = null,
        public ?float $total_cost = null,
        public ?string $notes = null,
        public ?\DateTimeInterface $transaction_date = null,
    ) {}

    /**
     * Convert to array for database operations.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'product_id' => $this->product_id,
            'variant_id' => $this->variant_id,
            'warehouse_id' => $this->warehouse_id,
            'location_id' => $this->location_id,
            'transaction_type' => $this->transaction_type,
            'quantity' => $this->quantity,
            'uom_id' => $this->uom_id,
            'batch_number' => $this->batch_number,
            'serial_number' => $this->serial_number,
            'reference_type' => $this->reference_type,
            'reference_id' => $this->reference_id,
            'unit_cost' => $this->unit_cost,
            'total_cost' => $this->total_cost,
            'notes' => $this->notes,
            'transaction_date' => $this->transaction_date,
        ], fn ($value) => ! is_null($value));
    }
}
