<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'name' => $this->name,
            'sku' => $this->sku,
            'description' => $this->description,
            'price' => $this->price,
            'cost' => $this->cost,
            'weight' => $this->weight,
            'dimensions' => $this->dimensions ?? [],
            'images' => $this->images ?? [],
            'tags' => $this->tags ?? [],
            'is_active' => $this->is_active,
            'category' => $this->whenLoaded('category', fn() => new CategoryResource($this->category)),
            'category_id' => $this->category_id,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
