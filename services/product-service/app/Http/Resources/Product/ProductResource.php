<?php

declare(strict_types=1);

namespace App\Http\Resources\Product;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * ProductResource — wraps a single product for API responses.
 *
 * @mixin \App\Infrastructure\Persistence\Models\Product
 */
class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'tenant_id'   => $this->tenant_id,
            'category'    => $this->whenLoaded('category', fn () => [
                'id'   => $this->category->id,
                'name' => $this->category->name,
                'slug' => $this->category->slug,
            ]),
            'name'        => $this->name,
            'code'        => $this->code,
            'sku'         => $this->sku,
            'barcode'     => $this->barcode,
            'description' => $this->description,
            'price'       => $this->price,
            'cost_price'  => $this->cost_price,
            'currency'    => $this->currency,
            'unit'        => $this->unit,
            'weight'      => $this->weight,
            'dimensions'  => $this->dimensions,
            'status'      => $this->status,
            'attributes'  => $this->attributes,
            'image_url'   => $this->image_url,
            'is_trackable'=> $this->is_trackable,
            'created_at'  => $this->created_at?->toIso8601String(),
            'updated_at'  => $this->updated_at?->toIso8601String(),
        ];
    }
}
