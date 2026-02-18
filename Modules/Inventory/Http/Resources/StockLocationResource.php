<?php

declare(strict_types=1);

namespace Modules\Inventory\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockLocationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'warehouse_id' => $this->warehouse_id,
            'parent_id' => $this->parent_id,
            'code' => $this->code,
            'name' => $this->name,
            'location_type' => $this->location_type,
            'full_code' => $this->getFullCode(),
            'aisle' => $this->aisle,
            'rack' => $this->rack,
            'shelf' => $this->shelf,
            'bin' => $this->bin,
            'capacity' => $this->capacity,
            'capacity_unit' => $this->capacity_unit,
            'remaining_capacity' => $this->getRemainingCapacity(),
            'is_active' => $this->is_active,
            'warehouse' => new WarehouseResource($this->whenLoaded('warehouse')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
