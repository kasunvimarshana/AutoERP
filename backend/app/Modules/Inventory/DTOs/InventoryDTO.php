<?php

namespace App\Modules\Inventory\DTOs;

use App\Modules\Inventory\Models\Inventory;

readonly class InventoryDTO
{
    public function __construct(
        public ?int $id,
        public int $productId,
        public ?int $tenantId,
        public ?string $warehouseLocation,
        public int $quantity,
        public int $reservedQuantity,
        public int $reorderPoint,
        public int $reorderQuantity,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'] ?? null,
            productId: $data['product_id'],
            tenantId: $data['tenant_id'] ?? null,
            warehouseLocation: $data['warehouse_location'] ?? null,
            quantity: (int) ($data['quantity'] ?? 0),
            reservedQuantity: (int) ($data['reserved_quantity'] ?? 0),
            reorderPoint: (int) ($data['reorder_point'] ?? 0),
            reorderQuantity: (int) ($data['reorder_quantity'] ?? 0),
        );
    }

    public static function fromModel(Inventory $inventory): self
    {
        return new self(
            id: $inventory->id,
            productId: $inventory->product_id,
            tenantId: $inventory->tenant_id,
            warehouseLocation: $inventory->warehouse_location,
            quantity: $inventory->quantity,
            reservedQuantity: $inventory->reserved_quantity,
            reorderPoint: $inventory->reorder_point,
            reorderQuantity: $inventory->reorder_quantity,
        );
    }
}
