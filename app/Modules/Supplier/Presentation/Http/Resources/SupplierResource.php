<?php

declare(strict_types=1);

namespace Modules\Supplier\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class SupplierResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $address = $this->resource->relationLoaded('primaryAddress')
            ? $this->resource->primaryAddress
            : null;

        return [
            'id' => $this->resource->getKey(),
            'tenant_id' => $this->resource->tenant_id,
            'organization_unit_id' => $this->resource->organization_unit_id,
            'supplier_code' => $this->resource->supplier_code,
            'name' => $this->resource->supplier_name,
            'display_name' => $this->resource->display_name,
            'email' => $this->resource->email,
            'phone' => $this->resource->phone,
            'mobile' => $this->resource->mobile,
            'tax_number' => $this->resource->tax_number,
            'vat_number' => $this->resource->vat_number,
            'credit_limit' => $this->resource->credit_limit,
            'current_credit_balance' => null,
            'available_credit' => null,
            'payment_terms_days' => $this->resource->payment_terms_days,
            'status' => $this->resource->status,
            'notes' => $this->resource->notes,
            'address' => $address === null ? null : [
                'label' => $address->label,
                'address_line_1' => $address->address_line1,
                'address_line_2' => $address->address_line2,
                'city' => $address->city,
                'state_province' => $address->state,
                'postal_code' => $address->postal_code,
                'country_name' => $address->country_name,
            ],
            'row_version' => $this->resource->row_version,
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
        ];
    }
}
