<?php

namespace App\Modules\Inventory\Resources;

use App\Modules\Product\Resources\ProductResource;
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
            'warehouse_location' => $this->warehouse_location,
            'quantity'           => $this->quantity,
            'reserved_quantity'  => $this->reserved_quantity,
            'available_quantity' => $this->available_quantity,
            'reorder_point'      => $this->reorder_point,
            'reorder_quantity'   => $this->reorder_quantity,
            'last_restocked_at'  => $this->last_restocked_at?->toIso8601String(),
            'product'            => $this->whenLoaded('product', fn() => new ProductResource($this->product)),
            'created_at'         => $this->created_at?->toIso8601String(),
            'updated_at'         => $this->updated_at?->toIso8601String(),
        ];
    }
}
