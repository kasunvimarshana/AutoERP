<?php

declare(strict_types=1);

namespace Modules\Product\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Product Unit Conversion Resource
 */
class ProductUnitConversionResource extends JsonResource
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
            'from_unit_id' => $this->from_unit_id,
            'from_unit' => new UnitResource($this->whenLoaded('fromUnit')),
            'to_unit_id' => $this->to_unit_id,
            'to_unit' => new UnitResource($this->whenLoaded('toUnit')),
            'conversion_factor' => $this->conversion_factor,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
