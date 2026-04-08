<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class StockItemResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                 => $this->id,
            'uuid'               => $this->uuid,
            'tenant_id'          => $this->tenant_id,
            'product_id'         => $this->product_id,
            'variant_id'         => $this->variant_id,
            'location_id'        => $this->location_id,
            'batch_lot_id'       => $this->batch_lot_id,
            'quantity_on_hand'   => $this->quantity_on_hand,
            'quantity_reserved'  => $this->quantity_reserved,
            'quantity_available' => $this->quantity_available,
            'unit_cost'          => $this->unit_cost,
            'status'             => $this->status,
            'metadata'           => $this->metadata,
            'created_at'         => $this->created_at,
            'updated_at'         => $this->updated_at,
        ];
    }
}
