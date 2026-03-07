<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'tenant_id'   => $this->tenant_id,
            'parent_id'   => $this->parent_id,
            'name'        => $this->name,
            'slug'        => $this->slug,
            'description' => $this->description,
            'is_active'   => (bool) $this->is_active,
            'sort_order'  => (int) $this->sort_order,
            'metadata'    => $this->metadata,
            'parent'      => $this->whenLoaded('parent', fn() => new CategoryResource($this->parent)),
            'children'    => $this->whenLoaded('children', fn() => CategoryResource::collection($this->children)),
            'created_at'  => $this->created_at?->toIso8601String(),
            'updated_at'  => $this->updated_at?->toIso8601String(),
        ];
    }
}
