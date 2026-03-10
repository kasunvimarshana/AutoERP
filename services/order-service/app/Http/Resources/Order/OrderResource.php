<?php

declare(strict_types=1);

namespace App\Http\Resources\Order;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * OrderResource
 *
 * @mixin \App\Infrastructure\Persistence\Models\Order
 */
class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'tenant_id'        => $this->tenant_id,
            'user_id'          => $this->user_id,
            'status'           => $this->status,
            'currency'         => $this->currency,
            'subtotal'         => $this->subtotal,
            'tax_amount'       => $this->tax_amount,
            'shipping_amount'  => $this->shipping_amount,
            'discount_amount'  => $this->discount_amount,
            'total_amount'     => $this->total_amount,
            'payment_id'       => $this->payment_id,
            'payment_status'   => $this->payment_status,
            'shipping_address' => $this->shipping_address,
            'notes'            => $this->notes,
            'items'            => $this->whenLoaded('items', fn () =>
                $this->items->map(fn ($item) => [
                    'id'              => $item->id,
                    'product_id'      => $item->product_id,
                    'product_name'    => $item->product_name,
                    'product_code'    => $item->product_code,
                    'product_sku'     => $item->product_sku,
                    'quantity'        => $item->quantity,
                    'unit_price'      => $item->unit_price,
                    'discount_amount' => $item->discount_amount,
                    'tax_amount'      => $item->tax_amount,
                    'line_total'      => $item->line_total,
                    'currency'        => $item->currency,
                ])
            ),
            'confirmed_at'  => $this->confirmed_at?->toIso8601String(),
            'shipped_at'    => $this->shipped_at?->toIso8601String(),
            'delivered_at'  => $this->delivered_at?->toIso8601String(),
            'created_at'    => $this->created_at?->toIso8601String(),
            'updated_at'    => $this->updated_at?->toIso8601String(),
        ];
    }
}
