<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Http\Resources;

use Modules\Core\Infrastructure\Http\Resources\BaseResource;

class InventoryMovementResource extends BaseResource
{
    public function toArray($request): array
    {
        return [
            'id'              => $this->id,
            'product_id'      => $this->product_id,
            'variant_id'      => $this->variant_id,
            'warehouse_id'    => $this->warehouse_id,
            'location_id'     => $this->location_id,
            'type'            => $this->type,
            'reference_type'  => $this->reference_type,
            'reference_id'    => $this->reference_id,
            'quantity'        => $this->quantity,
            'unit_cost'       => $this->unit_cost,
            'total_cost'      => $this->total_cost,
            'quantity_before' => $this->quantity_before,
            'quantity_after'  => $this->quantity_after,
            'notes'           => $this->notes,
            'created_at'      => $this->created_at?->toIso8601String(),
        ];
    }
}
