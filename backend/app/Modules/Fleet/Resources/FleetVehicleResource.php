<?php

namespace App\Modules\Fleet\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class FleetVehicleResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'vehicle_number' => $this->vehicle_number,
            'make' => $this->make,
            'model' => $this->model,
            'year' => $this->year,
            'vin' => $this->vin,
            'license_plate' => $this->license_plate,
            'color' => $this->color,
            'mileage' => $this->mileage,
            'fuel_type' => $this->fuel_type,
            'status' => $this->status,
            'last_service_date' => $this->last_service_date ? $this->last_service_date->toDateString() : null,
            'next_service_due' => $this->next_service_due ? $this->next_service_due->toDateString() : null,
            'notes' => $this->notes,
            'maintenance_records' => $this->whenLoaded('maintenanceRecords', function () {
                return MaintenanceRecordResource::collection($this->maintenanceRecords);
            }),
            'tenant' => $this->whenLoaded('tenant', function () {
                return [
                    'id' => $this->tenant->id,
                    'name' => $this->tenant->name,
                    'domain' => $this->tenant->domain,
                ];
            }),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
