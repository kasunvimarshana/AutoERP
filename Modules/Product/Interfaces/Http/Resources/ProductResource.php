<?php

declare(strict_types=1);

namespace Modules\Product\Interfaces\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'name'          => $this->name,
            'sku'           => $this->sku,
            'barcode'       => $this->barcode,
            'type'          => $this->type,
            'cost_price'    => $this->cost_price,
            'selling_price' => $this->selling_price,
            'reorder_point' => $this->reorder_point,
            'is_active'     => $this->is_active,
            'description'   => $this->description,
            'category'      => $this->whenLoaded('category', fn () => [
                'id'   => $this->category->id,
                'name' => $this->category->name,
            ]),
            'brand'         => $this->whenLoaded('brand', fn () => [
                'id'   => $this->brand->id,
                'name' => $this->brand->name,
            ]),
            'unit'          => $this->whenLoaded('unit', fn () => [
                'id'         => $this->unit->id,
                'name'       => $this->unit->name,
                'short_name' => $this->unit->short_name,
            ]),
            'variants'      => $this->whenLoaded('variants'),
            'tenant_id'     => $this->tenant_id,
            'created_at'    => $this->created_at?->toIso8601String(),
            'updated_at'    => $this->updated_at?->toIso8601String(),
        ];
    }
}
