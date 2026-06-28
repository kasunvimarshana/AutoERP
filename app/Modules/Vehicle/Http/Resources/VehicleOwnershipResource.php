<?php

declare(strict_types=1);

namespace Modules\Vehicle\Http\Resources;

use BackedEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class VehicleOwnershipResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->getKey(),
            'row_version' => (int) $this->row_version,
            'owner_type' => $this->owner_type instanceof BackedEnum ? $this->owner_type->value : $this->owner_type,
            'owner' => [
                'id' => $this->owner_id === null ? null : (int) $this->owner_id,
                'code' => $this->owner_code_snapshot,
                'name' => $this->owner_name_snapshot,
            ],
            'customer' => ($this->owner_type instanceof BackedEnum ? $this->owner_type->value : $this->owner_type) === 'customer' ? [
                'id' => (int) $this->owner_id, 'code' => $this->owner_code_snapshot, 'number' => $this->owner_code_snapshot, 'name' => $this->owner_name_snapshot, 'status' => 'snapshot',
            ] : null,
            'supplier' => ($this->owner_type instanceof BackedEnum ? $this->owner_type->value : $this->owner_type) === 'supplier' ? [
                'id' => (int) $this->owner_id, 'code' => $this->owner_code_snapshot, 'number' => $this->owner_code_snapshot, 'name' => $this->owner_name_snapshot, 'status' => 'snapshot',
            ] : null,
            'vehicle' => $this->relationLoaded('vehicle') && $this->vehicle ? [
                'id' => (int) $this->vehicle->getKey(),
                'number' => $this->vehicle->vehicle_number,
                'registration_number' => $this->vehicle->registration_number,
                'chassis_number' => $this->vehicle->chassis_number,
                'make' => $this->vehicle->make?->name,
                'model' => $this->vehicle->model?->name,
            ] : ['id' => (int) $this->vehicle_id],
            'organization' => $this->relationLoaded('organizationUnit') && $this->organizationUnit ? [
                'id' => (int) $this->organizationUnit->getKey(),
                'name' => $this->organizationUnit->name,
            ] : null,
            'relationship_type' => $this->ownership_type instanceof BackedEnum ? $this->ownership_type->value : $this->ownership_type,
            'ownership_type' => $this->ownership_type instanceof BackedEnum ? $this->ownership_type->value : $this->ownership_type,
            'started_at' => $this->started_at?->toISOString(),
            'ended_at' => $this->ended_at?->toISOString(),
            'is_current' => (bool) $this->is_current,
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
