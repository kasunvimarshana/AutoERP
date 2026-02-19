<?php

declare(strict_types=1);

namespace Modules\Inventory\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockLocationResource extends JsonResource
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
            'tenant_id' => $this->tenant_id,
            'warehouse_id' => $this->warehouse_id,
            'code' => $this->code,
            'name' => $this->name,
            'aisle' => $this->aisle,
            'bay' => $this->bay,
            'shelf' => $this->shelf,
            'bin' => $this->bin,
            'is_active' => $this->is_active,
            'notes' => $this->notes,
            'warehouse' => new WarehouseResource($this->whenLoaded('warehouse')),
            'stock_items_count' => $this->when(
                $this->relationLoaded('stockItems'),
                fn () => $this->stockItems->count()
            ),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'deleted_at' => $this->deleted_at?->toISOString(),
        ];
    }
}
