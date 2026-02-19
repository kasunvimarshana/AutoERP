<?php

declare(strict_types=1);

namespace Modules\Product\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Product Variant Resource
 *
 * Transforms ProductVariant model data for API responses
 */
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
            'product_id' => $this->product_id,
            'branch_id' => $this->branch_id,
            'sku' => $this->sku,
            'name' => $this->name,
            'barcode' => $this->barcode,
            'variant_attributes' => $this->variant_attributes,

            // Pricing
            'pricing' => [
                'cost_price' => $this->cost_price ? (float) $this->cost_price : null,
                'selling_price' => $this->selling_price ? (float) $this->selling_price : null,
                'effective_cost_price' => (float) $this->effective_cost_price,
                'effective_selling_price' => (float) $this->effective_selling_price,
            ],

            // Inventory
            'inventory' => [
                'current_stock' => $this->current_stock,
                'reorder_level' => $this->reorder_level,
                'reorder_quantity' => $this->reorder_quantity,
                'needs_reorder' => $this->needsReorder(),
                'is_in_stock' => $this->isInStock(),
            ],

            // Additional
            'images' => $this->images,
            'weight' => $this->weight ? (float) $this->weight : null,
            'is_default' => $this->is_default,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,

            // Relationships
            'product' => $this->whenLoaded('product', fn () => [
                'id' => $this->product->id,
                'name' => $this->product->name,
                'sku' => $this->product->sku,
            ]),

            // Timestamps
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
