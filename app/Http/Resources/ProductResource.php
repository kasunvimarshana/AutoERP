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
            'type' => $this->type?->value,
            'sku' => $this->sku,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'is_purchasable' => $this->is_purchasable,
            'is_saleable' => $this->is_saleable,
            'is_trackable' => $this->is_trackable,
            'base_price' => $this->base_price,
            'buy_unit_cost' => $this->buy_unit_cost,
            'currency' => $this->currency,
            'tax_rate' => $this->tax_rate,
            'lock_version' => $this->lock_version,
            'attributes' => $this->attributes,
            'metadata' => $this->metadata,
            'category' => $this->whenLoaded('category', fn () => [
                'id' => $this->category?->id,
                'name' => $this->category?->name,
            ]),
            'brand' => $this->whenLoaded('brand', fn () => [
                'id' => $this->brand?->id,
                'name' => $this->brand?->name,
            ]),
            'buy_unit' => $this->whenLoaded('buyUnit', fn () => [
                'id' => $this->buyUnit?->id,
                'name' => $this->buyUnit?->name,
                'symbol' => $this->buyUnit?->symbol,
            ]),
            'sell_unit' => $this->whenLoaded('sellUnit', fn () => [
                'id' => $this->sellUnit?->id,
                'name' => $this->sellUnit?->name,
                'symbol' => $this->sellUnit?->symbol,
            ]),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
