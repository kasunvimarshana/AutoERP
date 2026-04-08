<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Http\Resources;

use Modules\Core\Infrastructure\Http\Resources\BaseResource;

class ProductVariantResource extends BaseResource
{
    public function toArray($request): array
    {
        return [
            'id'            => $this->id,
            'product_id'    => $this->product_id,
            'sku'           => $this->sku,
            'barcode'       => $this->barcode,
            'name'          => $this->name,
            'attributes'    => $this->attributes,
            'cost_price'    => $this->cost_price,
            'selling_price' => $this->selling_price,
            'weight'        => $this->weight,
            'image_path'    => $this->image_path,
            'is_active'     => $this->is_active,
            'created_at'    => $this->created_at?->toIso8601String(),
            'updated_at'    => $this->updated_at?->toIso8601String(),
        ];
    }
}
