<?php

declare(strict_types=1);

namespace Modules\Inventory\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Stock Movement Resource
 *
 * Transforms StockMovement model data for API responses
 */
class StockMovementResource extends JsonResource
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
            'item' => $this->whenLoaded('item', function () {
                return [
                    'id' => $this->item->id,
                    'item_code' => $this->item->item_code,
                    'item_name' => $this->item->item_name,
                ];
            }),
            'branch_id' => $this->branch_id,
            'movement_type' => $this->movement_type,
            'quantity' => $this->quantity,
            'unit_cost' => $this->unit_cost ? (float) $this->unit_cost : null,
            'reference_type' => $this->reference_type,
            'reference_id' => $this->reference_id,
            'from_branch' => $this->whenLoaded('fromBranch', function () {
                return [
                    'id' => $this->fromBranch->id,
                    'name' => $this->fromBranch->name,
                ];
            }),
            'to_branch' => $this->whenLoaded('toBranch', function () {
                return [
                    'id' => $this->toBranch->id,
                    'name' => $this->toBranch->name,
                ];
            }),
            'notes' => $this->notes,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
