<?php

declare(strict_types=1);

namespace App\Http\Resources\Procurement;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseReturnResource extends JsonResource
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
            'purchase_receipt_id' => $this->purchase_receipt_id,
            'vendor_id' => $this->vendor_id,
            'code' => $this->code,
            'return_date' => $this->return_date?->toDateString(),
            'reason' => $this->reason,
            'status' => $this->status,
            'approval_status' => $this->approval_status,
            'refund_status' => $this->refund_status,
            'refund_amount' => $this->refund_amount,
            'restocking_fee' => $this->restocking_fee,
            'notes' => $this->notes,
            'approved_by' => $this->approved_by,
            'approved_at' => $this->approved_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'deleted_at' => $this->deleted_at?->toIso8601String(),

            // Relationships
            'purchase_order' => new PurchaseOrderResource($this->whenLoaded('purchaseOrder')),
            'purchase_receipt' => new PurchaseReceiptResource($this->whenLoaded('purchaseReceipt')),
            'vendor' => new VendorResource($this->whenLoaded('vendor')),
            'organization' => $this->whenLoaded('organization'),
            'branch' => $this->whenLoaded('branch'),
            'location' => $this->whenLoaded('location'),
            'items' => PurchaseReturnItemResource::collection($this->whenLoaded('items')),
            'approver' => $this->whenLoaded('approver'),
        ];
    }
}
