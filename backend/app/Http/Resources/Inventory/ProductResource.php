<?php

namespace App\Http\Resources\Inventory;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * ProductResource
 * 
 * API Resource transformer for Product model
 * Provides consistent JSON structure for API responses
 */
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
            'tenant_id' => $this->tenant_id,
            'branch_id' => $this->branch_id,
            'name' => $this->name,
            'slug' => $this->slug,
            'sku' => $this->sku,
            'barcode' => $this->barcode,
            'description' => $this->description,
            'type' => $this->type,
            'category' => $this->category,
            'brand' => $this->brand,
            'unit' => $this->unit,
            
            'flags' => [
                'is_variant_product' => $this->is_variant_product,
                'track_stock' => $this->track_stock,
                'track_batch' => $this->track_batch,
                'track_serial' => $this->track_serial,
                'is_active' => $this->is_active,
                'is_purchasable' => $this->is_purchasable,
                'is_sellable' => $this->is_sellable,
            ],
            
            'stock_management' => [
                'min_stock_level' => $this->min_stock_level,
                'max_stock_level' => $this->max_stock_level,
                'reorder_level' => $this->reorder_level,
                'reorder_quantity' => $this->reorder_quantity,
            ],
            
            'pricing' => [
                'cost_price' => $this->cost_price,
                'selling_price' => $this->selling_price,
                'tax_rate' => $this->tax_rate,
                'tax_inclusive' => $this->tax_inclusive,
            ],
            
            'physical' => [
                'weight' => $this->weight,
                'dimensions' => $this->dimensions,
            ],
            
            'media' => [
                'images' => $this->images,
            ],
            
            'attributes' => $this->attributes,
            'metadata' => $this->metadata,
            
            'timestamps' => [
                'created_at' => $this->created_at?->toISOString(),
                'updated_at' => $this->updated_at?->toISOString(),
                'deleted_at' => $this->deleted_at?->toISOString(),
            ],
            
            // Conditionally load relationships
            'tenant' => $this->whenLoaded('tenant'),
            'branch' => $this->whenLoaded('branch'),
            'variants' => $this->whenLoaded('variants'),
            'batches' => $this->whenLoaded('batches'),
        ];
    }
}
