<?php

declare(strict_types=1);

namespace App\Http\Resources\Sales;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalesOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'order_number' => $this->order_number,
            'quote_id' => $this->quote_id,
            'customer_id' => $this->customer_id,
            'customer_name' => $this->customer?->name,
            'customer_email' => $this->customer_email,
            'customer_phone' => $this->customer_phone,
            'status' => $this->status,
            'payment_status' => $this->payment_status,
            'fulfillment_status' => $this->fulfillment_status,
            'order_date' => $this->order_date?->toDateString(),
            'expected_delivery_date' => $this->expected_delivery_date?->toDateString(),
            'delivery_date' => $this->delivery_date?->toDateString(),
            'currency_id' => $this->currency_id,
            'currency_code' => $this->currency?->code,
            'exchange_rate' => $this->exchange_rate,
            'subtotal' => $this->subtotal,
            'discount_type' => $this->discount_type,
            'discount_value' => $this->discount_value,
            'discount_amount' => $this->discount_amount,
            'tax_amount' => $this->tax_amount,
            'shipping_amount' => $this->shipping_amount,
            'adjustment_amount' => $this->adjustment_amount,
            'total_amount' => $this->total_amount,
            'billing_address' => $this->billing_address,
            'shipping_address' => $this->shipping_address,
            'notes' => $this->notes,
            'terms_and_conditions' => $this->terms_and_conditions,
            'is_editable' => $this->isEditable(),
            'is_approved' => $this->isApproved(),
            'is_completed' => $this->isCompleted(),
            'items' => SalesOrderItemResource::collection($this->whenLoaded('items')),
            'approved_at' => $this->approved_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
