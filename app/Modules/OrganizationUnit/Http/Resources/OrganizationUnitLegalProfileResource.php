<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class OrganizationUnitLegalProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->resource->getKey(),
            'organization_unit_id' => (int) $this->resource->organization_unit_id,
            'legal_name' => (string) $this->resource->legal_name,
            'tin' => $this->resource->tin,
            'vat_registration_number' => $this->resource->vat_registration_number,
            'svat_registration_number' => $this->resource->svat_registration_number,
            'address_line_1' => (string) $this->resource->address_line_1,
            'address_line_2' => $this->resource->address_line_2,
            'city' => $this->resource->city,
            'state' => $this->resource->state,
            'postal_code' => $this->resource->postal_code,
            'country' => $this->resource->country,
            'phone' => $this->resource->phone,
            'email' => $this->resource->email,
            'row_version' => (int) $this->resource->row_version,
        ];
    }
}
