<?php

declare(strict_types=1);

namespace Modules\Vehicle\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class VehicleListResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->getKey(),
            'organization_unit_id' => $this->resource->organization_unit_id,
            'vehicle_code' => $this->resource->vehicle_code,
            'registration_number' => $this->resource->registration_number,
            'chassis_number' => $this->resource->chassis_number,
            'engine_number' => $this->resource->engine_number,
            'make' => $this->resource->make,
            'model' => $this->resource->model,
            'year' => $this->resource->year,
            'color' => $this->resource->color,
            'vehicle_type' => $this->resource->vehicle_type,
            'fuel_type' => $this->resource->fuel_type,
            'transmission_type' => $this->resource->transmission_type,
            'ownership_type' => $this->resource->ownership_type,
            'status' => $this->resource->status,
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
        ];
    }
}
