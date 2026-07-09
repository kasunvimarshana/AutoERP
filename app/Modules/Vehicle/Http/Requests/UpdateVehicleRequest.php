<?php

declare(strict_types=1);

namespace Modules\Vehicle\Http\Requests;

use Illuminate\Validation\Rule;
use Modules\Core\Http\Requests\TenantScopedRequest;
use Modules\Vehicle\DTOs\UpdateVehicleData;
use Modules\Vehicle\Enums\VehicleFuelType;
use Modules\Vehicle\Enums\VehicleTransmissionType;

final class UpdateVehicleRequest extends TenantScopedRequest
{
    private const EDITABLE_FIELDS = [
        'code',
        'vehicle_make_id',
        'vehicle_model_id',
        'vehicle_type_id',
        'vehicle_category_id',
        'registration_number',
        'chassis_number',
        'engine_number',
        'vin_number',
        'manufacture_year',
        'registration_date',
        'color',
        'fuel_type',
        'transmission_type',
        'odometer_reading',
        'odometer_unit',
        'fuel_level',
        'notes',
        'metadata',
    ];

    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
            'organization_unit_id' => ['nullable', 'integer', 'min:1'],
            'row_version' => ['required', 'integer', 'min:0'],
            'code' => ['nullable', 'string', 'max:80'],
            'vehicle_make_id' => ['nullable', 'integer', 'min:1'],
            'vehicle_model_id' => ['nullable', 'integer', 'min:1'],
            'vehicle_type_id' => ['nullable', 'integer', 'min:1'],
            'vehicle_category_id' => ['nullable', 'integer', 'min:1'],
            'registration_number' => ['nullable', 'string', 'max:100'],
            'chassis_number' => ['nullable', 'string', 'max:150'],
            'engine_number' => ['nullable', 'string', 'max:150'],
            'vin_number' => ['nullable', 'string', 'max:150'],
            'manufacture_year' => ['nullable', 'integer', 'between:1886,'.(((int) date('Y')) + 1)],
            'registration_date' => ['nullable', 'date'],
            'color' => ['nullable', 'string', 'max:80'],
            'fuel_type' => ['nullable', Rule::enum(VehicleFuelType::class)],
            'transmission_type' => ['nullable', Rule::enum(VehicleTransmissionType::class)],
            'odometer_reading' => ['nullable', 'decimal:0,6', 'gte:0'],
            'odometer_unit' => ['nullable', 'string', 'max:30'],
            'fuel_level' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    public function toData(): UpdateVehicleData
    {
        return new UpdateVehicleData(
            rowVersion: (int) $this->input('row_version'),
            organizationUnitId: $this->organizationUnitId(),
            code: $this->stringOrNull('code'),
            vehicleMakeId: $this->integerOrNull('vehicle_make_id'),
            vehicleModelId: $this->integerOrNull('vehicle_model_id'),
            vehicleTypeId: $this->integerOrNull('vehicle_type_id'),
            vehicleCategoryId: $this->integerOrNull('vehicle_category_id'),
            registrationNumber: $this->stringOrNull('registration_number'),
            chassisNumber: $this->stringOrNull('chassis_number'),
            engineNumber: $this->stringOrNull('engine_number'),
            vinNumber: $this->stringOrNull('vin_number'),
            manufactureYear: $this->integerOrNull('manufacture_year'),
            registrationDate: $this->stringOrNull('registration_date'),
            color: $this->stringOrNull('color'),
            fuelType: $this->filled('fuel_type') ? VehicleFuelType::from((string) $this->input('fuel_type')) : null,
            transmissionType: $this->filled('transmission_type') ? VehicleTransmissionType::from((string) $this->input('transmission_type')) : null,
            odometerReading: $this->filled('odometer_reading') ? (string) $this->input('odometer_reading') : null,
            odometerUnit: $this->stringOrNull('odometer_unit'),
            fuelLevel: $this->stringOrNull('fuel_level'),
            notes: $this->stringOrNull('notes'),
            metadata: $this->has('metadata') ? $this->input('metadata') : null,
            provided: array_values(array_intersect(self::EDITABLE_FIELDS, array_keys($this->validated()))),
        );
    }

    private function stringOrNull(string $key): ?string { return $this->filled($key) ? (string) $this->input($key) : null; }
    private function integerOrNull(string $key): ?int { return $this->filled($key) ? (int) $this->input($key) : null; }
}
