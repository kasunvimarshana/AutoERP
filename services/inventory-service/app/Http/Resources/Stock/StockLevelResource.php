<?php

namespace App\Http\Resources\Stock;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockLevelResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'tenant_id'           => $this->tenant_id,
            'product_id'          => $this->product_id,
            'warehouse_id'        => $this->warehouse_id,
            'warehouse'           => $this->whenLoaded('warehouse', fn () => [
                'id'   => $this->warehouse->id,
                'code' => $this->warehouse->code,
                'name' => $this->warehouse->name,
            ]),
            'quantity_available'  => (float) $this->quantity_available,
            'quantity_reserved'   => (float) $this->quantity_reserved,
            'quantity_on_hand'    => (float) $this->quantity_on_hand,
            'quantity_allocated'  => (float) ($this->quantity_reserved ?? 0),
            'is_low_stock'        => $this->whenLoaded('product', function () {
                return $this->quantity_available <= ($this->product->reorder_point ?? 0);
            }, fn () => false),
            'version'             => $this->version,
            'updated_at'          => $this->updated_at?->toISOString(),
        ];
    }
}
