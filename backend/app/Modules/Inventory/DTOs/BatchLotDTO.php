<?php

namespace App\Modules\Inventory\DTOs;

class BatchLotDTO
{
    public function __construct(
        public ?string $id = null,
        public ?string $tenant_id = null,
        public ?string $branch_id = null,
        public ?string $product_variant_id = null,
        public ?string $batch_number = null,
        public ?string $lot_number = null,
        public ?int $quantity_received = 0,
        public ?int $quantity_remaining = 0,
        public ?float $unit_cost = 0,
        public ?string $manufacture_date = null,
        public ?string $expiry_date = null,
        public ?string $status = 'active',
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'] ?? null,
            tenant_id: $data['tenant_id'] ?? null,
            branch_id: $data['branch_id'] ?? null,
            product_variant_id: $data['product_variant_id'] ?? null,
            batch_number: $data['batch_number'] ?? null,
            lot_number: $data['lot_number'] ?? null,
            quantity_received: isset($data['quantity_received']) ? (int) $data['quantity_received'] : 0,
            quantity_remaining: isset($data['quantity_remaining']) ? (int) $data['quantity_remaining'] : 0,
            unit_cost: isset($data['unit_cost']) ? (float) $data['unit_cost'] : 0,
            manufacture_date: $data['manufacture_date'] ?? null,
            expiry_date: $data['expiry_date'] ?? null,
            status: $data['status'] ?? 'active',
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'branch_id' => $this->branch_id,
            'product_variant_id' => $this->product_variant_id,
            'batch_number' => $this->batch_number,
            'lot_number' => $this->lot_number,
            'quantity_received' => $this->quantity_received,
            'quantity_remaining' => $this->quantity_remaining,
            'unit_cost' => $this->unit_cost,
            'manufacture_date' => $this->manufacture_date,
            'expiry_date' => $this->expiry_date,
            'status' => $this->status,
        ], fn($value) => !is_null($value));
    }
}
