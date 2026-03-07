<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'tenant_id'          => $this->tenant_id,
            'product_id'         => $this->product_id,
            'warehouse_id'       => $this->warehouse_id,
            'warehouse'          => $this->whenLoaded('warehouse', fn () => new WarehouseResource($this->warehouse)),
            'sku'                => $this->sku,
            'quantity'           => $this->quantity,
            'reserved_quantity'  => $this->reserved_quantity,
            'available_quantity' => $this->available_quantity,
            'reorder_point'      => $this->reorder_point,
            'reorder_quantity'   => $this->reorder_quantity,
            'is_low_stock'       => $this->is_low_stock,
            'unit_cost'          => $this->unit_cost,
            'metadata'           => $this->metadata,
            'created_at'         => $this->created_at?->toIso8601String(),
            'updated_at'         => $this->updated_at?->toIso8601String(),
        ];
    }
}
