<?php

declare(strict_types=1);

namespace Modules\CRM\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
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
            'customer_code' => $this->customer_code,
            'customer_type' => $this->customer_type->value,
            'customer_type_label' => $this->customer_type->label(),
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'company_name' => $this->company_name,
            'tax_id' => $this->tax_id,
            'phone' => $this->phone,
            'email' => $this->email,
            'website' => $this->website,
            'billing_address' => [
                'line1' => $this->billing_address_line1,
                'line2' => $this->billing_address_line2,
                'city' => $this->billing_city,
                'state' => $this->billing_state,
                'postal_code' => $this->billing_postal_code,
                'country' => $this->billing_country,
            ],
            'shipping_address' => [
                'line1' => $this->shipping_address_line1,
                'line2' => $this->shipping_address_line2,
                'city' => $this->shipping_city,
                'state' => $this->shipping_state,
                'postal_code' => $this->shipping_postal_code,
                'country' => $this->shipping_country,
            ],
            'credit_limit' => $this->credit_limit,
            'payment_terms' => $this->payment_terms,
            'notes' => $this->notes,
            'metadata' => $this->metadata ?? [],
            'contacts_count' => $this->when(
                $this->relationLoaded('contacts'),
                fn () => $this->contacts->count()
            ),
            'opportunities_count' => $this->when(
                $this->relationLoaded('opportunities'),
                fn () => $this->opportunities->count()
            ),
            'primary_contact' => new ContactResource($this->whenLoaded('primaryContact')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'deleted_at' => $this->deleted_at?->toISOString(),
        ];
    }
}
