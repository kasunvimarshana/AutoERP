<?php

declare(strict_types=1);

namespace Modules\Product\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Product Resource
 */
class ProductResource extends JsonResource
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
            'code' => $this->code,
            'type' => $this->type->value,
            'type_label' => $this->type->label(),
            'description' => $this->description,
            'category_id' => $this->category_id,
            'category' => new ProductCategoryResource($this->whenLoaded('category')),
            'buying_unit_id' => $this->buying_unit_id,
            'buying_unit' => new UnitResource($this->whenLoaded('buyingUnit')),
            'selling_unit_id' => $this->selling_unit_id,
            'selling_unit' => new UnitResource($this->whenLoaded('sellingUnit')),
            'metadata' => $this->metadata ?? [],
            'is_active' => $this->is_active,
            'has_inventory' => $this->type->hasInventory(),
            'bundle_items' => ProductBundleResource::collection($this->whenLoaded('bundleItems')),
            'bundle_items_count' => $this->when(
                $this->relationLoaded('bundleItems'),
                fn () => $this->bundleItems->count()
            ),
            'composite_parts' => ProductCompositeResource::collection($this->whenLoaded('compositeParts')),
            'composite_parts_count' => $this->when(
                $this->relationLoaded('compositeParts'),
                fn () => $this->compositeParts->count()
            ),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'deleted_at' => $this->deleted_at?->toISOString(),
        ];
    }
}
