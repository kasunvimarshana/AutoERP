<?php

namespace App\DTOs;

class InventoryDataDTO
{
    public function __construct(
        public readonly string $productId,
        public readonly string $tenantId,
        public readonly int $quantityOnHand,
        public readonly int $quantityReserved,
        public readonly int $quantityAvailable,
        public readonly ?string $warehouseId,
        public readonly ?string $location,
        public readonly bool $tracked,
        public readonly ?int $reorderPoint,
        public readonly ?int $reorderQuantity,
        public readonly ?string $syncedAt,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            productId:          (string) ($data['product_id'] ?? ''),
            tenantId:           (string) ($data['tenant_id'] ?? ''),
            quantityOnHand:     (int) ($data['quantity_on_hand'] ?? 0),
            quantityReserved:   (int) ($data['quantity_reserved'] ?? 0),
            quantityAvailable:  (int) ($data['quantity_available'] ?? 0),
            warehouseId:        $data['warehouse_id'] ?? null,
            location:           $data['location'] ?? null,
            tracked:            (bool) ($data['tracked'] ?? false),
            reorderPoint:       isset($data['reorder_point']) ? (int) $data['reorder_point'] : null,
            reorderQuantity:    isset($data['reorder_quantity']) ? (int) $data['reorder_quantity'] : null,
            syncedAt:           $data['synced_at'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'product_id'          => $this->productId,
            'tenant_id'           => $this->tenantId,
            'quantity_on_hand'    => $this->quantityOnHand,
            'quantity_reserved'   => $this->quantityReserved,
            'quantity_available'  => $this->quantityAvailable,
            'warehouse_id'        => $this->warehouseId,
            'location'            => $this->location,
            'tracked'             => $this->tracked,
            'reorder_point'       => $this->reorderPoint,
            'reorder_quantity'    => $this->reorderQuantity,
            'synced_at'           => $this->syncedAt,
        ];
    }
}
