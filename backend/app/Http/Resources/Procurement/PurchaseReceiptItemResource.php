<?php

declare(strict_types=1);

namespace App\Http\Resources\Procurement;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseReceiptItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'purchase_receipt_id' => $this->purchase_receipt_id,
            'purchase_order_item_id' => $this->purchase_order_item_id,
            'product_id' => $this->product_id,
            'variant_id' => $this->variant_id,
            'ordered_quantity' => $this->ordered_quantity,
            'received_quantity' => $this->received_quantity,
            'accepted_quantity' => $this->accepted_quantity,
            'rejected_quantity' => $this->rejected_quantity,
            'unit_cost' => $this->unit_cost,
            'batch_number' => $this->batch_number,
            'serial_number' => $this->serial_number,
            'lot_number' => $this->lot_number,
            'expiry_date' => $this->expiry_date?->toDateString(),
            'manufacturing_date' => $this->manufacturing_date?->toDateString(),
            'quality_status' => $this->quality_status,
            'rejection_reason' => $this->rejection_reason,
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),

            // Relationships
            'product' => $this->whenLoaded('product'),
            'variant' => $this->whenLoaded('variant'),
        ];
    }
}
