<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'tenant_id'          => $this->tenant_id,
            'product_id'         => $this->product_id,
            'warehouse_id'       => $this->warehouse_id,
            'quantity'           => $this->quantity,
            'reserved_quantity'  => $this->reserved_quantity,
            'available_quantity' => $this->available_quantity,
            'unit'               => $this->unit,
            'location'           => $this->location,
            'notes'              => $this->notes,
            'product'            => new ProductResource($this->whenLoaded('product')),
            'created_at'         => $this->created_at->toIso8601String(),
            'updated_at'         => $this->updated_at->toIso8601String(),
        ];
    }
}
