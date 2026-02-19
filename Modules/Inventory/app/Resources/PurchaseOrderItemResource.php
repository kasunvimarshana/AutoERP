<?php

declare(strict_types=1);

namespace Modules\Inventory\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Purchase Order Item Resource
 *
 * Transforms PurchaseOrderItem model data for API responses
 */
class PurchaseOrderItemResource extends JsonResource
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
            'purchase_order_id' => $this->purchase_order_id,
            'item' => $this->whenLoaded('inventoryItem', function () {
                return [
                    'id' => $this->inventoryItem->id,
                    'item_code' => $this->inventoryItem->item_code,
                    'item_name' => $this->inventoryItem->item_name,
                ];
            }),
            'quantity' => $this->quantity,
            'unit_cost' => (float) $this->unit_cost,
            'total' => (float) $this->total,
            'received_quantity' => $this->received_quantity,
            'remaining_quantity' => $this->getRemainingQuantity(),
            'is_fully_received' => $this->isFullyReceived(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
