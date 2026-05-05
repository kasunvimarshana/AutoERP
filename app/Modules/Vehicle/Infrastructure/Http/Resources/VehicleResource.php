<?php

declare(strict_types=1);

namespace Modules\Vehicle\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VehicleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        if (is_object($this->resource) && method_exists($this->resource, 'getId')) {
            return [
                'id' => $this->resource->getId(),
                'tenant_id' => $this->resource->getTenantId(),
                'ownership_type' => $this->resource->getOwnershipType(),
                'make' => $this->resource->getMake(),
                'model' => $this->resource->getModel(),
                'vin' => $this->resource->getVin(),
                'registration_number' => $this->resource->getRegistrationNumber(),
                'chassis_number' => $this->resource->getChassisNumber(),
                'rental_status' => $this->resource->getRentalStatus(),
                'service_status' => $this->resource->getServiceStatus(),
                'next_maintenance_due_at' => $this->resource->getNextMaintenanceDueAt(),
            ];
        }

        return [
            'id' => $this->resource->id,
            'tenant_id' => $this->resource->tenant_id,
            'ownership_type' => $this->resource->ownership_type,
            'make' => $this->resource->make,
            'model' => $this->resource->model,
            'vin' => $this->resource->vin,
            'registration_number' => $this->resource->registration_number,
            'chassis_number' => $this->resource->chassis_number,
            'rental_status' => $this->resource->rental_status,
            'service_status' => $this->resource->service_status,
            'next_maintenance_due_at' => $this->resource->next_maintenance_due_at,
        ];
    }
}
