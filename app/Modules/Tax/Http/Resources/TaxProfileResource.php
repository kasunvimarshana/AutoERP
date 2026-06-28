<?php

declare(strict_types=1);

namespace Modules\Tax\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class TaxProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'organization_unit_id' => $this->organization_unit_id,
            'customer_id' => $this->customer_id ?? null,
            'supplier_id' => $this->supplier_id ?? null,
            'party' => [
                'id' => $this->customer_id ?? $this->supplier_id,
                'code' => $this->party_code_snapshot,
                'name' => $this->party_name_snapshot,
            ],
            'tax_group_id' => $this->tax_group_id,
            'tax_group' => $this->whenLoaded('taxGroup', fn () => $this->taxGroup ? [
                'id' => $this->taxGroup->id,
                'code' => $this->taxGroup->code,
                'name' => $this->taxGroup->name,
            ] : null),
            'registration_number' => $this->registration_number,
            'exemption_status' => $this->exemption_status,
            'active' => (bool) $this->active,
        ];
    }
}
