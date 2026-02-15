<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockLedgerResource extends JsonResource
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
            'product_id' => $this->product_id,
            'product_variant_id' => $this->product_variant_id,
            'branch_id' => $this->branch_id,
            'location_id' => $this->location_id,
            'transaction_type' => $this->transaction_type,
            'transaction_date' => $this->transaction_date?->toIso8601String(),
            'quantity' => $this->quantity,
            'running_balance' => $this->running_balance,
            'unit_cost' => $this->unit_cost,
            'total_cost' => $this->total_cost,
            'batch_number' => $this->batch_number,
            'serial_number' => $this->serial_number,
            'lot_number' => $this->lot_number,
            'expiry_date' => $this->expiry_date?->toIso8601String(),
            'reference_type' => $this->reference_type,
            'reference_id' => $this->reference_id,
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toIso8601String(),
            
            // Related data (when loaded)
            'product' => $this->whenLoaded('product', function () {
                return [
                    'id' => $this->product->id,
                    'name' => $this->product->name,
                    'sku' => $this->product->sku,
                ];
            }),
            'variant' => $this->whenLoaded('variant', function () {
                return [
                    'id' => $this->variant->id,
                    'name' => $this->variant->name,
                    'sku' => $this->variant->sku,
                ];
            }),
            'branch' => $this->whenLoaded('branch', function () {
                return [
                    'id' => $this->branch->id,
                    'name' => $this->branch->name,
                ];
            }),
            'location' => $this->whenLoaded('location', function () {
                return [
                    'id' => $this->location->id,
                    'name' => $this->location->name,
                ];
            }),
        ];
    }
}
