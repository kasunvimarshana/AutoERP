<?php

declare(strict_types=1);

namespace Modules\Inventory\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Core\Helpers\MathHelper;
use Modules\Product\Http\Resources\ProductResource;

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
            'tenant_id' => $this->tenant_id,
            'type' => $this->type->value,
            'type_label' => $this->type->label(),
            'product_id' => $this->product_id,
            'from_warehouse_id' => $this->from_warehouse_id,
            'to_warehouse_id' => $this->to_warehouse_id,
            'from_location_id' => $this->from_location_id,
            'to_location_id' => $this->to_location_id,
            'quantity' => $this->quantity,
            'cost' => $this->cost,
            'total_cost' => $this->when(
                ! is_null($this->cost),
                fn () => MathHelper::multiply($this->quantity, $this->cost ?? '0')
            ),
            'reference_type' => $this->reference_type,
            'reference_id' => $this->reference_id,
            'batch_lot_id' => $this->batch_lot_id,
            'serial_number_id' => $this->serial_number_id,
            'movement_date' => $this->movement_date?->toDateString(),
            'document_number' => $this->document_number,
            'notes' => $this->notes,
            'created_by' => $this->created_by,
            'product' => new ProductResource($this->whenLoaded('product')),
            'from_warehouse' => new WarehouseResource($this->whenLoaded('fromWarehouse')),
            'to_warehouse' => new WarehouseResource($this->whenLoaded('toWarehouse')),
            'from_location' => new StockLocationResource($this->whenLoaded('fromLocation')),
            'to_location' => new StockLocationResource($this->whenLoaded('toLocation')),
            'is_inbound' => $this->type->isInbound(),
            'is_outbound' => $this->type->isOutbound(),
            'is_internal' => $this->type->isInternal(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'deleted_at' => $this->deleted_at?->toISOString(),
        ];
    }
}
