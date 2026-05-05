<?php

declare(strict_types=1);

namespace Modules\Fleet\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VehicleTypeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'tenant_id'        => $this->tenant_id,
            'name'             => $this->name,
            'description'      => $this->description,
            'base_daily_rate'  => $this->base_daily_rate,
            'base_hourly_rate' => $this->base_hourly_rate,
            'seating_capacity' => $this->seating_capacity,
            'is_active'        => $this->is_active,
            'created_at'       => $this->created_at,
            'updated_at'       => $this->updated_at,
        ];
    }
}
