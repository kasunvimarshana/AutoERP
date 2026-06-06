<?php

declare(strict_types=1);

namespace Modules\Vehicle\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Vehicle\Http\Resources\Concerns\FormatsVehicleResources;

final class VehicleCategoryResource extends JsonResource
{
    use FormatsVehicleResources;

    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->getKey(),
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'parent' => $this->relationLoaded('parent') ? $this->namedResource($this->parent) : null,
            'is_active' => (bool) $this->is_active,
            'sort_order' => (int) $this->sort_order,
        ];
    }
}
