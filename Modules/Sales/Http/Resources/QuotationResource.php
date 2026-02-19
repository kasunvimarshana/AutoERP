<?php

declare(strict_types=1);

namespace Modules\Sales\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\CRM\Http\Resources\CustomerResource;

class QuotationResource extends JsonResource
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
            'customer_id' => $this->customer_id,
            'quotation_code' => $this->quotation_code,
            'reference' => $this->reference,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'quotation_date' => $this->quotation_date?->toDateString(),
            'valid_until' => $this->valid_until?->toDateString(),
            'subtotal' => $this->subtotal,
            'tax_amount' => $this->tax_amount,
            'discount_amount' => $this->discount_amount,
            'total_amount' => $this->total_amount,
            'notes' => $this->notes,
            'terms_conditions' => $this->terms_conditions,
            'created_by' => $this->created_by,
            'sent_at' => $this->sent_at?->toISOString(),
            'accepted_at' => $this->accepted_at?->toISOString(),
            'rejected_at' => $this->rejected_at?->toISOString(),
            'expired_at' => $this->expired_at?->toISOString(),
            'converted_at' => $this->converted_at?->toISOString(),
            'converted_order_id' => $this->converted_order_id,
            'customer' => new CustomerResource($this->whenLoaded('customer')),
            'items' => QuotationItemResource::collection($this->whenLoaded('items')),
            'items_count' => $this->when(
                $this->relationLoaded('items'),
                fn () => $this->items->count()
            ),
            'can_send' => $this->status->canSend(),
            'can_convert' => $this->status->canConvert(),
            'can_modify' => $this->status->canModify(),
            'is_final' => $this->status->isFinal(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'deleted_at' => $this->deleted_at?->toISOString(),
        ];
    }
}
