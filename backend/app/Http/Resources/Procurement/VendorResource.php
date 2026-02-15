<?php

declare(strict_types=1);

namespace App\Http\Resources\Procurement;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VendorResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'tenant_id' => $this->tenant_id,
            'organization_id' => $this->organization_id,
            'branch_id' => $this->branch_id,
            'type' => $this->type,
            'code' => $this->code,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'mobile' => $this->mobile,
            'website' => $this->website,
            'tax_number' => $this->tax_number,
            'company_name' => $this->company_name,
            'industry' => $this->industry,
            'established_date' => $this->established_date?->toDateString(),
            'credit_limit' => $this->credit_limit,
            'payment_terms_days' => $this->payment_terms_days,
            'payment_terms_type' => $this->payment_terms_type,
            'currency_id' => $this->currency_id,
            'payment_method' => $this->payment_method,
            'bank_name' => $this->bank_name,
            'bank_account_number' => $this->bank_account_number,
            'bank_branch' => $this->bank_branch,
            'swift_code' => $this->swift_code,
            'is_active' => $this->is_active,
            'is_verified' => $this->is_verified,
            'status' => $this->status,
            'rating' => $this->rating,
            'vendor_category' => $this->vendor_category,
            'source' => $this->source,
            'assigned_to' => $this->assigned_to,
            'notes' => $this->notes,
            'tags' => $this->tags,
            'custom_fields' => $this->custom_fields,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'deleted_at' => $this->deleted_at?->toIso8601String(),

            // Relationships
            'organization' => $this->whenLoaded('organization'),
            'branch' => $this->whenLoaded('branch'),
            'currency' => $this->whenLoaded('currency'),
            'assigned_user' => $this->whenLoaded('assignedUser'),
            'contacts' => VendorContactResource::collection($this->whenLoaded('contacts')),
            'purchase_orders' => $this->whenLoaded('purchaseOrders'),
        ];
    }
}
