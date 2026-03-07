<?php

namespace App\DTOs;

class InventoryDTO
{
    public function __construct(
        public readonly int $tenantId,
        public readonly int $productId,
        public readonly int $warehouseId,
        public readonly int $quantity,
        public readonly int $reservedQuantity = 0,
        public readonly int $reorderLevel = 10,
        public readonly ?int $maxQuantity = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            tenantId: $data['tenant_id'],
            productId: $data['product_id'],
            warehouseId: $data['warehouse_id'],
            quantity: (int) $data['quantity'],
            reservedQuantity: (int) ($data['reserved_quantity'] ?? 0),
            reorderLevel: (int) ($data['reorder_level'] ?? 10),
            maxQuantity: isset($data['max_quantity']) ? (int) $data['max_quantity'] : null,
        );
    }

    public function toArray(): array
    {
        $data = [
            'tenant_id' => $this->tenantId,
            'product_id' => $this->productId,
            'warehouse_id' => $this->warehouseId,
            'quantity' => $this->quantity,
            'reserved_quantity' => $this->reservedQuantity,
            'reorder_level' => $this->reorderLevel,
        ];
        if ($this->maxQuantity !== null) {
            $data['max_quantity'] = $this->maxQuantity;
        }
        return $data;
    }
}
