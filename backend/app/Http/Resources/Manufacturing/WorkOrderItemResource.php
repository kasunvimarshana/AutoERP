<?php

declare(strict_types=1);

namespace App\Http\Resources\Manufacturing;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkOrderItemResource extends JsonResource
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
            'work_order_id' => $this->work_order_id,
            'product_id' => $this->product_id,
            'bom_item_id' => $this->bom_item_id,
            'planned_quantity' => $this->planned_quantity,
            'allocated_quantity' => $this->allocated_quantity,
            'consumed_quantity' => $this->consumed_quantity,
            'returned_quantity' => $this->returned_quantity,
            'unit_id' => $this->unit_id,
            'unit_cost' => $this->unit_cost,
            'total_cost' => $this->total_cost,
            'scrap_percentage' => $this->scrap_percentage,
            'status' => $this->status,
            'sequence' => $this->sequence,
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            
            // Computed properties
            'remaining_quantity' => $this->remaining_quantity,
            'shortfall_quantity' => $this->shortfall_quantity,
            'consumption_percentage' => $this->consumption_percentage,
            'is_fully_consumed' => $this->isFullyConsumed(),
            'is_allocated' => $this->isAllocated(),
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
