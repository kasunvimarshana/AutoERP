<?php

namespace App\Modules\Procurement\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Supplier Resource
 * 
 * Transforms supplier model data for API responses
 * 
 * @package App\Modules\Procurement\Resources
 */
class SupplierResource extends JsonResource
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
            'name' => $this->name,
            'contact_person' => $this->contact_person,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'city' => $this->city,
            'state' => $this->state,
            'postal_code' => $this->postal_code,
            'country' => $this->country,
            'payment_terms' => $this->payment_terms,
            'tax_id' => $this->tax_id,
            'status' => $this->status,
            'notes' => $this->notes,
            'purchase_orders' => $this->whenLoaded('purchaseOrders', function () {
                return PurchaseOrderResource::collection($this->purchaseOrders);
            }),
            'tenant' => $this->whenLoaded('tenant', function () {
                return [
                    'id' => $this->tenant->id,
                    'name' => $this->tenant->name,
                    'domain' => $this->tenant->domain,
                ];
            }),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
