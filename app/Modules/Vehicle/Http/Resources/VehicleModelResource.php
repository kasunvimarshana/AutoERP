<?php

declare(strict_types=1);

namespace Modules\Vehicle\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Vehicle\Http\Resources\Concerns\FormatsVehicleResources;

final class VehicleModelResource extends JsonResource
{
    use FormatsVehicleResources;

    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->getKey(),
            'code' => $this->code,
            'name' => $this->name,
            'make' => $this->relationLoaded('make') ? $this->namedResource($this->make) : null,
            'year_from' => $this->year_from,
            'year_to' => $this->year_to,
            'description' => $this->description,
            'is_active' => (bool) $this->is_active,
        ];
    }
}
