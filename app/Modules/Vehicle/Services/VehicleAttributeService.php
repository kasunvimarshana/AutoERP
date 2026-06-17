<?php

declare(strict_types=1);

namespace Modules\Vehicle\Services;

use DateTimeImmutable;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Vehicle\DTOs\VehicleAttributeData;
use Modules\Vehicle\Enums\VehicleAttributeDataType;
use Modules\Vehicle\Models\Vehicle;
use Modules\Vehicle\Models\VehicleAttribute;

final class VehicleAttributeService
{
    public function __construct(private readonly DecimalMath $math) {}

    public function create(Vehicle $vehicle, VehicleAttributeData $data): VehicleAttribute
    {
        $this->validate($data);
        $this->assertUniqueKey($vehicle, $data->attributeKey);

        return $vehicle->attributes()->create($this->attributes($vehicle, $data));
    }

    public function update(Vehicle $vehicle, VehicleAttribute $attribute, VehicleAttributeData $data): VehicleAttribute
    {
        $this->assertOwned($vehicle, $attribute);
        $this->validate($data);
        $this->assertUniqueKey($vehicle, $data->attributeKey, (int) $attribute->getKey());

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
        $this->validateTypedValue($data);
    }

    private function assertUniqueKey(Vehicle $vehicle, string $attributeKey, ?int $ignoreId = null): void
    {
        $normalizedKey = trim($attributeKey);
        $query = $vehicle->attributes()->where('attribute_key', $normalizedKey);
        if ($ignoreId !== null) {
            $query->whereKeyNot($ignoreId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'attribute_key' => ['This attribute key already exists for the vehicle.'],
            ]);
        }
    }

    private function validateTypedValue(VehicleAttributeData $data): void
    {
        $value = $data->attributeValue;
        if ($value === null || trim($value) === '') {
            return;
        }

        $valid = match ($data->dataType) {
            VehicleAttributeDataType::Text => true,
            VehicleAttributeDataType::Number => preg_match('/^-?\d+$/', trim($value)) === 1,
            VehicleAttributeDataType::Decimal => $this->isDecimal($value),
            VehicleAttributeDataType::Date => $this->isBusinessDate($value),
            VehicleAttributeDataType::Boolean => in_array(strtolower(trim($value)), ['true', 'false', '1', '0'], true),
        };

        if (! $valid) {
            throw ValidationException::withMessages([
                'attribute_value' => ['The attribute value is not valid for the selected data type.'],
            ]);
        }
    }

    private function attributes(Vehicle $vehicle, VehicleAttributeData $data, bool $includeScope = true): array
    {
        return [
            ...($includeScope ? ['tenant_id' => $vehicle->tenant_id, 'organization_unit_id' => $vehicle->organization_unit_id] : []),
            'attribute_key' => trim($data->attributeKey),
            'attribute_value' => $this->storedValue($data),
            'data_type' => $data->dataType,
            'sort_order' => $data->sortOrder,
        ];
    }

    private function storedValue(VehicleAttributeData $data): ?string
    {
        if ($data->attributeValue === null) {
            return null;
        }

        return $data->dataType === VehicleAttributeDataType::Decimal
            ? $this->math->normalize($data->attributeValue)
            : $data->attributeValue;
    }

    private function isDecimal(string $value): bool
    {
        try {
            $this->math->normalize($value);
            return true;
        } catch (InvalidArgumentException) {
            return false;
        }
    }

    private function isBusinessDate(string $value): bool
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', trim($value));

        return $date !== false && $date->format('Y-m-d') === trim($value);
    }

    private function assertOwned(Vehicle $vehicle, VehicleAttribute $attribute): void
    {
        if ((int) $attribute->vehicle_id !== (int) $vehicle->getKey()) {
            throw new InvalidArgumentException('Vehicle attribute does not belong to the vehicle.');
        }
    }
}
