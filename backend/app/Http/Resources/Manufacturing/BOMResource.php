<?php

declare(strict_types=1);

namespace App\Http\Resources\Manufacturing;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BOMResource extends JsonResource
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
            'organization_id' => $this->organization_id,
            'product_id' => $this->product_id,
            'bom_number' => $this->bom_number,
            'name' => $this->name,
            'version' => $this->version,
            'status' => $this->status,
            'quantity' => $this->quantity,
            'unit_id' => $this->unit_id,
            'production_time_minutes' => $this->production_time_minutes,
            'estimated_cost' => $this->estimated_cost,
            'actual_cost' => $this->actual_cost,
            'notes' => $this->notes,
            'instructions' => $this->instructions,
            'is_default' => $this->is_default,
            'valid_from' => $this->valid_from?->toDateString(),
            'valid_until' => $this->valid_until?->toDateString(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            
            // Computed properties
            'estimated_cost_formatted' => $this->estimated_cost 
                ? number_format((float) $this->estimated_cost, 2) 
                : null,
            'actual_cost_formatted' => $this->actual_cost 
                ? number_format((float) $this->actual_cost, 2) 
                : null,
            'production_time_formatted' => $this->production_time_minutes 
                ? $this->formatProductionTime() 
                : null,
            'is_valid' => $this->isValid(),
            
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
            'items' => $this->whenLoaded('items', function () {
                return BOMItemResource::collection($this->items);
            }),
            'created_by_user' => $this->whenLoaded('createdBy', function () {
                return [
                    'id' => $this->createdBy->id,
                    'name' => $this->createdBy->name,
                ];
            }),
        ];
    }

    /**
     * Format production time into human-readable format
     */
    protected function formatProductionTime(): string
    {
        $minutes = $this->production_time_minutes;
        
        if ($minutes < 60) {
            return "{$minutes} minute" . ($minutes != 1 ? 's' : '');
        }
        
        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;
        
        $result = "{$hours} hour" . ($hours != 1 ? 's' : '');
        
        if ($remainingMinutes > 0) {
            $result .= " {$remainingMinutes} minute" . ($remainingMinutes != 1 ? 's' : '');
        }
        
        return $result;
    }
}
