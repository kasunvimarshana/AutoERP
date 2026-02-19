<?php

declare(strict_types=1);

namespace Modules\Pricing\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * PriceListItem Resource
 */
class PriceListItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'price_list_id' => $this->price_list_id,
            'product_id' => $this->product_id,
            'price' => (string) $this->price,
            'min_quantity' => $this->min_quantity ? (string) $this->min_quantity : null,
            'max_quantity' => $this->max_quantity ? (string) $this->max_quantity : null,
            'discount_percentage' => $this->discount_percentage ? (string) $this->discount_percentage : null,
            'final_price' => $this->getFinalPrice(),

            // Relationships
            'product' => $this->whenLoaded('product', fn () => [
                'id' => $this->product->id,
                'name' => $this->product->name,
                'sku' => $this->product->sku,
            ]),

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
