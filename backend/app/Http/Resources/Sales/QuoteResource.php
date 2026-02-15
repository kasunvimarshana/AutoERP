<?php

declare(strict_types=1);

namespace App\Http\Resources\Sales;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuoteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'quote_number' => $this->quote_number,
            'customer_id' => $this->customer_id,
            'customer_name' => $this->customer?->name,
            'customer_email' => $this->customer_email,
            'customer_phone' => $this->customer_phone,
            'status' => $this->status,
            'quote_date' => $this->quote_date?->toDateString(),
            'valid_until' => $this->valid_until?->toDateString(),
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
            'is_expired' => $this->isExpired(),
            'is_converted' => $this->isConverted(),
            'items' => QuoteItemResource::collection($this->whenLoaded('items')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
