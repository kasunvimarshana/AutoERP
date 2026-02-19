<?php

declare(strict_types=1);

namespace Modules\Purchase\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Core\Helpers\MathHelper;

class PurchaseOrderResource extends JsonResource
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
            'vendor_id' => $this->vendor_id,
            'po_code' => $this->po_code,
            'reference' => $this->reference,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'order_date' => $this->order_date?->toDateString(),
            'expected_delivery_date' => $this->expected_delivery_date?->toDateString(),
            'total_amount' => $this->total_amount,
            'notes' => $this->notes,
            'terms_conditions' => $this->terms_conditions,
            'created_by' => $this->created_by,
            'approved_by' => $this->approved_by,
            'approved_at' => $this->approved_at?->toISOString(),
            'sent_at' => $this->sent_at?->toISOString(),
            'confirmed_at' => $this->confirmed_at?->toISOString(),
            'cancelled_at' => $this->cancelled_at?->toISOString(),
            'cancellation_reason' => $this->cancellation_reason,
            'vendor' => new VendorResource($this->whenLoaded('vendor')),
            'items' => PurchaseOrderItemResource::collection($this->whenLoaded('items')),
            'items_count' => $this->when(
                $this->relationLoaded('items'),
                fn () => $this->items->count()
            ),
            'is_fully_received' => $this->when(
                $this->relationLoaded('items'),
                function () {
                    if ($this->items->isEmpty()) {
                        return false;
                    }
                    foreach ($this->items as $item) {
                        if (MathHelper::compare($item->quantity_received, $item->quantity) < 0) {
                            return false;
                        }
                    }

                    return true;
                }
            ),
            'is_fully_billed' => $this->when(
                $this->relationLoaded('items'),
                function () {
                    if ($this->items->isEmpty()) {
                        return false;
                    }
                    foreach ($this->items as $item) {
                        if (MathHelper::compare($item->quantity_billed, $item->quantity) < 0) {
                            return false;
                        }
                    }

                    return true;
                }
            ),
            'can_modify' => $this->status->canModify(),
            'can_approve' => $this->status->canApprove(),
            'can_cancel' => $this->status->canCancel(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'deleted_at' => $this->deleted_at?->toISOString(),
        ];
    }
}
