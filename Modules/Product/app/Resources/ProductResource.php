<?php

declare(strict_types=1);

namespace Modules\Product\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Product Resource
 *
 * Transforms Product model data for API responses
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
            'branch_id' => $this->branch_id,
            'category_id' => $this->category_id,
            'sku' => $this->sku,
            'name' => $this->name,
            'description' => $this->description,
            'barcode' => $this->barcode,
            'type' => $this->type,
            'status' => $this->status,

            // Units
            'buy_unit_id' => $this->buy_unit_id,
            'sell_unit_id' => $this->sell_unit_id,
            'buy_unit' => $this->whenLoaded('buyUnit', fn () => [
                'id' => $this->buyUnit->id,
                'name' => $this->buyUnit->name,
                'code' => $this->buyUnit->code,
            ]),
            'sell_unit' => $this->whenLoaded('sellUnit', fn () => [
                'id' => $this->sellUnit->id,
                'name' => $this->sellUnit->name,
                'code' => $this->sellUnit->code,
            ]),

            // Pricing
            'pricing' => [
                'cost_price' => (float) $this->cost_price,
                'selling_price' => (float) $this->selling_price,
                'min_price' => $this->min_price ? (float) $this->min_price : null,
                'max_price' => $this->max_price ? (float) $this->max_price : null,
                'profit' => (float) $this->profit,
                'profit_margin' => (float) $this->profit_margin,
            ],

            // Inventory
            'inventory' => [
                'track_inventory' => $this->track_inventory,
                'current_stock' => $this->current_stock,
                'reorder_level' => $this->reorder_level,
                'reorder_quantity' => $this->reorder_quantity,
                'min_stock_level' => $this->min_stock_level,
                'max_stock_level' => $this->max_stock_level,
                'stock_status' => $this->stock_status,
                'needs_reorder' => $this->needsReorder(),
                'is_in_stock' => $this->isInStock(),
            ],

            // Product Details
            'details' => [
                'manufacturer' => $this->manufacturer,
                'brand' => $this->brand,
                'model' => $this->model,
                'attributes' => $this->attributes,
            ],

            // Dimensions & Weight
            'physical' => [
                'weight' => $this->weight ? (float) $this->weight : null,
                'weight_unit' => $this->weight_unit,
                'length' => $this->length ? (float) $this->length : null,
                'width' => $this->width ? (float) $this->width : null,
                'height' => $this->height ? (float) $this->height : null,
                'dimension_unit' => $this->dimension_unit,
            ],

            // Tax & Discount
            'tax_discount' => [
                'is_taxable' => $this->is_taxable,
                'tax_rate' => $this->tax_rate ? (float) $this->tax_rate : null,
                'allow_discount' => $this->allow_discount,
                'max_discount_percentage' => $this->max_discount_percentage ? (float) $this->max_discount_percentage : null,
            ],

            // Additional
            'images' => $this->images,
            'notes' => $this->notes,
            'is_featured' => $this->is_featured,
            'sort_order' => $this->sort_order,

            // Relationships
            'category' => $this->whenLoaded('category', fn () => [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'code' => $this->category->code,
            ]),
            'variants_count' => $this->whenCounted('variants'),
            'variants' => ProductVariantResource::collection($this->whenLoaded('variants')),

            // Timestamps
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
