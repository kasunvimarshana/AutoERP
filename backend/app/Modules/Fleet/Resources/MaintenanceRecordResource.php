<?php

namespace App\Modules\Fleet\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MaintenanceRecordResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'fleet_vehicle_id' => $this->fleet_vehicle_id,
            'maintenance_type' => $this->maintenance_type,
            'description' => $this->description,
            'service_date' => $this->service_date ? $this->service_date->toDateString() : null,
            'mileage_at_service' => $this->mileage_at_service,
            'cost' => number_format((float) $this->cost, 2, '.', ''),
            'performed_by' => $this->performed_by,
            'next_service_date' => $this->next_service_date ? $this->next_service_date->toDateString() : null,
            'notes' => $this->notes,
            'status' => $this->status,
            'fleet_vehicle' => $this->whenLoaded('fleetVehicle', function () {
                return [
                    'id' => $this->fleetVehicle->id,
                    'vehicle_number' => $this->fleetVehicle->vehicle_number,
                    'make' => $this->fleetVehicle->make,
                    'model' => $this->fleetVehicle->model,
                    'license_plate' => $this->fleetVehicle->license_plate,
                ];
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
