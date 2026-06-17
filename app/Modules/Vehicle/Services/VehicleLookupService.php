<?php

declare(strict_types=1);

namespace Modules\Vehicle\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Modules\Vehicle\DTOs\VehicleResultData;
use Modules\Vehicle\Enums\VehicleStatus;
use Modules\Vehicle\Models\Vehicle;
use Modules\Vehicle\Models\VehicleOwnership;

final class VehicleLookupService
{
    public function activeVehicles(int $tenantId, ?int $organizationUnitId = null): Collection { return $this->baseQuery($tenantId, $organizationUnitId)->active()->get(); }
    public function vehiclesByCustomer(int $tenantId, int $customerId, ?int $organizationUnitId = null): Collection
    {
        return $this->baseQuery($tenantId, $organizationUnitId)
            ->whereCurrentOwner(VehicleOwnership::OWNER_TYPE_CUSTOMER, $customerId)
            ->with('currentOwnerships.customerOwner')
            ->get();
    }
    public function vehiclesByRegistrationNumber(int $tenantId, string $registrationNumber, ?int $organizationUnitId = null): Collection { return $this->baseQuery($tenantId, $organizationUnitId)->where('registration_number', $registrationNumber)->get(); }
    public function vehiclesAvailableForService(int $tenantId, ?int $organizationUnitId = null): Collection { return $this->baseQuery($tenantId, $organizationUnitId)->whereIn('status', [VehicleStatus::Active->value, VehicleStatus::UnderService->value])->get(); }
    public function vehiclesAvailableForRental(int $tenantId, ?int $organizationUnitId = null): Collection { return $this->baseQuery($tenantId, $organizationUnitId)->where('status', VehicleStatus::Active->value)->get(); }
    public function vehiclesByStatus(int $tenantId, VehicleStatus $status, ?int $organizationUnitId = null): Collection { return $this->baseQuery($tenantId, $organizationUnitId)->where('status', $status->value)->get(); }
    public function vehiclesByType(int $tenantId, int $typeId, ?int $organizationUnitId = null): Collection { return $this->baseQuery($tenantId, $organizationUnitId)->where('vehicle_type_id', $typeId)->get(); }
    public function vehiclesByCategory(int $tenantId, int $categoryId, ?int $organizationUnitId = null): Collection { return $this->baseQuery($tenantId, $organizationUnitId)->where('vehicle_category_id', $categoryId)->get(); }

    public function result(Vehicle $vehicle): VehicleResultData
    {
        return new VehicleResultData((int) $vehicle->getKey(), (int) $vehicle->tenant_id, $vehicle->organization_unit_id, (string) $vehicle->vehicle_number, $vehicle->code, $vehicle->registration_number, $vehicle->status, (string) $vehicle->odometer_reading);
    }

    private function baseQuery(int $tenantId, ?int $organizationUnitId): Builder { return Vehicle::query()->forTenant($tenantId, $organizationUnitId); }
}
