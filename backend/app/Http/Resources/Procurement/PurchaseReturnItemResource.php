<?php

declare(strict_types=1);

namespace App\Http\Resources\Procurement;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseReturnItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'purchase_return_id' => $this->purchase_return_id,
            'purchase_receipt_item_id' => $this->purchase_receipt_item_id,
            'product_id' => $this->product_id,
            'variant_id' => $this->variant_id,
            'quantity' => $this->quantity,
            'unit_cost' => $this->unit_cost,
            'batch_number' => $this->batch_number,
            'serial_number' => $this->serial_number,
            'lot_number' => $this->lot_number,
            'reason' => $this->reason,
            'condition' => $this->condition,
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),

            // Relationships
            'product' => $this->whenLoaded('product'),
            'variant' => $this->whenLoaded('variant'),
        ];
    }
}
