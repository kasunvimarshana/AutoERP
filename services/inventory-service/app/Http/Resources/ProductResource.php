<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Product API Resource
 */
class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'tenant_id'     => $this->tenant_id,
            'category_id'   => $this->category_id,
            'sku'           => $this->sku,
            'name'          => $this->name,
            'description'   => $this->description,
            'price'         => $this->price,
            'cost_price'    => $this->cost_price,
            'quantity'      => $this->quantity,
            'reorder_level' => $this->reorder_level,
            'unit'          => $this->unit,
            'is_active'     => $this->is_active,
            'needs_reorder' => $this->needsReorder(),
            'category'      => $this->whenLoaded('category', fn () => [
                'id'   => $this->category->id,
                'name' => $this->category->name,
            ]),
            'created_at'    => $this->created_at?->toIso8601String(),
            'updated_at'    => $this->updated_at?->toIso8601String(),
        ];
    }
}
