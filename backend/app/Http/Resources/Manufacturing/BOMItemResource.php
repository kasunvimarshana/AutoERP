<?php

declare(strict_types=1);

namespace App\Http\Resources\Manufacturing;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BOMItemResource extends JsonResource
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
            'bom_id' => $this->bom_id,
            'product_id' => $this->product_id,
            'quantity' => $this->quantity,
            'unit_id' => $this->unit_id,
            'unit_cost' => $this->unit_cost,
            'total_cost' => $this->total_cost,
            'scrap_percentage' => $this->scrap_percentage,
            'sequence' => $this->sequence,
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            
            // Computed properties
            'required_quantity' => $this->required_quantity,
            'unit_cost_formatted' => $this->unit_cost 
                ? number_format((float) $this->unit_cost, 2) 
                : null,
            'total_cost_formatted' => $this->total_cost 
                ? number_format((float) $this->total_cost, 2) 
                : null,
            
            // Related data (when loaded)
            'product' => $this->whenLoaded('product', function () {
                return [
                    'id' => $this->product->id,
                    'name' => $this->product->name,
                    'sku' => $this->product->sku,
                ];
            }),
            'unit' => $this->whenLoaded('unit', function () {
                return [
                    'id' => $this->unit->id,
                    'name' => $this->unit->name,
                    'symbol' => $this->unit->symbol,
                ];
            }),
        ];
    }
}
