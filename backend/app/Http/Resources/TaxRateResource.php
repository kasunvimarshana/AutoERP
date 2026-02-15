<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaxRateResource extends JsonResource
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
            'uuid' => $this->uuid,
            'tenant_id' => $this->tenant_id,
            'name' => $this->name,
            'code' => $this->code,
            'description' => $this->description,
            'rate' => (float) $this->rate,
            'type' => $this->type,
            'is_compound' => $this->is_compound,
            'is_active' => $this->is_active,
            'effective_from' => $this->effective_from?->toIso8601String(),
            'effective_to' => $this->effective_to?->toIso8601String(),
            'metadata' => $this->metadata,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'deleted_at' => $this->deleted_at?->toIso8601String(),
            
            // Computed properties
            'rate_percentage' => (float) $this->rate,
            'rate_formatted' => number_format((float) $this->rate, 2) . '%',
            'display_name' => "{$this->name} ({$this->rate}%)",
            'is_currently_effective' => $this->isCurrentlyEffective(),
            
            // Related data (when loaded)
            'tenant' => $this->whenLoaded('tenant', function () {
                return [
                    'id' => $this->tenant->id,
                    'uuid' => $this->tenant->uuid,
                    'name' => $this->tenant->name,
                ];
            }),
            'created_by_user' => $this->whenLoaded('createdBy', function () {
                return [
                    'id' => $this->createdBy->id,
                    'name' => $this->createdBy->name,
                    'email' => $this->createdBy->email,
                ];
            }),
            'updated_by_user' => $this->whenLoaded('updatedBy', function () {
                return [
                    'id' => $this->updatedBy->id,
                    'name' => $this->updatedBy->name,
                    'email' => $this->updatedBy->email,
                ];
            }),
        ];
    }
    
    /**
     * Check if tax rate is currently effective.
     */
    protected function isCurrentlyEffective(): bool
    {
        $now = now();
        
        $afterStart = !$this->effective_from || $this->effective_from->lte($now);
        $beforeEnd = !$this->effective_to || $this->effective_to->gte($now);
        
        return $this->is_active && $afterStart && $beforeEnd;
    }
}
