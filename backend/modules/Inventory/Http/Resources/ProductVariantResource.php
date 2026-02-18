<?php

namespace Modules\Inventory\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductVariantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'variant_sku' => $this->sku,
            'variant_name' => $this->name,
            'parent_product_id' => $this->product_id,
            'variant_attributes' => $this->attributes ?? [],
            'variant_pricing' => $this->price,
            'variant_cost' => $this->cost,
        ];
    }
}
