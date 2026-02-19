<?php

declare(strict_types=1);

namespace Modules\Pricing\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * ProductPriceResource
 *
 * API resource for product price transformation
 *
 * @mixin \Modules\Pricing\Models\ProductPrice
 */
class ProductPriceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'product_id' => $this->product_id,
            'product' => [
                'id' => $this->product->id,
                'name' => $this->product->name,
                'sku' => $this->product->sku,
                'type' => $this->product->type,
            ],
            'location_id' => $this->location_id,
            'location' => $this->when($this->location, [
                'id' => $this->location?->id,
                'name' => $this->location?->name,
            ]),
            'strategy' => [
                'value' => $this->strategy->value,
                'label' => $this->strategy->label(),
            ],
            'price' => $this->price,
            'config' => $this->config,
            'valid_from' => $this->valid_from?->toISOString(),
            'valid_until' => $this->valid_until?->toISOString(),
            'is_active' => $this->is_active,
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
