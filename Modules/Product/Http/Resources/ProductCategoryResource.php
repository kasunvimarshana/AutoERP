<?php

declare(strict_types=1);

namespace Modules\Product\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Product Category Resource
 */
class ProductCategoryResource extends JsonResource
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
            'parent_id' => $this->parent_id,
            'parent' => new ProductCategoryResource($this->whenLoaded('parent')),
            'name' => $this->name,
            'code' => $this->code,
            'description' => $this->description,
            'metadata' => $this->metadata ?? [],
            'is_active' => $this->is_active,
            'children' => ProductCategoryResource::collection($this->whenLoaded('children')),
            'children_count' => $this->when(
                $this->relationLoaded('children'),
                fn () => $this->children->count()
            ),
            'products_count' => $this->when(
                $this->relationLoaded('products'),
                fn () => $this->products->count()
            ),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'deleted_at' => $this->deleted_at?->toISOString(),
        ];
    }
}
