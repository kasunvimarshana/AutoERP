<?php

namespace App\Modules\Inventory\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * SKU Resource
 * 
 * Transforms SKU model data for API responses
 * 
 * @package App\Modules\Inventory\Resources
 */
class SKUResource extends JsonResource
{
    /**
     * Transform the resource into an array
     *
     * @param \Illuminate\Http\Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'product_id' => $this->product_id,
            'sku_code' => $this->sku_code,
            'name' => $this->name,
            'barcode' => $this->barcode,
            'cost_price' => $this->cost_price,
            'selling_price' => $this->selling_price,
            'reorder_level' => $this->reorder_level,
            'status' => $this->status,
            'product' => $this->whenLoaded('product', function () {
                return [
                    'id' => $this->product->id,
                    'name' => $this->product->name,
                    'category' => $this->product->category,
                    'brand' => $this->product->brand,
                ];
            }),
            'batches' => $this->whenLoaded('batches', function () {
                return BatchResource::collection($this->batches);
            }),
            'tenant' => $this->whenLoaded('tenant', function () {
                return [
                    'id' => $this->tenant->id,
                    'name' => $this->tenant->name,
                    'domain' => $this->tenant->domain,
                ];
            }),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
