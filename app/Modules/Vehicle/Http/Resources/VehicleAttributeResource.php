<?php

declare(strict_types=1);

namespace Modules\Vehicle\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Vehicle\Http\Resources\Concerns\FormatsVehicleResources;

final class VehicleAttributeResource extends JsonResource
{
    use FormatsVehicleResources;

    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->getKey(),
            'attribute_key' => $this->attribute_key,
            'attribute_value' => $this->attribute_value,
            'data_type' => $this->enumValue($this->data_type),
            'sort_order' => (int) $this->sort_order,
        ];
    }
}
