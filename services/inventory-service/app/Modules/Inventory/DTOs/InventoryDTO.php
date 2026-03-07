<?php

namespace App\Modules\Inventory\DTOs;

class InventoryDTO
{
    public function __construct(
        public readonly int $productId,
        public readonly string $productSku,
        public readonly string $productName,
        public readonly int $quantity = 0,
        public readonly int $reservedQuantity = 0,
        public readonly int $minimumQuantity = 10,
        public readonly ?string $location = null,
        public readonly string $status = 'available',
        public readonly ?array $metadata = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            productId: (int) $data['product_id'],
            productSku: $data['product_sku'],
            productName: $data['product_name'],
            quantity: (int) ($data['quantity'] ?? 0),
            reservedQuantity: (int) ($data['reserved_quantity'] ?? 0),
            minimumQuantity: (int) ($data['minimum_quantity'] ?? 10),
            location: $data['location'] ?? null,
            status: $data['status'] ?? 'available',
            metadata: $data['metadata'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'product_id' => $this->productId,
            'product_sku' => $this->productSku,
            'product_name' => $this->productName,
            'quantity' => $this->quantity,
            'reserved_quantity' => $this->reservedQuantity,
            'minimum_quantity' => $this->minimumQuantity,
            'location' => $this->location,
            'status' => $this->status,
            'metadata' => $this->metadata,
        ];
    }
}
