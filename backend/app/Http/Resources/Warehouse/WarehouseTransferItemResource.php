<?php

declare(strict_types=1);

namespace App\Http\Resources\Warehouse;

use Illuminate\Http\Resources\Json\JsonResource;

class WarehouseTransferItemResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'product_name' => $this->product->name ?? null,
            'variant_id' => $this->variant_id,
            'quantity_requested' => $this->quantity_requested,
            'quantity_shipped' => $this->quantity_shipped,
            'quantity_received' => $this->quantity_received,
            'pending_quantity' => $this->pending_quantity,
            'unit' => $this->whenLoaded('unit', fn() => [
                'id' => $this->unit->id,
                'name' => $this->unit->name,
                'symbol' => $this->unit->symbol,
            ]),
            'batch_number' => $this->batch_number,
            'serial_number' => $this->serial_number,
            'lot_number' => $this->lot_number,
            'expiry_date' => $this->expiry_date?->format('Y-m-d'),
            'unit_cost' => $this->unit_cost,
            'total_cost' => $this->total_cost,
            'notes' => $this->notes,
        ];
    }
}
