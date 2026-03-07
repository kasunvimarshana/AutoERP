<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'user_id' => $this->user_id,
            'status' => $this->status,
            'total_amount' => $this->total_amount,
            'shipping_address' => $this->shipping_address,
            'notes' => $this->notes,
            'items' => $this->whenLoaded('items', fn() =>
                $this->items->map(fn($item) => [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'warehouse_id' => $item->warehouse_id,
                    'product_name' => $item->product_name,
                    'product_sku' => $item->product_sku,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'subtotal' => $item->subtotal,
                ])
            ),
            'saga_steps_count' => $this->whenLoaded('sagaLogs', fn() => $this->sagaLogs->count()),
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
