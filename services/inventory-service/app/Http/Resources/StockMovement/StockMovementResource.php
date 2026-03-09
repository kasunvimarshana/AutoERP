<?php

namespace App\Http\Resources\StockMovement;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockMovementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'tenant_id'        => $this->tenant_id,
            'product_id'       => $this->product_id,
            'product'          => $this->whenLoaded('product', fn () => [
                'id'   => $this->product->id,
                'sku'  => $this->product->sku,
                'name' => $this->product->name,
            ]),
            'warehouse_id'     => $this->warehouse_id,
            'warehouse'        => $this->whenLoaded('warehouse', fn () => [
                'id'   => $this->warehouse->id,
                'code' => $this->warehouse->code,
                'name' => $this->warehouse->name,
            ]),
            'type'             => $this->type,
            'quantity'         => (float) $this->quantity,
            'before_quantity'  => (float) $this->before_quantity,
            'after_quantity'   => (float) $this->after_quantity,
            'reference_id'     => $this->reference_id,
            'reference_type'   => $this->reference_type,
            'notes'            => $this->notes,
            'metadata'         => $this->metadata ?? [],
            'performed_by'     => $this->performed_by,
            'performed_at'     => $this->performed_at?->toISOString(),
            'created_at'       => $this->created_at?->toISOString(),
        ];
    }
}
