<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductVariantResource extends JsonResource
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
            'uuid' => $this->uuid ?? null,
            'product_id' => $this->product_id ?? null,
            'name' => $this->name ?? null,
            'sku' => $this->sku ?? null,
            'barcode' => $this->barcode ?? null,
            'buying_price' => $this->buying_price ? (float) $this->buying_price : null,
            'selling_price' => $this->selling_price ? (float) $this->selling_price : null,
            'cost_price' => $this->cost_price ? (float) $this->cost_price : null,
            'attributes' => $this->attributes ?? null,
            'is_active' => $this->is_active ?? true,
            'stock_quantity' => $this->stock_quantity ?? null,
            'reserved_quantity' => $this->reserved_quantity ?? null,
            'available_quantity' => $this->available_quantity ?? null,
            'metadata' => $this->metadata ?? null,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'deleted_at' => $this->deleted_at?->toIso8601String(),
            
            // Computed properties
            'buying_price_formatted' => $this->buying_price 
                ? number_format((float) $this->buying_price, 2) 
                : null,
            'selling_price_formatted' => $this->selling_price 
                ? number_format((float) $this->selling_price, 2) 
                : null,
            'profit_margin' => $this->calculateProfitMargin(),
            'display_name' => $this->getDisplayName(),
            'in_stock' => ($this->available_quantity ?? 0) > 0,
            
            // Related data (when loaded)
            'product' => $this->whenLoaded('product', function () {
                return [
                    'id' => $this->product->id,
                    'uuid' => $this->product->uuid,
                    'name' => $this->product->name,
                    'sku' => $this->product->sku,
                ];
            }),
        ];
    }
    
    /**
     * Calculate profit margin.
     */
    protected function calculateProfitMargin(): ?float
    {
        if (!$this->buying_price || !$this->selling_price) {
            return null;
        }
        
        $buying = (float) $this->buying_price;
        $selling = (float) $this->selling_price;
        
        if ($buying <= 0) {
            return null;
        }
        
        return round((($selling - $buying) / $buying) * 100, 2);
    }
    
    /**
     * Get display name.
     */
    protected function getDisplayName(): string
    {
        if ($this->name) {
            return $this->name;
        }
        
        if ($this->attributes && is_array($this->attributes)) {
            return implode(' - ', array_values($this->attributes));
        }
        
        return $this->sku ?? 'Variant #' . $this->id;
    }
}
