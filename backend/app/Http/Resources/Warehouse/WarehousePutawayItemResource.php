<?php

declare(strict_types=1);

namespace App\Http\Resources\Warehouse;

use Illuminate\Http\Resources\Json\JsonResource;

class WarehousePutawayItemResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'product_name' => $this->product->name ?? null,
            'variant_id' => $this->variant_id,
            'destination_location' => $this->whenLoaded('destinationLocation', fn() => [
                'id' => $this->destinationLocation->id,
                'name' => $this->destinationLocation->name,
                'code' => $this->destinationLocation->code,
            ]),
            'quantity_to_putaway' => $this->quantity_to_putaway,
            'quantity_putaway' => $this->quantity_putaway,
            'remaining_quantity' => $this->remaining_quantity,
            'unit' => $this->whenLoaded('unit', fn() => [
                'id' => $this->unit->id,
                'name' => $this->unit->name,
                'symbol' => $this->unit->symbol,
            ]),
            'batch_number' => $this->batch_number,
            'serial_number' => $this->serial_number,
            'manufacture_date' => $this->manufacture_date?->format('Y-m-d'),
            'expiry_date' => $this->expiry_date?->format('Y-m-d'),
            'unit_cost' => $this->unit_cost,
            'total_cost' => $this->total_cost,
            'sequence' => $this->sequence,
            'status' => $this->status,
            'notes' => $this->notes,
        ];
    }
}
