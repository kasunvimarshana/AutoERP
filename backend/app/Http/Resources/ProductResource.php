<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API Resource for Product.
 */
final class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'sku'              => $this->sku,
            'name'             => $this->name,
            'description'      => $this->description,
            'category'         => $this->category,
            'price'            => (float) $this->price,
            'cost'             => (float) $this->cost,
            'stock'            => [
                'quantity'          => $this->stock_quantity,
                'reserved'          => $this->reserved_quantity,
                'available'         => $this->stock_quantity - $this->reserved_quantity,
                'reorder_point'     => $this->reorder_point,
                'reorder_quantity'  => $this->reorder_quantity,
                'is_low'            => $this->stock_quantity <= $this->reorder_point,
            ],
            'unit'             => $this->unit,
            'weight'           => $this->weight ? (float) $this->weight : null,
            'dimensions'       => $this->dimensions,
            'attributes'       => $this->attributes,
            'is_active'        => (bool) $this->is_active,
            'is_trackable'     => (bool) $this->is_trackable,
            'tenant_id'        => $this->tenant_id,
            'created_at'       => $this->created_at?->toIso8601String(),
            'updated_at'       => $this->updated_at?->toIso8601String(),
        ];
    }
}
