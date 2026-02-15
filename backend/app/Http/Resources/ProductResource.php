<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
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
            'uuid' => $this->uuid,
            'tenant_id' => $this->tenant_id,
            'name' => $this->name,
            'slug' => $this->slug,
            'sku' => $this->sku,
            'type' => $this->type,
            'description' => $this->description,
            'category_id' => $this->category_id,
            'unit_of_measure_id' => $this->unit_of_measure_id,
            'buying_price' => $this->buying_price,
            'selling_price' => $this->selling_price,
            'discount_type' => $this->discount_type,
            'discount_value' => $this->discount_value,
            'tax_rate_id' => $this->tax_rate_id,
            'profit_margin' => $this->profit_margin,
            'profit_margin_type' => $this->profit_margin_type,
            'min_stock_level' => $this->min_stock_level,
            'max_stock_level' => $this->max_stock_level,
            'reorder_level' => $this->reorder_level,
            'is_active' => $this->is_active,
            'has_variants' => $this->has_variants,
            'is_serialized' => $this->is_serialized,
            'is_batch_tracked' => $this->is_batch_tracked,
            'has_expiry' => $this->has_expiry,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            
            // Computed properties
            'buying_price_formatted' => $this->buying_price 
                ? number_format((float) $this->buying_price, 2) 
                : null,
            'selling_price_formatted' => $this->selling_price 
                ? number_format((float) $this->selling_price, 2) 
                : null,
            'discount_formatted' => $this->formatDiscount(),
            'profit_margin_formatted' => $this->profit_margin 
                ? number_format((float) $this->profit_margin, 2) . ($this->profit_margin_type === 'percentage' ? '%' : '') 
                : null,
            
            // Related data (when loaded)
            'category' => $this->whenLoaded('category', function () {
                return new ProductCategoryResource($this->category);
            }),
            'unit_of_measure' => $this->whenLoaded('unitOfMeasure', function () {
                return new UnitOfMeasureResource($this->unitOfMeasure);
            }),
            'tax_rate' => $this->whenLoaded('taxRate', function () {
                return new TaxRateResource($this->taxRate);
            }),
            'variants' => $this->whenLoaded('variants', function () {
                return ProductVariantResource::collection($this->variants);
            }),
            'tenant' => $this->whenLoaded('tenant', function () {
                return [
                    'id' => $this->tenant->id,
                    'uuid' => $this->tenant->uuid,
                    'name' => $this->tenant->name,
                ];
            }),
        ];
    }
    
    /**
     * Format discount for display.
     */
    protected function formatDiscount(): ?string
    {
        if (!$this->discount_value) {
            return null;
        }
        
        $value = (float) $this->discount_value;
        
        return match($this->discount_type) {
            'flat' => number_format($value, 2),
            'percentage' => number_format($value, 2) . '%',
            default => null,
        };
    }
}
