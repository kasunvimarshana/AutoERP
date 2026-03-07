<?php

namespace App\Modules\Order\Resources;

use App\Modules\Product\Resources\ProductResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'tenant_id'      => $this->tenant_id,
            'order_number'   => $this->order_number,
            'customer_name'  => $this->customer_name,
            'customer_email' => $this->customer_email,
            'status'         => $this->status,
            'subtotal'       => (float) $this->subtotal,
            'tax'            => (float) $this->tax,
            'total'          => (float) $this->total,
            'notes'          => $this->notes,
            'metadata'       => $this->metadata ?? [],
            'items'          => $this->whenLoaded('items', function () {
                return $this->items->map(fn($item) => [
                    'id'          => $item->id,
                    'product_id'  => $item->product_id,
                    'quantity'    => $item->quantity,
                    'unit_price'  => (float) $item->unit_price,
                    'total_price' => (float) $item->total_price,
                    'product'     => $item->relationLoaded('product')
                        ? new ProductResource($item->product)
                        : null,
                ]);
            }),
            'created_at'  => $this->created_at?->toIso8601String(),
            'updated_at'  => $this->updated_at?->toIso8601String(),
        ];
    }
}
