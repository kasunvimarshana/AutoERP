<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BatchLotResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                 => $this->id,
            'uuid'               => $this->uuid,
            'tenant_id'          => $this->tenant_id,
            'product_id'         => $this->product_id,
            'batch_number'       => $this->batch_number,
            'lot_number'         => $this->lot_number,
            'manufacture_date'   => $this->manufacture_date,
            'expiry_date'        => $this->expiry_date,
            'initial_quantity'   => $this->initial_quantity,
            'remaining_quantity' => $this->remaining_quantity,
            'supplier_batch'     => $this->supplier_batch,
            'metadata'           => $this->metadata,
            'created_at'         => $this->created_at,
            'updated_at'         => $this->updated_at,
        ];
    }
}
