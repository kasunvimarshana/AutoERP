<?php

declare(strict_types=1);

namespace Modules\Inventory\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockLevelResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'product_id' => $this->product_id,
            'warehouse_id' => $this->warehouse_id,
            'stock_location_id' => $this->stock_location_id,
            'quantity_on_hand' => $this->quantity_on_hand,
            'quantity_reserved' => $this->quantity_reserved,
            'quantity_available' => $this->quantity_available,
            'unit_cost' => $this->unit_cost,
            'currency' => $this->currency,
            'valuation_method' => $this->valuation_method,
            'inventory_value' => $this->getInventoryValue(),
            'last_recount_date' => $this->last_recount_date?->toIso8601String(),
            'last_movement_date' => $this->last_movement_date?->toIso8601String(),
            'product' => new ProductResource($this->whenLoaded('product')),
            'warehouse' => new WarehouseResource($this->whenLoaded('warehouse')),
            'location' => new StockLocationResource($this->whenLoaded('stockLocation')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
