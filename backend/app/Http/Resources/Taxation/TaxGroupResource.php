<?php

declare(strict_types=1);

namespace App\Http\Resources\Taxation;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaxGroupResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'name' => $this->name,
            'code' => $this->code,
            'description' => $this->description,
            'application_type' => $this->application_type,
            'is_inclusive' => $this->is_inclusive,
            'is_active' => $this->is_active,
            'effective_from' => $this->effective_from?->format('Y-m-d'),
            'effective_to' => $this->effective_to?->format('Y-m-d'),
            'total_rate' => $this->getTotalRate(),
            'tax_rates' => TaxGroupRateResource::collection($this->whenLoaded('taxRates')),
            'metadata' => $this->metadata,
            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
