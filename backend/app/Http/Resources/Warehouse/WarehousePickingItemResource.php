<?php

declare(strict_types=1);

namespace App\Http\Resources\Warehouse;

use Illuminate\Http\Resources\Json\JsonResource;

class WarehousePickingItemResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'product_name' => $this->product->name ?? null,
            'variant_id' => $this->variant_id,
            'location' => $this->whenLoaded('location', fn() => [
                'id' => $this->location->id,
                'name' => $this->location->name,
                'code' => $this->location->code,
            ]),
            'quantity_required' => $this->quantity_required,
            'quantity_picked' => $this->quantity_picked,
            'remaining_quantity' => $this->remaining_quantity,
            'unit' => $this->whenLoaded('unit', fn() => [
                'id' => $this->unit->id,
                'name' => $this->unit->name,
                'symbol' => $this->unit->symbol,
            ]),
            'batch_number' => $this->batch_number,
            'serial_number' => $this->serial_number,
            'sequence' => $this->sequence,
            'status' => $this->status,
            'notes' => $this->notes,
        ];
    }
}
