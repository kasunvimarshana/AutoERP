<?php

declare(strict_types=1);

namespace Modules\Warehouse\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class LocationResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'           => $this->id,
            'uuid'         => $this->uuid,
            'warehouse_id' => $this->warehouse_id,
            'parent_id'    => $this->parent_id,
            'name'         => $this->name,
            'code'         => $this->code,
            'type'         => $this->type,
            'path'         => $this->path,
            'level'        => $this->level,
            'capacity'     => $this->capacity,
            'is_active'    => $this->is_active,
            'metadata'     => $this->metadata,
            'created_at'   => $this->created_at,
            'updated_at'   => $this->updated_at,
            'children'     => $this->when(
                $this->resource->relationLoaded('children_tree') || isset($this->children_tree),
                fn () => LocationResource::collection($this->children_tree ?? collect()),
            ),
        ];
    }
}
