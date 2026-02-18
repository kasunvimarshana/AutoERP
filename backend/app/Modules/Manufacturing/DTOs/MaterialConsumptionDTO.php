<?php

namespace App\Modules\Manufacturing\DTOs;

/**
 * Material Consumption Data Transfer Object
 *
 * Encapsulates data for recording material consumption in production.
 */
class MaterialConsumptionDTO
{
    public function __construct(
        public readonly int $productionOrderId,
        public readonly array $consumedItems, // [['product_id' => int, 'quantity' => float, 'batch_number' => string, ...]]
        public readonly ?int $consumedBy = null,
        public readonly ?string $consumedAt = null,
        public readonly ?string $notes = null,
    ) {}

    /**
     * Create DTO from array
     */
    public static function fromArray(array $data): self
    {
        return new self(
            productionOrderId: $data['production_order_id'],
            consumedItems: $data['consumed_items'],
            consumedBy: $data['consumed_by'] ?? null,
            consumedAt: $data['consumed_at'] ?? null,
            notes: $data['notes'] ?? null,
        );
    }

    /**
     * Convert DTO to array
     */
    public function toArray(): array
    {
        return [
            'production_order_id' => $this->productionOrderId,
            'consumed_items' => $this->consumedItems,
            'consumed_by' => $this->consumedBy,
            'consumed_at' => $this->consumedAt,
            'notes' => $this->notes,
        ];
    }
}
