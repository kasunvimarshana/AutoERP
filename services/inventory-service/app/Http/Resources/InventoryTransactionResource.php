<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'tenant_id'          => $this->tenant_id,
            'inventory_item_id'  => $this->inventory_item_id,
            'type'               => $this->type,
            'quantity_before'    => $this->quantity_before,
            'quantity_change'    => $this->quantity_change,
            'quantity_after'     => $this->quantity_after,
            'reserved_before'    => $this->reserved_before,
            'reserved_change'    => $this->reserved_change,
            'reserved_after'     => $this->reserved_after,
            'reason'             => $this->reason,
            'reference_type'     => $this->reference_type,
            'reference_id'       => $this->reference_id,
            'performed_by'       => $this->performed_by,
            'metadata'           => $this->metadata,
            'created_at'         => $this->created_at?->toIso8601String(),
        ];
    }
}
