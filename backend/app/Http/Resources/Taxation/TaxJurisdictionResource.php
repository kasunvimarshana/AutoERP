<?php

declare(strict_types=1);

namespace App\Http\Resources\Taxation;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaxJurisdictionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'name' => $this->name,
            'code' => $this->code,
            'jurisdiction_type' => $this->jurisdiction_type,
            'country_code' => $this->country_code,
            'state_code' => $this->state_code,
            'city_name' => $this->city_name,
            'postal_code' => $this->postal_code,
            'tax_rate_id' => $this->tax_rate_id,
            'tax_group_id' => $this->tax_group_id,
            'priority' => $this->priority,
            'is_reverse_charge' => $this->is_reverse_charge,
            'is_active' => $this->is_active,
            'rules' => $this->rules,
            'metadata' => $this->metadata,
            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
