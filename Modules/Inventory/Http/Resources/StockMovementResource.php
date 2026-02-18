<?php

declare(strict_types=1);

namespace Modules\Inventory\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockMovementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'product_id' => $this->product_id,
            'warehouse_id' => $this->warehouse_id,
            'stock_location_id' => $this->stock_location_id,
            'movement_type' => $this->movement_type,
            'movement_number' => $this->movement_number,
            'quantity' => $this->quantity,
            'signed_quantity' => $this->getSignedQuantity(),
            'unit_cost' => $this->unit_cost,
            'currency' => $this->currency,
            'movement_value' => $this->getMovementValue(),
            'movement_date' => $this->movement_date?->toIso8601String(),
            'reference_type' => $this->reference_type,
            'reference_id' => $this->reference_id,
            'reference_number' => $this->reference_number,
            'reason' => $this->reason,
            'notes' => $this->notes,
            'from_warehouse_id' => $this->from_warehouse_id,
            'from_location_id' => $this->from_location_id,
            'to_warehouse_id' => $this->to_warehouse_id,
            'to_location_id' => $this->to_location_id,
            'product' => new ProductResource($this->whenLoaded('product')),
            'warehouse' => new WarehouseResource($this->whenLoaded('warehouse')),
            'location' => new StockLocationResource($this->whenLoaded('stockLocation')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
