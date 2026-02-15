<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UnitOfMeasureResource extends JsonResource
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
            'name' => $this->name,
            'symbol' => $this->symbol,
            'code' => $this->code,
            'type' => $this->type,
            'base_unit_id' => $this->base_unit_id,
            'conversion_factor' => (float) $this->conversion_factor,
            'is_active' => $this->is_active,
            'is_system' => $this->is_system,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            
            // Computed properties
            'display_name' => "{$this->name} ({$this->symbol})",
            'is_base_unit' => $this->base_unit_id === null,
            'can_delete' => !$this->is_system,
            
            // Related data (when loaded)
            'base_unit' => $this->whenLoaded('baseUnit', function () {
                return [
                    'id' => $this->baseUnit->id,
                    'name' => $this->baseUnit->name,
                    'symbol' => $this->baseUnit->symbol,
                    'code' => $this->baseUnit->code,
                ];
            }),
            'derived_units' => $this->whenLoaded('derivedUnits', function () {
                return self::collection($this->derivedUnits);
            }),
            'derived_units_count' => $this->when(
                isset($this->derived_units_count),
                $this->derived_units_count
            ),
        ];
    }
}
