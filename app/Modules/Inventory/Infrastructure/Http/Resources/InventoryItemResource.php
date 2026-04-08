<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Http\Resources;

use Modules\Core\Infrastructure\Http\Resources\BaseResource;

class InventoryItemResource extends BaseResource
{
    public function toArray($request): array
    {
        return [
            'id'                   => $this->id,
            'product_id'           => $this->product_id,
            'variant_id'           => $this->variant_id,
            'warehouse_id'         => $this->warehouse_id,
            'location_id'          => $this->location_id,
            'quantity_on_hand'     => $this->quantity_on_hand,
            'quantity_reserved'    => $this->quantity_reserved,
            'quantity_in_transit'  => $this->quantity_in_transit,
            'quantity_available'   => $this->quantity_available,
            'average_cost'         => $this->average_cost,
            'unit_of_measure'      => $this->unit_of_measure,
            'updated_at'           => $this->updated_at?->toIso8601String(),
        ];
    }
}
