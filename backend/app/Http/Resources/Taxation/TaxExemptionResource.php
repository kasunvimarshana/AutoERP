<?php

declare(strict_types=1);

namespace App\Http\Resources\Taxation;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaxExemptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'name' => $this->name,
            'exemption_number' => $this->exemption_number,
            'entity_type' => $this->entity_type,
            'entity_id' => $this->entity_id,
            'tax_rate_id' => $this->tax_rate_id,
            'tax_group_id' => $this->tax_group_id,
            'exemption_type' => $this->exemption_type,
            'exemption_rate' => $this->exemption_rate,
            'reason' => $this->reason,
            'certificate_number' => $this->certificate_number,
            'valid_from' => $this->valid_from?->format('Y-m-d'),
            'valid_to' => $this->valid_to?->format('Y-m-d'),
            'is_active' => $this->is_active,
            'metadata' => $this->metadata,
            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
