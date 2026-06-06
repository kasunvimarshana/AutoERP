<?php

declare(strict_types=1);

namespace Modules\Vehicle\Services;

use InvalidArgumentException;
use Modules\Vehicle\DTOs\VehicleAttributeData;
use Modules\Vehicle\Models\Vehicle;
use Modules\Vehicle\Models\VehicleAttribute;

final class VehicleAttributeService
{
    public function create(Vehicle $vehicle, VehicleAttributeData $data): VehicleAttribute
    {
        $this->validate($data);
        if ($vehicle->attributes()->where('attribute_key', $data->attributeKey)->exists()) {
            throw new InvalidArgumentException('Vehicle attribute key already exists.');
        }
        return $vehicle->attributes()->create($this->attributes($vehicle, $data));
    }

    public function update(Vehicle $vehicle, VehicleAttribute $attribute, VehicleAttributeData $data): VehicleAttribute
    {
        $this->assertOwned($vehicle, $attribute);
        $this->validate($data);
        if ($vehicle->attributes()->whereKeyNot($attribute->getKey())->where('attribute_key', $data->attributeKey)->exists()) {
            throw new InvalidArgumentException('Vehicle attribute key already exists.');
        }
        $attribute->fill($this->attributes($vehicle, $data, false))->save();
        return $attribute->refresh();
    }

    public function delete(Vehicle $vehicle, VehicleAttribute $attribute): void
    {
        $this->assertOwned($vehicle, $attribute);
        $attribute->delete();
    }

    /** @param list<VehicleAttributeData> $attributes */
    public function replace(Vehicle $vehicle, array $attributes): void
    {
        $vehicle->attributes()->delete();
        foreach ($attributes as $attribute) { $this->create($vehicle, $attribute); }
    }

    private function validate(VehicleAttributeData $data): void
    {
        if (trim($data->attributeKey) === '') { throw new InvalidArgumentException('Vehicle attribute key is required.'); }
        if ($data->sortOrder < 0) { throw new InvalidArgumentException('Vehicle attribute sort order cannot be negative.'); }
    }

    private function attributes(Vehicle $vehicle, VehicleAttributeData $data, bool $includeScope = true): array
    {
        return [
            ...($includeScope ? ['tenant_id' => $vehicle->tenant_id, 'organization_unit_id' => $vehicle->organization_unit_id] : []),
            'attribute_key' => $data->attributeKey,
            'attribute_value' => $data->attributeValue,
            'data_type' => $data->dataType,
            'sort_order' => $data->sortOrder,
        ];
    }

    private function assertOwned(Vehicle $vehicle, VehicleAttribute $attribute): void
    {
        if ((int) $attribute->vehicle_id !== (int) $vehicle->getKey()) {
            throw new InvalidArgumentException('Vehicle attribute does not belong to the vehicle.');
        }
    }
}
