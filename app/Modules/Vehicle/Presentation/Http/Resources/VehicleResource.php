<?php

declare(strict_types=1);

namespace Modules\Vehicle\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VehicleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'organization_unit_id' => $this->organization_unit_id,
            'vehicle_code' => $this->vehicle_code,
            'vin' => $this->vin,
            'license_plate' => $this->license_plate,
            'make' => $this->make,
            'model' => $this->model,
            'year' => $this->year,
            'color' => $this->color,
            'category' => $this->category,
            'usage_profile' => $this->usage_profile,
            'fuel_type' => $this->fuel_type,
            'transmission' => $this->transmission,
            'seating_capacity' => $this->seating_capacity,
            'current_odometer' => $this->current_odometer,
            'status' => $this->status,
            'registration_expiry' => $this->registration_expiry,
            'insurance_expiry' => $this->insurance_expiry,
            'last_service_date' => $this->last_service_date,
            'last_service_odometer' => $this->last_service_odometer,
            'next_service_due_date' => $this->next_service_due_date,
            'next_service_due_odometer' => $this->next_service_due_odometer,
            'metadata' => $this->metadata,
            'row_version' => $this->row_version,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
        ];
    }
}
