<?php

declare(strict_types=1);

namespace Modules\Vehicle\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class VehicleOwnershipResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->getKey(),
            'row_version' => (int) $this->row_version,
            'owner_type' => $this->owner_type->value,
            'owner_id' => $this->owner_id === null ? null : (int) $this->owner_id,
            'owner' => [
                'id' => $this->owner_id === null ? null : (int) $this->owner_id,
                'code' => $this->owner_code_snapshot,
                'name' => $this->owner_name_snapshot,
            ],
            'vehicle' => $this->relationLoaded('vehicle') ? [
                'id' => (int) $this->vehicle->getKey(),
                'number' => $this->vehicle->vehicle_number,
                'registration_number' => $this->vehicle->registration_number,
                'chassis_number' => $this->vehicle->chassis_number,
                'make' => $this->vehicle->make?->name,
                'model' => $this->vehicle->model?->name,
            ] : null,
            'ownership_type' => $this->ownership_type->value,
            'started_at' => $this->started_at?->toISOString(),
            'ended_at' => $this->ended_at?->toISOString(),
            'is_current' => (bool) $this->is_current,
            'supersedes_ownership_id' => $this->supersedes_ownership_id === null ? null : (int) $this->supersedes_ownership_id,
            'correction_reason' => $this->correction_reason,
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
