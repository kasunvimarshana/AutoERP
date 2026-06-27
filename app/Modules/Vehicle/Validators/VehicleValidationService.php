<?php

declare(strict_types=1);

namespace Modules\Vehicle\Validators;

use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\OrganizationUnit\Models\OrganizationUnitModel;
use Modules\Vehicle\DTOs\CreateVehicleData;
use Modules\Vehicle\DTOs\UpdateVehicleData;
use Modules\Vehicle\DTOs\VehicleModelData;
use Modules\Vehicle\Models\Vehicle;
use Modules\Vehicle\Models\VehicleCategory;
use Modules\Vehicle\Models\VehicleMake;
use Modules\Vehicle\Models\VehicleModel;
use Modules\Vehicle\Models\VehicleType;

final class VehicleValidationService
{
    public function __construct(private readonly DecimalMath $math) {}

    public function validateCreate(CreateVehicleData $data): void
    {
        if ($data->vehicleNumber !== null) {
            $this->assertNumberUnique($data->tenantId, $data->vehicleNumber);
        }
        $this->assertUniqueNullable('code', $data->tenantId, $data->code, 'Vehicle code already exists for this tenant.');
        $this->assertUniqueNullable('registration_number', $data->tenantId, $data->registrationNumber, 'Vehicle registration number already exists for this tenant.');
        $this->assertUniqueNullable('chassis_number', $data->tenantId, $data->chassisNumber, 'Vehicle chassis number already exists for this tenant.');
        $this->assertUniqueNullable('engine_number', $data->tenantId, $data->engineNumber, 'Vehicle engine number already exists for this tenant.');
        $this->assertUniqueNullable('vin_number', $data->tenantId, $data->vinNumber, 'Vehicle VIN already exists for this tenant.');
        $this->assertNonNegative($data->odometerReading, 'Vehicle odometer reading cannot be negative.');
        $this->assertManufactureYear($data->manufactureYear);
        $this->assertOrganizationUsable($data->tenantId, $data->organizationUnitId);
        $this->assertReferences($data->tenantId, $data->organizationUnitId, $data->vehicleMakeId, $data->vehicleModelId, $data->vehicleTypeId, $data->vehicleCategoryId);
    }

    public function validateUpdate(Vehicle $vehicle, UpdateVehicleData $data): void
    {
        $tenantId = (int) $vehicle->tenant_id;
        $vehicleId = (int) $vehicle->getKey();
        $this->assertUniqueNullable('code', $tenantId, $data->code, 'Vehicle code already exists for this tenant.', $vehicleId);
        $this->assertUniqueNullable('registration_number', $tenantId, $data->registrationNumber, 'Vehicle registration number already exists for this tenant.', $vehicleId);
        $this->assertUniqueNullable('chassis_number', $tenantId, $data->chassisNumber, 'Vehicle chassis number already exists for this tenant.', $vehicleId);
        $this->assertUniqueNullable('engine_number', $tenantId, $data->engineNumber, 'Vehicle engine number already exists for this tenant.', $vehicleId);
        $this->assertUniqueNullable('vin_number', $tenantId, $data->vinNumber, 'Vehicle VIN already exists for this tenant.', $vehicleId);
        if ($data->odometerReading !== null) {
            $this->assertNonNegative($data->odometerReading, 'Vehicle odometer reading cannot be negative.');
        }
        $this->assertManufactureYear($data->manufactureYear);
        $this->assertOrganizationUsable($tenantId, $data->organizationUnitId);
        $this->assertReferences($tenantId, $data->organizationUnitId, $data->vehicleMakeId, $data->vehicleModelId, $data->vehicleTypeId, $data->vehicleCategoryId);
    }

    public function validateModelData(VehicleModelData $data): VehicleMake
    {
        $make = VehicleMake::query()->findOrFail($data->vehicleMakeId);
        $this->assertScope($data->tenantId, $data->organizationUnitId, (int) $make->tenant_id, $make->organization_unit_id);
        if (! (bool) $make->is_active) {
            throw new InvalidArgumentException('Inactive vehicle make cannot be used for a model.');
        }
        if ($data->yearFrom !== null && ($data->yearFrom < 1886 || $data->yearFrom > ((int) date('Y')) + 1)) {
            throw new InvalidArgumentException('Vehicle model year from is invalid.');
        }
        if ($data->yearTo !== null && ($data->yearTo < 1886 || $data->yearTo > ((int) date('Y')) + 1)) {
            throw new InvalidArgumentException('Vehicle model year to is invalid.');
        }
        if ($data->yearFrom !== null && $data->yearTo !== null && $data->yearFrom > $data->yearTo) {
            throw new InvalidArgumentException('Vehicle model year from cannot be after year to.');
        }

        return $make;
    }

    public function assertOrganizationUsable(int $tenantId, ?int $organizationUnitId): void
    {
        if ($organizationUnitId === null) {
            return;
        }
        $organization = OrganizationUnitModel::query()->findOrFail($organizationUnitId);
        if ((int) $organization->tenant_id !== $tenantId) {
            throw new InvalidArgumentException('Vehicle organization unit belongs to a different tenant.');
        }
        if (! (bool) $organization->is_active) {
            throw new InvalidArgumentException('Vehicle organization unit must be active.');
        }
    }

    public function assertReferenceUsable(int $tenantId, ?int $organizationUnitId, object $record, string $message): void
    {
        $this->assertScope($tenantId, $organizationUnitId, (int) $record->tenant_id, $record->organization_unit_id);
        if (method_exists($record, 'getAttribute') && $record->getAttribute('is_active') !== null && ! (bool) $record->getAttribute('is_active')) {
            throw new InvalidArgumentException($message);
        }
    }

    public function assertScope(int $tenantId, ?int $organizationUnitId, int $recordTenantId, ?int $recordOrganizationUnitId): void
    {
        if ($recordTenantId !== $tenantId) {
            throw new InvalidArgumentException('Vehicle reference belongs to a different tenant.');
        }
        if (($organizationUnitId === null && $recordOrganizationUnitId !== null)
            || ($organizationUnitId !== null && $recordOrganizationUnitId !== null && (int) $recordOrganizationUnitId !== $organizationUnitId)) {
            throw new InvalidArgumentException('Vehicle reference belongs to a different organization unit.');
        }
    }

    private function assertReferences(int $tenantId, ?int $organizationUnitId, ?int $makeId, ?int $modelId, ?int $typeId, ?int $categoryId): void
    {
        if ($makeId !== null) {
            $make = VehicleMake::query()->findOrFail($makeId);
            $this->assertReferenceUsable($tenantId, $organizationUnitId, $make, 'Inactive vehicle make cannot be used.');
        }
        if ($modelId !== null) {
            $model = VehicleModel::query()->findOrFail($modelId);
            $this->assertReferenceUsable($tenantId, $organizationUnitId, $model, 'Inactive vehicle model cannot be used.');
            if ($makeId !== null && (int) $model->vehicle_make_id !== $makeId) {
                throw new InvalidArgumentException('Vehicle model must belong to the selected make.');
            }
        }
        if ($typeId !== null) {
            $type = VehicleType::query()->findOrFail($typeId);
            $this->assertReferenceUsable($tenantId, $organizationUnitId, $type, 'Inactive vehicle type cannot be used.');
        }
        if ($categoryId !== null) {
            $category = VehicleCategory::query()->findOrFail($categoryId);
            $this->assertReferenceUsable($tenantId, $organizationUnitId, $category, 'Inactive vehicle category cannot be used.');
        }
    }

    private function assertNumberUnique(int $tenantId, string $number, ?int $ignoreId = null): void
    {
        $query = Vehicle::query()->withTrashed()->where('tenant_id', $tenantId)->where('vehicle_number', $number);
        $this->ignoreKey($query, $ignoreId);
        if ($query->exists()) {
            throw new InvalidArgumentException('Vehicle number already exists for this tenant.');
        }
    }

    private function assertUniqueNullable(string $column, int $tenantId, ?string $value, string $message, ?int $ignoreId = null): void
    {
        if ($value === null || trim($value) === '') {
            return;
        }
        $query = Vehicle::query()->withTrashed()->where('tenant_id', $tenantId)->where($column, $value);
        $this->ignoreKey($query, $ignoreId);
        if ($query->exists()) {
            throw new InvalidArgumentException($message);
        }
    }

    private function assertManufactureYear(?int $year): void
    {
        if ($year !== null && ($year < 1886 || $year > ((int) date('Y')) + 1)) {
            throw new InvalidArgumentException('Vehicle manufacture year is invalid.');
        }
    }

    private function assertNonNegative(string $value, string $message): void
    {
        if ($this->math->isNegative($value)) {
            throw new InvalidArgumentException($message);
        }
    }

    private function ignoreKey(Builder $query, ?int $id): void
    {
        if ($id !== null) {
            $query->whereKeyNot($id);
        }
    }
}
