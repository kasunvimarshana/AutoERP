<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API Resource for Order.
 */
final class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'order_number'     => $this->order_number,
            'status'           => $this->status,
            'customer'         => [
                'id'    => $this->customer_id,
                'name'  => $this->customer_name,
                'email' => $this->customer_email,
            ],
            'items'            => $this->whenLoaded('items', fn () => $this->items->map(fn ($item) => [
                'id'          => $item->id,
                'product_id'  => $item->product_id,
                'sku'         => $item->sku,
                'name'        => $item->name,
                'quantity'    => $item->quantity,
                'unit_price'  => (float) $item->unit_price,
                'total_price' => (float) $item->total_price,
                'discount'    => (float) ($item->discount ?? 0),
            ])),
            'financials'       => [
                'subtotal' => (float) $this->subtotal,
                'tax'      => (float) ($this->tax ?? 0),
                'discount' => (float) ($this->discount ?? 0),
                'total'    => (float) $this->total,
                'currency' => $this->currency,
            ],
            'shipping_address' => $this->shipping_address,
            'billing_address'  => $this->billing_address,
            'notes'            => $this->notes,
            'metadata'         => $this->metadata,
            'tenant_id'        => $this->tenant_id,
            'completed_at'     => $this->completed_at?->toIso8601String(),
            'cancelled_at'     => $this->cancelled_at?->toIso8601String(),
            'created_at'       => $this->created_at?->toIso8601String(),
            'updated_at'       => $this->updated_at?->toIso8601String(),
        ];
    }
}
