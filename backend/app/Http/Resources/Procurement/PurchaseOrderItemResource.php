<?php

declare(strict_types=1);

namespace App\Http\Resources\Procurement;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseOrderItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'purchase_order_id' => $this->purchase_order_id,
            'product_id' => $this->product_id,
            'variant_id' => $this->variant_id,
            'description' => $this->description,
            'quantity' => $this->quantity,
            'received_quantity' => $this->received_quantity,
            'unit_price' => $this->unit_price,
            'discount_type' => $this->discount_type,
            'discount_amount' => $this->discount_amount,
            'discount_percentage' => $this->discount_percentage,
            'tax_rate_id' => $this->tax_rate_id,
            'tax_amount' => $this->tax_amount,
            'line_total' => $this->line_total,
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),

            // Calculated fields
            'pending_quantity' => $this->getPendingQuantity(),
            'is_fully_received' => $this->isFullyReceived(),

            // Relationships
            'product' => $this->whenLoaded('product'),
            'variant' => $this->whenLoaded('variant'),
            'tax_rate' => $this->whenLoaded('taxRate'),
        ];
    }
}
