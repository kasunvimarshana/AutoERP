<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
            'price' => (float) $this->price,
            'min_quantity' => (float) $this->min_quantity,
            'max_quantity' => $this->max_quantity ? (float) $this->max_quantity : null,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            
            // Computed properties
            'price_formatted' => number_format((float) $this->price, 2),
            'is_tiered' => $this->min_quantity > 1 || $this->max_quantity !== null,
            'quantity_range' => $this->getQuantityRange(),
            
            // Related data (when loaded)
            'price_list' => $this->whenLoaded('priceList', function () {
                return [
                    'id' => $this->priceList->id,
                    'uuid' => $this->priceList->uuid,
                    'name' => $this->priceList->name,
                    'code' => $this->priceList->code,
                    'is_active' => $this->priceList->is_active,
                ];
            }),
            'product' => $this->whenLoaded('product', function () {
                return [
                    'id' => $this->product->id,
                    'uuid' => $this->product->uuid,
                    'name' => $this->product->name,
                    'sku' => $this->product->sku,
                    'selling_price' => $this->product->selling_price,
                ];
            }),
        ];
    }
    
    /**
     * Get quantity range description.
     */
    protected function getQuantityRange(): string
    {
        $min = (float) $this->min_quantity;
        $max = $this->max_quantity ? (float) $this->max_quantity : null;
        
        if ($max === null) {
            return $min . '+';
        }
        
        if ($min === $max) {
            return (string) $min;
        }
        
        return $min . ' - ' . $max;
    }
}
