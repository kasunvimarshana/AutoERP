<?php

declare(strict_types=1);

namespace Modules\Customer\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class CustomerVehicleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->getKey(),
            'customer' => $this->relationLoaded('customer') ? ['id' => (int) $this->customer->getKey(), 'code' => $this->customer->code, 'number' => $this->customer->customer_number, 'name' => $this->customer->name, 'status' => $this->customer->status instanceof \BackedEnum ? $this->customer->status->value : $this->customer->status] : null,
            'vehicle' => $this->relationLoaded('vehicle') ? ['id' => (int) $this->vehicle->getKey(), 'number' => $this->vehicle->vehicle_number, 'registration_number' => $this->vehicle->registration_number, 'chassis_number' => $this->vehicle->chassis_number, 'make' => $this->vehicle->make?->name, 'model' => $this->vehicle->model?->name] : null,
            'organization' => $this->relationLoaded('organizationUnit') && $this->organizationUnit ? ['id' => (int) $this->organizationUnit->getKey(), 'name' => $this->organizationUnit->name] : null,
            'relationship_type' => $this->relationship_type,
            'started_at' => $this->started_at?->toISOString(), 'ended_at' => $this->ended_at?->toISOString(), 'is_current' => (bool) $this->is_current, 'notes' => $this->notes,
            'created_at' => $this->created_at?->toISOString(), 'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
