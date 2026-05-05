<?php declare(strict_types=1);

namespace Modules\Driver\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DriverResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'tenant_id' => $this->resource->tenantId,
            'first_name' => $this->resource->firstName,
            'last_name' => $this->resource->lastName,
            'email' => $this->resource->email,
            'phone' => $this->resource->phone,
            'date_of_birth' => $this->resource->dateOfBirth->format('Y-m-d'),
            'driver_type' => $this->resource->driverType,
            'is_available' => $this->resource->isAvailable,
            'hire_date' => $this->resource->hireDate?->format('Y-m-d'),
        ];
    }
}
