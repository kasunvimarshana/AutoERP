<?php

namespace App\Modules\Inventory\DTOs;

class StockAdjustmentDTO
{
    public function __construct(
        public readonly int $inventoryItemId,
        public readonly int $quantity,
        public readonly string $type,
        public readonly ?string $reason = null,
        public readonly ?string $reference = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            inventoryItemId: (int) $data['inventory_item_id'],
            quantity: (int) $data['quantity'],
            type: $data['type'],
            reason: $data['reason'] ?? null,
            reference: $data['reference'] ?? null,
        );
    }
}
