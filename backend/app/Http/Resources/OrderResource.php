<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'tenant_id'        => $this->tenant_id,
            'user_id'          => $this->user_id,
            'order_number'     => $this->order_number,
            'status'           => $this->status,
            'payment_status'   => $this->payment_status,
            'subtotal'         => (float) $this->subtotal,
            'tax'              => (float) $this->tax,
            'discount'         => (float) $this->discount,
            'total'            => (float) $this->total,
            'currency'         => $this->currency,
            'notes'            => $this->notes,
            'shipping_address' => $this->shipping_address,
            'billing_address'  => $this->billing_address,
            'metadata'         => $this->metadata,
            'user'             => new UserResource($this->whenLoaded('user')),
            'items'            => OrderItemResource::collection($this->whenLoaded('items')),
            'items_count'      => $this->when(!$this->relationLoaded('items'), $this->items_count ?? null),
            'created_at'       => $this->created_at->toIso8601String(),
            'updated_at'       => $this->updated_at->toIso8601String(),
        ];
    }
}
