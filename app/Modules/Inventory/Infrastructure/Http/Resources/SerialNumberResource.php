<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SerialNumberResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'               => $this->id,
            'uuid'             => $this->uuid,
            'tenant_id'        => $this->tenant_id,
            'product_id'       => $this->product_id,
            'variant_id'       => $this->variant_id,
            'serial_number'    => $this->serial_number,
            'status'           => $this->status,
            'location_id'      => $this->location_id,
            'manufacture_date' => $this->manufacture_date,
            'expiry_date'      => $this->expiry_date,
            'metadata'         => $this->metadata,
            'created_at'       => $this->created_at,
            'updated_at'       => $this->updated_at,
        ];
    }
}
