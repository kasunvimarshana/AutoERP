<?php

declare(strict_types=1);

namespace Modules\Inventory\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Core\Helpers\MathHelper;
use Modules\Product\Http\Resources\ProductResource;

class StockItemResource extends JsonResource
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
            'product_id' => $this->product_id,
            'warehouse_id' => $this->warehouse_id,
            'location_id' => $this->location_id,
            'quantity' => $this->quantity,
            'reserved_quantity' => $this->reserved_quantity,
            'available_quantity' => $this->available_quantity,
            'reorder_point' => $this->reorder_point,
            'reorder_quantity' => $this->reorder_quantity,
            'minimum_quantity' => $this->minimum_quantity,
            'maximum_quantity' => $this->maximum_quantity,
            'average_cost' => $this->average_cost,
            'total_value' => MathHelper::multiply($this->quantity, $this->average_cost),
            'last_stock_count_date' => $this->last_stock_count_date?->toDateString(),
            'notes' => $this->notes,
            'product' => new ProductResource($this->whenLoaded('product')),
            'warehouse' => new WarehouseResource($this->whenLoaded('warehouse')),
            'location' => new StockLocationResource($this->whenLoaded('location')),
            'is_low_stock' => $this->when(
                ! is_null($this->reorder_point),
                fn () => MathHelper::lessThanOrEqual($this->quantity, $this->reorder_point ?? '0')
            ),
            'is_out_of_stock' => MathHelper::equals($this->quantity, '0'),
            'is_overstock' => $this->when(
                ! is_null($this->maximum_quantity),
                fn () => MathHelper::greaterThan($this->quantity, $this->maximum_quantity ?? '999999')
            ),
            'stock_health' => $this->calculateStockHealth(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'deleted_at' => $this->deleted_at?->toISOString(),
        ];
    }

    /**
     * Calculate stock health indicator.
     */
    private function calculateStockHealth(): string
    {
        if (MathHelper::equals($this->quantity, '0')) {
            return 'out_of_stock';
        }

        if (! is_null($this->reorder_point) && MathHelper::lessThanOrEqual($this->quantity, $this->reorder_point)) {
            return 'low';
        }

        if (! is_null($this->maximum_quantity) && MathHelper::greaterThan($this->quantity, $this->maximum_quantity)) {
            return 'overstock';
        }

        return 'healthy';
    }
}
