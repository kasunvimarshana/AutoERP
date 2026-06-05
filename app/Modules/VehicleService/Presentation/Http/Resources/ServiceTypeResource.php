<?php

declare(strict_types=1);

namespace Modules\VehicleService\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ServiceTypeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'organization_unit_id' => $this->resource->organization_unit_id ?? null,
            'name' => $this->resource->name,
            'code' => $this->resource->code,
            'description' => $this->resource->description,
            'standard_hours' => $this->resource->standard_hours === null ? null : number_format((float) $this->resource->standard_hours, 4, '.', ''),
            'is_active' => (bool) $this->resource->is_active,
            'created_at' => $this->resource->created_at,
        ];
    }
}
