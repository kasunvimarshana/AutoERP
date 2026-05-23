<?php

declare(strict_types=1);

namespace Modules\UOM\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UomConversionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'row_version' => $this->row_version,
            'tenant_id' => $this->tenant_id,
            'organization_unit_id' => $this->organization_unit_id,
            'from_uom_id' => $this->from_uom_id,
            'to_uom_id' => $this->to_uom_id,
            'factor' => $this->factor,
            'item_id' => $this->item_id,
            'is_bidirectional' => $this->is_bidirectional,
            'is_active' => $this->is_active,
            'metadata' => $this->metadata,
            'from_uom' => $this->whenLoaded('fromUom', fn () => new UnitOfMeasureResource($this->fromUom)),
            'to_uom' => $this->whenLoaded('toUom', fn () => new UnitOfMeasureResource($this->toUom)),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
