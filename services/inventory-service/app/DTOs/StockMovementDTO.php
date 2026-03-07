<?php

namespace App\DTOs;

use App\Models\StockMovement;

class StockMovementDTO
{
    public function __construct(
        public readonly string  $tenantId,
        public readonly string  $inventoryId,
        public readonly string  $productId,
        public readonly string  $warehouseId,
        public readonly string  $type,
        public readonly int     $quantity,
        public readonly int     $previousQuantity,
        public readonly int     $newQuantity,
        public readonly ?string $referenceType,
        public readonly ?string $referenceId,
        public readonly ?string $notes,
        public readonly ?string $performedBy,
        public readonly ?string $id = null,
    ) {}

    public static function fromModel(StockMovement $movement): self
    {
        return new self(
            tenantId:         $movement->tenant_id,
            inventoryId:      $movement->inventory_id,
            productId:        $movement->product_id,
            warehouseId:      $movement->warehouse_id,
            type:             $movement->type,
            quantity:         $movement->quantity,
            previousQuantity: $movement->previous_quantity,
            newQuantity:      $movement->new_quantity,
            referenceType:    $movement->reference_type,
            referenceId:      $movement->reference_id,
            notes:            $movement->notes,
            performedBy:      $movement->performed_by,
            id:               $movement->id,
        );
    }

    public function toArray(): array
    {
        return [
            'id'                => $this->id,
            'tenant_id'         => $this->tenantId,
            'inventory_id'      => $this->inventoryId,
            'product_id'        => $this->productId,
            'warehouse_id'      => $this->warehouseId,
            'type'              => $this->type,
            'quantity'          => $this->quantity,
            'previous_quantity' => $this->previousQuantity,
            'new_quantity'      => $this->newQuantity,
            'reference_type'    => $this->referenceType,
            'reference_id'      => $this->referenceId,
            'notes'             => $this->notes,
            'performed_by'      => $this->performedBy,
        ];
    }
}
