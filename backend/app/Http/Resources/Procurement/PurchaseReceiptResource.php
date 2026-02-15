<?php

declare(strict_types=1);

namespace App\Http\Resources\Procurement;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseReceiptResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'tenant_id' => $this->tenant_id,
            'organization_id' => $this->organization_id,
            'branch_id' => $this->branch_id,
            'location_id' => $this->location_id,
            'purchase_order_id' => $this->purchase_order_id,
            'vendor_id' => $this->vendor_id,
            'code' => $this->code,
            'receipt_date' => $this->receipt_date?->toDateString(),
            'delivery_note_number' => $this->delivery_note_number,
            'invoice_number' => $this->invoice_number,
            'status' => $this->status,
            'quality_check_status' => $this->quality_check_status,
            'quality_check_notes' => $this->quality_check_notes,
            'accepted_by' => $this->accepted_by,
            'accepted_at' => $this->accepted_at?->toIso8601String(),
            'rejected_quantity' => $this->rejected_quantity,
            'rejection_reason' => $this->rejection_reason,
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'deleted_at' => $this->deleted_at?->toIso8601String(),

            // Relationships
            'purchase_order' => new PurchaseOrderResource($this->whenLoaded('purchaseOrder')),
            'vendor' => new VendorResource($this->whenLoaded('vendor')),
            'organization' => $this->whenLoaded('organization'),
            'branch' => $this->whenLoaded('branch'),
            'location' => $this->whenLoaded('location'),
            'items' => PurchaseReceiptItemResource::collection($this->whenLoaded('items')),
            'acceptor' => $this->whenLoaded('acceptor'),
        ];
    }
}
