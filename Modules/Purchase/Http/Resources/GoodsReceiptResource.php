<?php

declare(strict_types=1);

namespace Modules\Purchase\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GoodsReceiptResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'organization_id' => $this->organization_id,
            'purchase_order_id' => $this->purchase_order_id,
            'vendor_id' => $this->vendor_id,
            'receipt_code' => $this->receipt_code,
            'receipt_date' => $this->receipt_date?->toDateString(),
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'delivery_note' => $this->delivery_note,
            'total_amount' => $this->total_amount,
            'notes' => $this->notes,
            'created_by' => $this->created_by,
            'confirmed_by' => $this->confirmed_by,
            'confirmed_at' => $this->confirmed_at?->toISOString(),
            'posted_to_inventory_at' => $this->posted_to_inventory_at?->toISOString(),
            'cancelled_at' => $this->cancelled_at?->toISOString(),
            'cancellation_reason' => $this->cancellation_reason,
            'vendor' => new VendorResource($this->whenLoaded('vendor')),
            'purchase_order' => $this->when(
                $this->relationLoaded('purchaseOrder'),
                fn () => [
                    'id' => $this->purchaseOrder->id,
                    'po_code' => $this->purchaseOrder->po_code,
                    'status' => $this->purchaseOrder->status->value,
                ]
            ),
            'items' => GoodsReceiptItemResource::collection($this->whenLoaded('items')),
            'items_count' => $this->when(
                $this->relationLoaded('items'),
                fn () => $this->items->count()
            ),
            'can_confirm' => $this->status->canConfirm(),
            'can_post_inventory' => $this->status->canPostInventory(),
            'can_cancel' => $this->status->canCancel(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'deleted_at' => $this->deleted_at?->toISOString(),
        ];
    }
}
