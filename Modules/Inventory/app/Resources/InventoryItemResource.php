<?php

declare(strict_types=1);

namespace Modules\Inventory\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Inventory Item Resource
 *
 * Transforms InventoryItem model data for API responses
 */
class InventoryItemResource extends JsonResource
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
            'branch_id' => $this->branch_id,
            'branch' => $this->whenLoaded('branch', function () {
                return [
                    'id' => $this->branch->id,
                    'name' => $this->branch->name,
                ];
            }),
            'item_code' => $this->item_code,
            'item_name' => $this->item_name,
            'category' => $this->category,
            'description' => $this->description,
            'unit_of_measure' => $this->unit_of_measure,
            'reorder_level' => $this->reorder_level,
            'reorder_quantity' => $this->reorder_quantity,
            'unit_cost' => (float) $this->unit_cost,
            'selling_price' => (float) $this->selling_price,
            'stock_on_hand' => $this->stock_on_hand,
            'is_dummy_item' => $this->is_dummy_item,
            'needs_reorder' => $this->needsReorder(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
