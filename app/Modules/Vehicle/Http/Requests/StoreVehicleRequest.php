<?php

declare(strict_types=1);

namespace Modules\Vehicle\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\Vehicle\DTOs\CreateVehicleData;
use Modules\Vehicle\Enums\VehicleFuelType;
use Modules\Vehicle\Enums\VehicleStatus;
use Modules\Vehicle\Enums\VehicleTransmissionType;
use Modules\Vehicle\Http\Requests\Concerns\MapsVehicleData;

final class StoreVehicleRequest extends TenantScopedRequest
{
    use MapsVehicleData;

    public function rules(): array { return self::vehicleRules(); }
    public function toData(): CreateVehicleData { return $this->mapVehicleData($this->validated()); }

    public static function vehicleRules(string $prefix = ''): array
    {
        $key = fn (string $name): string => $prefix.$name;
        return [
            $key('tenant_id') => $prefix === '' ? ['required', 'integer', 'min:1'] : ['prohibited'],
            $key('organization_unit_id') => $prefix === '' ? ['nullable', 'integer', 'min:1'] : ['prohibited'],
            $key('vehicle_number') => ['nullable', 'string', 'max:80'],
            $key('code') => ['nullable', 'string', 'max:80'],
            $key('vehicle_make_id') => ['nullable', 'integer', 'min:1'],
            $key('vehicle_model_id') => ['nullable', 'integer', 'min:1'],
            $key('vehicle_type_id') => ['nullable', 'integer', 'min:1'],
            $key('vehicle_category_id') => ['nullable', 'integer', 'min:1'],
            $key('registration_number') => ['nullable', 'string', 'max:100'],
            $key('chassis_number') => ['nullable', 'string', 'max:150'],
            $key('engine_number') => ['nullable', 'string', 'max:150'],
            $key('vin_number') => ['nullable', 'string', 'max:150'],
            $key('manufacture_year') => ['nullable', 'integer', 'between:1886,'.(((int) date('Y')) + 1)],
            $key('registration_date') => ['nullable', 'date'],
            $key('color') => ['nullable', 'string', 'max:80'],
            $key('fuel_type') => ['nullable', Rule::enum(VehicleFuelType::class)],
            $key('transmission_type') => ['nullable', Rule::enum(VehicleTransmissionType::class)],
            $key('odometer_reading') => ['nullable', 'decimal:0,6', 'gte:0'],
            $key('odometer_unit') => ['nullable', 'string', 'max:30'],
            $key('fuel_level') => ['nullable', 'string', 'max:50'],
            $key('status') => ['nullable', Rule::enum(VehicleStatus::class)],
            $key('notes') => ['nullable', 'string'],
            $key('metadata') => ['nullable', 'array'],
        ];
    }
}
