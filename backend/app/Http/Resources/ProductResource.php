<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'tenant_id'           => $this->tenant_id,
            'category_id'         => $this->category_id,
            'name'                => $this->name,
            'description'         => $this->description,
            'sku'                 => $this->sku,
            'barcode'             => $this->barcode,
            'price'               => (float) $this->price,
            'cost'                => $this->cost ? (float) $this->cost : null,
            'weight'              => $this->weight ? (float) $this->weight : null,
            'dimensions'          => $this->dimensions,
            'status'              => $this->status,
            'is_trackable'        => $this->is_trackable,
            'low_stock_threshold' => $this->low_stock_threshold,
            'images'              => $this->images ?? [],
            'metadata'            => $this->metadata,
            'category'            => new ProductCategoryResource($this->whenLoaded('category')),
            'inventory'           => new InventoryResource($this->whenLoaded('inventory')),
            'created_at'          => $this->created_at->toIso8601String(),
            'updated_at'          => $this->updated_at->toIso8601String(),
        ];
    }
}
