<?php

declare(strict_types=1);

namespace Modules\Product\Application\DTOs;

class BatchData
{
    /** @param array<string,mixed>|null $metadata */
    public function __construct(
        public readonly int $tenant_id,
        public readonly int $product_id,
        public readonly string $batch_number,
        public readonly ?int $variant_id = null,
        public readonly ?string $lot_number = null,
        public readonly ?string $manufactured_date = null,
        public readonly ?string $expiry_date = null,
        public readonly string $quantity = '0',
        public readonly string $status = 'active',
        public readonly ?string $notes = null,
        public readonly ?array $metadata = null,
        public readonly ?int $id = null,
    ) {}

    /** @param array<string,mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            tenant_id: (int) $data['tenant_id'],
            product_id: (int) $data['product_id'],
            batch_number: (string) $data['batch_number'],
            variant_id: isset($data['variant_id']) ? (int) $data['variant_id'] : null,
            lot_number: isset($data['lot_number']) ? (string) $data['lot_number'] : null,
            manufactured_date: isset($data['manufactured_date']) ? (string) $data['manufactured_date'] : null,
            expiry_date: isset($data['expiry_date']) ? (string) $data['expiry_date'] : null,
            quantity: (string) ($data['quantity'] ?? '0'),
            status: (string) ($data['status'] ?? 'active'),
            notes: isset($data['notes']) ? (string) $data['notes'] : null,
            metadata: isset($data['metadata']) && is_array($data['metadata']) ? $data['metadata'] : null,
            id: isset($data['id']) ? (int) $data['id'] : null,
        );
    }

    /** @return array<string,mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'product_id' => $this->product_id,
            'variant_id' => $this->variant_id,
            'batch_number' => $this->batch_number,
            'lot_number' => $this->lot_number,
            'manufactured_date' => $this->manufactured_date,
            'expiry_date' => $this->expiry_date,
            'quantity' => $this->quantity,
            'status' => $this->status,
            'notes' => $this->notes,
            'metadata' => $this->metadata,
        ];
    }
}
