<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PriceListResource extends JsonResource
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
            'name' => $this->name,
            'code' => $this->code,
            'description' => $this->description,
            'type' => $this->type,
            'discount_type' => $this->discount_type,
            'discount_value' => $this->discount_value ? (float) $this->discount_value : null,
            'valid_from' => $this->valid_from?->toIso8601String(),
            'valid_to' => $this->valid_to?->toIso8601String(),
            'is_active' => $this->is_active,
            'priority' => $this->priority,
            'conditions' => $this->conditions,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'deleted_at' => $this->deleted_at?->toIso8601String(),
            
            // Computed properties
            'has_discount' => $this->discount_type !== 'none' && $this->discount_value > 0,
            'discount_formatted' => $this->formatDiscount(),
            'is_currently_valid' => $this->isCurrentlyValid(),
            'is_time_limited' => $this->valid_from !== null || $this->valid_to !== null,
            
            // Related data (when loaded)
            'tenant' => $this->whenLoaded('tenant', function () {
                return [
                    'id' => $this->tenant->id,
                    'uuid' => $this->tenant->uuid,
                    'name' => $this->tenant->name,
                ];
            }),
            'items' => $this->whenLoaded('items', function () {
                return PriceListItemResource::collection($this->items);
            }),
            'created_by_user' => $this->whenLoaded('createdBy', function () {
                return [
                    'id' => $this->createdBy->id,
                    'name' => $this->createdBy->name,
                    'email' => $this->createdBy->email,
                ];
            }),
            'updated_by_user' => $this->whenLoaded('updatedBy', function () {
                return [
                    'id' => $this->updatedBy->id,
                    'name' => $this->updatedBy->name,
                    'email' => $this->updatedBy->email,
                ];
            }),
            'items_count' => $this->when(
                isset($this->items_count),
                $this->items_count
            ),
        ];
    }
    
    /**
     * Format discount for display.
     */
    protected function formatDiscount(): ?string
    {
        if ($this->discount_type === 'none' || !$this->discount_value) {
            return null;
        }
        
        $value = (float) $this->discount_value;
        
        return match($this->discount_type) {
            'flat' => number_format($value, 2),
            'percentage' => number_format($value, 2) . '%',
            default => null,
        };
    }
    
    /**
     * Check if price list is currently valid.
     */
    protected function isCurrentlyValid(): bool
    {
        if (!$this->is_active) {
            return false;
        }
        
        $now = now();
        
        $afterStart = !$this->valid_from || $this->valid_from->lte($now);
        $beforeEnd = !$this->valid_to || $this->valid_to->gte($now);
        
        return $afterStart && $beforeEnd;
    }
}
