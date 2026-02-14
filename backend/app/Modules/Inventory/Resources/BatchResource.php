<?php

namespace App\Modules\Inventory\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Batch Resource
 * 
 * Transforms batch model data for API responses
 * 
 * @package App\Modules\Inventory\Resources
 */
class BatchResource extends JsonResource
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
            'sku_id' => $this->sku_id,
            'batch_number' => $this->batch_number,
            'manufacturing_date' => $this->manufacturing_date->toDateString(),
            'expiry_date' => $this->expiry_date->toDateString(),
            'quantity' => $this->quantity,
            'status' => $this->status,
            'sku' => $this->whenLoaded('sku', function () {
                return [
                    'id' => $this->sku->id,
                    'sku_code' => $this->sku->sku_code,
                    'name' => $this->sku->name,
                    'barcode' => $this->sku->barcode,
                ];
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
