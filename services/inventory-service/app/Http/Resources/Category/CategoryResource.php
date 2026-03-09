<?php

namespace App\Http\Resources\Category;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'tenant_id'   => $this->tenant_id,
            'name'        => $this->name,
            'slug'        => $this->slug,
            'description' => $this->description,
            'parent_id'   => $this->parent_id,
            'parent'      => new CategoryResource($this->whenLoaded('parent')),
            'children'    => CategoryResource::collection($this->whenLoaded('children')),
            'metadata'    => $this->metadata ?? [],
            'sort_order'  => (int) $this->sort_order,
            'is_active'   => (bool) $this->is_active,
            'image'       => $this->image,
            'product_count' => $this->when(
                isset($this->products_count),
                fn () => (int) $this->products_count
            ),
            'depth'       => $this->when(isset($this->depth), fn () => (int) $this->depth),
            'path'        => $this->when(isset($this->path), fn () => $this->path),
            'created_at'  => $this->created_at?->toISOString(),
            'updated_at'  => $this->updated_at?->toISOString(),
        ];
    }
}
