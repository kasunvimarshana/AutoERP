<?php

namespace App\Modules\Inventory\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * StockMovement Resource
 * 
 * Transforms stock movement model data for API responses
 * 
 * @package App\Modules\Inventory\Resources
 */
class StockMovementResource extends JsonResource
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
            'batch_id' => $this->batch_id,
            'type' => $this->type,
            'quantity' => $this->quantity,
            'balance_after' => $this->balance_after,
            'reference_type' => $this->reference_type,
            'reference_id' => $this->reference_id,
            'notes' => $this->notes,
            'created_by' => $this->created_by,
            'sku' => $this->whenLoaded('sku', function () {
                return [
                    'id' => $this->sku->id,
                    'sku_code' => $this->sku->sku_code,
                    'name' => $this->sku->name,
                    'barcode' => $this->sku->barcode,
                ];
            }),
            'batch' => $this->whenLoaded('batch', function () {
                return [
                    'id' => $this->batch->id,
                    'batch_number' => $this->batch->batch_number,
                    'expiry_date' => $this->batch->expiry_date->toDateString(),
                ];
            }),
            'creator' => $this->whenLoaded('creator', function () {
                return [
                    'id' => $this->creator->id,
                    'name' => $this->creator->name,
                    'email' => $this->creator->email,
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
        ];
    }
}
