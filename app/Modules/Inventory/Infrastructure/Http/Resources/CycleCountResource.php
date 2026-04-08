<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Http\Resources;

use Modules\Core\Infrastructure\Http\Resources\BaseResource;

class CycleCountResource extends BaseResource
{
    public function toArray($request): array
    {
        return [
            'id'           => $this->id,
            'count_number' => $this->count_number,
            'warehouse_id' => $this->warehouse_id,
            'location_id'  => $this->location_id,
            'status'       => $this->status,
            'counted_at'   => $this->counted_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'counted_by'   => $this->counted_by,
            'notes'        => $this->notes,
            'lines'        => CycleCountLineResource::collection($this->whenLoaded('lines')),
            'created_at'   => $this->created_at?->toIso8601String(),
            'updated_at'   => $this->updated_at?->toIso8601String(),
        ];
    }
}
