<?php

namespace App\Modules\Inventory\DTOs;

class AdjustmentDTO
{
    public function __construct(
        public ?string $tenant_id = null,
        public ?string $branch_id = null,
        public ?string $product_variant_id = null,
        public ?int $quantity = 0,
        public ?float $unit_cost = 0,
        public ?string $reference_type = 'adjustment',
        public ?string $reference_id = null,
        public ?string $batch_number = null,
        public ?string $lot_number = null,
        public ?string $expiry_date = null,
        public ?string $notes = null,
        public ?string $created_by = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            tenant_id: $data['tenant_id'] ?? null,
            branch_id: $data['branch_id'] ?? null,
            product_variant_id: $data['product_variant_id'] ?? null,
            quantity: isset($data['quantity']) ? (int) $data['quantity'] : 0,
            unit_cost: isset($data['unit_cost']) ? (float) $data['unit_cost'] : 0,
            reference_type: $data['reference_type'] ?? 'adjustment',
            reference_id: $data['reference_id'] ?? null,
            batch_number: $data['batch_number'] ?? null,
            lot_number: $data['lot_number'] ?? null,
            expiry_date: $data['expiry_date'] ?? null,
            notes: $data['notes'] ?? null,
            created_by: $data['created_by'] ?? null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'tenant_id' => $this->tenant_id,
            'branch_id' => $this->branch_id,
            'product_variant_id' => $this->product_variant_id,
            'quantity' => $this->quantity,
            'unit_cost' => $this->unit_cost,
            'reference_type' => $this->reference_type,
            'reference_id' => $this->reference_id,
            'batch_number' => $this->batch_number,
            'lot_number' => $this->lot_number,
            'expiry_date' => $this->expiry_date,
            'notes' => $this->notes,
            'created_by' => $this->created_by,
        ], fn($value) => !is_null($value));
    }
}
