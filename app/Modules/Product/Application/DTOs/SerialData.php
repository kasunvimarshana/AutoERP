<?php

declare(strict_types=1);

namespace Modules\Product\Application\DTOs;

class SerialData
{
    /** @param array<string,mixed>|null $metadata */
    public function __construct(
        public readonly int $tenant_id,
        public readonly int $product_id,
        public readonly string $serial_number,
        public readonly ?int $variant_id = null,
        public readonly ?int $batch_id = null,
        public readonly string $status = 'available',
        public readonly ?string $sold_at = null,
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
            serial_number: (string) $data['serial_number'],
            variant_id: isset($data['variant_id']) ? (int) $data['variant_id'] : null,
            batch_id: isset($data['batch_id']) ? (int) $data['batch_id'] : null,
            status: (string) ($data['status'] ?? 'available'),
            sold_at: isset($data['sold_at']) ? (string) $data['sold_at'] : null,
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
            'batch_id' => $this->batch_id,
            'serial_number' => $this->serial_number,
            'status' => $this->status,
            'sold_at' => $this->sold_at,
            'notes' => $this->notes,
            'metadata' => $this->metadata,
        ];
    }
}
