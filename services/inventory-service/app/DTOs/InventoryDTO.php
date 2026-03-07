<?php

namespace App\DTOs;

use App\Models\Inventory;

class InventoryDTO
{
    public function __construct(
        public readonly string  $tenantId,
        public readonly string  $productId,
        public readonly string  $warehouseId,
        public readonly int     $quantity,
        public readonly int     $reservedQuantity,
        public readonly int     $availableQuantity,
        public readonly float   $unitCost,
        public readonly ?string $lastMovementAt,
        public readonly ?string $id = null,
    ) {}

    public static function fromModel(Inventory $inventory): self
    {
        return new self(
            tenantId:          $inventory->tenant_id,
            productId:         $inventory->product_id,
            warehouseId:       $inventory->warehouse_id,
            quantity:          $inventory->quantity,
            reservedQuantity:  $inventory->reserved_quantity,
            availableQuantity: $inventory->available_quantity,
            unitCost:          (float) $inventory->unit_cost,
            lastMovementAt:    $inventory->last_movement_at?->toIso8601String(),
            id:                $inventory->id,
        );
    }

    public static function fromArray(array $data): self
    {
        return new self(
            tenantId:          $data['tenant_id'],
            productId:         $data['product_id'],
            warehouseId:       $data['warehouse_id'],
            quantity:          (int) ($data['quantity'] ?? 0),
            reservedQuantity:  (int) ($data['reserved_quantity'] ?? 0),
            availableQuantity: (int) ($data['available_quantity'] ?? 0),
            unitCost:          (float) ($data['unit_cost'] ?? 0),
            lastMovementAt:    $data['last_movement_at'] ?? null,
            id:                $data['id'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'id'                 => $this->id,
            'tenant_id'          => $this->tenantId,
            'product_id'         => $this->productId,
            'warehouse_id'       => $this->warehouseId,
            'quantity'           => $this->quantity,
            'reserved_quantity'  => $this->reservedQuantity,
            'available_quantity' => $this->availableQuantity,
            'unit_cost'          => $this->unitCost,
            'last_movement_at'   => $this->lastMovementAt,
        ];
    }
}
