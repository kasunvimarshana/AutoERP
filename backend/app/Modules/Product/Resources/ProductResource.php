<?php

namespace App\Modules\Product\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $price     = (float) $this->price;
        $costPrice = (float) $this->cost_price;
        $margin    = $price > 0 ? round((($price - $costPrice) / $price) * 100, 2) : 0;

        return [
            'id'              => $this->id,
            'tenant_id'       => $this->tenant_id,
            'name'            => $this->name,
            'sku'             => $this->sku,
            'description'     => $this->description,
            'price'           => $price,
            'cost_price'      => $costPrice,
            'margin_percent'  => $margin,
            'category'        => $this->category,
            'unit'            => $this->unit,
            'is_active'       => $this->is_active,
            'metadata'        => $this->metadata ?? [],
            'created_at'      => $this->created_at?->toIso8601String(),
            'updated_at'      => $this->updated_at?->toIso8601String(),
        ];
    }
}
