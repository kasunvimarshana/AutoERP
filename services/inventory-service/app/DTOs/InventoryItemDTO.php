<?php

namespace App\DTOs;

class InventoryItemDTO
{
    public function __construct(
        public readonly int     $tenantId,
        public readonly int     $productId,
        public readonly int     $warehouseId,
        public readonly string  $sku,
        public readonly int     $quantity       = 0,
        public readonly int     $reservedQuantity = 0,
        public readonly int     $reorderPoint   = 0,
        public readonly int     $reorderQuantity = 0,
        public readonly ?float  $unitCost       = null,
        public readonly ?array  $metadata       = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            tenantId:         (int) $data['tenant_id'],
            productId:        (int) $data['product_id'],
            warehouseId:      (int) $data['warehouse_id'],
            sku:              (string) $data['sku'],
            quantity:         (int) ($data['quantity'] ?? 0),
            reservedQuantity: (int) ($data['reserved_quantity'] ?? 0),
            reorderPoint:     (int) ($data['reorder_point'] ?? 0),
            reorderQuantity:  (int) ($data['reorder_quantity'] ?? 0),
            unitCost:         isset($data['unit_cost']) ? (float) $data['unit_cost'] : null,
            metadata:         $data['metadata'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'tenant_id'         => $this->tenantId,
            'product_id'        => $this->productId,
            'warehouse_id'      => $this->warehouseId,
            'sku'               => $this->sku,
            'quantity'          => $this->quantity,
            'reserved_quantity' => $this->reservedQuantity,
            'reorder_point'     => $this->reorderPoint,
            'reorder_quantity'  => $this->reorderQuantity,
            'unit_cost'         => $this->unitCost,
            'metadata'          => $this->metadata,
        ];
    }
}
