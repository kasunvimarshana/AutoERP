<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class StockMovementResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'               => $this->id,
            'uuid'             => $this->uuid,
            'tenant_id'        => $this->tenant_id,
            'product_id'       => $this->product_id,
            'variant_id'       => $this->variant_id,
            'location_id'      => $this->location_id,
            'batch_lot_id'     => $this->batch_lot_id,
            'serial_number_id' => $this->serial_number_id,
            'movement_type'    => $this->movement_type,
            'quantity'         => $this->quantity,
            'unit_cost'        => $this->unit_cost,
            'reference'        => $this->reference,
            'notes'            => $this->notes,
            'moved_by'         => $this->moved_by,
            'moved_at'         => $this->moved_at,
            'created_at'       => $this->created_at,
        ];
    }
}
