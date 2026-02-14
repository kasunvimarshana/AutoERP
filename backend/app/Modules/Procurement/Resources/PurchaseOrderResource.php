<?php

namespace App\Modules\Procurement\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Purchase Order Resource
 * 
 * Transforms purchase order model data for API responses
 * 
 * @package App\Modules\Procurement\Resources
 */
class PurchaseOrderResource extends JsonResource
{
    /**
     * Transform the resource into an array
     *
     * @param \Illuminate\Http\Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'supplier_id' => $this->supplier_id,
            'po_number' => $this->po_number,
            'order_date' => $this->order_date->toIso8601String(),
            'expected_delivery_date' => $this->expected_delivery_date ? $this->expected_delivery_date->toIso8601String() : null,
            'status' => $this->status,
            'subtotal' => $this->subtotal,
            'tax_amount' => $this->tax_amount,
            'discount_amount' => $this->discount_amount,
            'total_amount' => $this->total_amount,
            'notes' => $this->notes,
            'approved_by' => $this->approved_by,
            'approved_at' => $this->approved_at ? $this->approved_at->toIso8601String() : null,
            'supplier' => $this->whenLoaded('supplier', function () {
                return new SupplierResource($this->supplier);
            }),
            'tenant' => $this->whenLoaded('tenant', function () {
                return [
                    'id' => $this->tenant->id,
                    'name' => $this->tenant->name,
                    'domain' => $this->tenant->domain,
                ];
            }),
            'approved_by_user' => $this->whenLoaded('approvedBy', function () {
                return [
                    'id' => $this->approvedBy->id,
                    'name' => $this->approvedBy->name,
                    'email' => $this->approvedBy->email,
                ];
            }),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
