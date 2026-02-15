<?php

declare(strict_types=1);

namespace App\Http\Resources\Sales;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuoteItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'product_id' => $this->product_id,
            'product_name' => $this->product?->name,
            'variant_id' => $this->variant_id,
            'item_name' => $this->item_name,
            'item_description' => $this->item_description,
            'quantity' => $this->quantity,
            'unit_id' => $this->unit_id,
            'unit_name' => $this->unit?->name,
            'unit_price' => $this->unit_price,
            'discount_type' => $this->discount_type,
            'discount_value' => $this->discount_value,
            'discount_amount' => $this->discount_amount,
            'tax_rate_id' => $this->tax_rate_id,
            'tax_percentage' => $this->tax_percentage,
            'tax_amount' => $this->tax_amount,
            'subtotal' => $this->subtotal,
            'total' => $this->total,
        ];
    }
}
