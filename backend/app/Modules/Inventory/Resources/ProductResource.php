<?php

namespace App\Modules\Inventory\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Product Resource
 * 
 * Transforms product model data for API responses
 * 
 * @package App\Modules\Inventory\Resources
 */
class ProductResource extends JsonResource
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
            'name' => $this->name,
            'description' => $this->description,
            'category' => $this->category,
            'brand' => $this->brand,
            'unit_of_measure' => $this->unit_of_measure,
            'status' => $this->status,
            'skus' => $this->whenLoaded('skus', function () {
                return SKUResource::collection($this->skus);
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
