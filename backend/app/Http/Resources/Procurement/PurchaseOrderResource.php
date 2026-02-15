<?php

declare(strict_types=1);

namespace App\Http\Resources\Procurement;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseOrderResource extends JsonResource
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
            'vendor_id' => $this->vendor_id,
            'code' => $this->code,
            'reference_number' => $this->reference_number,
            'order_date' => $this->order_date?->toDateString(),
            'expected_delivery_date' => $this->expected_delivery_date?->toDateString(),
            'actual_delivery_date' => $this->actual_delivery_date?->toDateString(),
            'status' => $this->status,
            'approval_status' => $this->approval_status,
            'payment_status' => $this->payment_status,
            'currency_id' => $this->currency_id,
            'exchange_rate' => $this->exchange_rate,
            'subtotal' => $this->subtotal,
            'discount_type' => $this->discount_type,
            'discount_amount' => $this->discount_amount,
            'discount_percentage' => $this->discount_percentage,
            'tax_amount' => $this->tax_amount,
            'shipping_amount' => $this->shipping_amount,
            'other_charges' => $this->other_charges,
            'total_amount' => $this->total_amount,
            'paid_amount' => $this->paid_amount,
            'balance_amount' => $this->balance_amount,
            'payment_terms_days' => $this->payment_terms_days,
            'payment_method' => $this->payment_method,
            'shipping_method' => $this->shipping_method,
            'notes' => $this->notes,
            'internal_notes' => $this->internal_notes,
            'terms_conditions' => $this->terms_conditions,
            'tags' => $this->tags,
            'approved_by' => $this->approved_by,
            'approved_at' => $this->approved_at?->toIso8601String(),
            'cancelled_by' => $this->cancelled_by,
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            'cancellation_reason' => $this->cancellation_reason,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'deleted_at' => $this->deleted_at?->toIso8601String(),

            // Calculated fields
            'received_percentage' => $this->when($this->items, fn() => $this->getReceivedPercentage()),
            'payment_percentage' => $this->getPaymentPercentage(),

            // Relationships
            'vendor' => new VendorResource($this->whenLoaded('vendor')),
            'organization' => $this->whenLoaded('organization'),
            'branch' => $this->whenLoaded('branch'),
            'location' => $this->whenLoaded('location'),
            'currency' => $this->whenLoaded('currency'),
            'items' => PurchaseOrderItemResource::collection($this->whenLoaded('items')),
            'receipts' => PurchaseReceiptResource::collection($this->whenLoaded('receipts')),
            'approver' => $this->whenLoaded('approver'),
            'canceller' => $this->whenLoaded('canceller'),
        ];
    }
}
