<?php

declare(strict_types=1);

namespace Modules\Procurement\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Purchase Order Resource
 *
 * Transforms purchase order data for API responses.
 */
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
            'order_number' => $this->order_number,
            'purchase_requisition_id' => $this->purchase_requisition_id,
            'supplier_id' => $this->supplier_id,
            'order_date' => $this->order_date?->toIso8601String(),
            'expected_delivery_date' => $this->expected_delivery_date?->toIso8601String(),
            'status' => $this->status,
            'payment_status' => $this->payment_status,
            'payment_terms' => $this->payment_terms,
            'currency' => $this->currency,
            'subtotal' => $this->subtotal,
            'tax_amount' => $this->tax_amount,
            'discount_amount' => $this->discount_amount,
            'shipping_amount' => $this->shipping_amount,
            'total_amount' => $this->total_amount,
            'notes' => $this->notes,
            'internal_notes' => $this->internal_notes,
            'terms_and_conditions' => $this->terms_and_conditions,
            'supplier' => new SupplierResource($this->whenLoaded('supplier')),
            'lines' => PurchaseOrderLineResource::collection($this->whenLoaded('lines')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
