<?php

declare(strict_types=1);

namespace Modules\VehicleService\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class VehicleServiceJobTypeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->getKey(),
            'row_version' => (int) $this->row_version,
            'tenant_id' => (int) $this->tenant_id,
            'organization_unit_id' => $this->organization_unit_id === null ? null : (int) $this->organization_unit_id,
            'code' => (string) $this->code,
            'name' => (string) $this->name,
            'description' => $this->description === null ? null : (string) $this->description,
            'tracks_mileage' => (bool) $this->tracks_mileage,
            'is_active' => (bool) $this->is_active,
            'is_system' => (bool) $this->is_system,
            'sort_order' => (int) $this->sort_order,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
