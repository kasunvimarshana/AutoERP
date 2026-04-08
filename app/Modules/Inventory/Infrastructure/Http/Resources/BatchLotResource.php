<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Http\Resources;

use Modules\Core\Infrastructure\Http\Resources\BaseResource;

class BatchLotResource extends BaseResource
{
    public function toArray($request): array
    {
        return [
            'id'               => $this->id,
            'product_id'       => $this->product_id,
            'variant_id'       => $this->variant_id,
            'batch_number'     => $this->batch_number,
            'lot_number'       => $this->lot_number,
            'serial_number'    => $this->serial_number,
            'manufacture_date' => $this->manufacture_date?->toDateString(),
            'expiry_date'      => $this->expiry_date?->toDateString(),
            'quantity'         => $this->quantity,
            'unit_cost'        => $this->unit_cost,
            'status'           => $this->status,
            'created_at'       => $this->created_at?->toIso8601String(),
            'updated_at'       => $this->updated_at?->toIso8601String(),
        ];
    }
}
