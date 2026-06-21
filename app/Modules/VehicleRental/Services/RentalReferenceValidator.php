<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Services;

use Illuminate\Database\Eloquent\Builder;
use Modules\ReferenceData\Models\CurrencyModel;
use Modules\Customer\Models\Customer;
use Modules\Hr\Models\HrEmployee;
use Modules\Supplier\Models\Supplier;
use Modules\Tax\Models\TaxGroup;
use Modules\Vehicle\Models\Vehicle;
use Modules\Vehicle\Models\VehicleCategory;
use Modules\VehicleRental\Models\RentalAgreement;
use Modules\VehicleRental\Models\RentalUsageLog;
use Modules\VehicleRental\Models\RentalVehicleAllocation;

final class RentalReferenceValidator
{
    public function customer(int $id, int $tenantId, ?int $organizationUnitId, bool $activeOnly = true): Customer
    {
        return Customer::query()
            ->forTenant($tenantId, $organizationUnitId)
            ->when($activeOnly, fn (Builder $query) => $query->where('status', 'active'))
            ->findOrFail($id);
    }

    public function supplier(int $id, int $tenantId, ?int $organizationUnitId, bool $activeOnly = true): Supplier
    {
        return Supplier::query()
            ->forTenant($tenantId, $organizationUnitId)
            ->when($activeOnly, fn (Builder $query) => $query->where('status', 'active'))
            ->findOrFail($id);
    }

    public function currency(int $id, bool $activeOnly = true): CurrencyModel
    {
        return CurrencyModel::query()
            ->when($activeOnly, fn (Builder $query) => $query->where('is_active', true))
            ->findOrFail($id);
    }

    public function taxGroup(?int $id, int $tenantId, ?int $organizationUnitId): ?TaxGroup
    {
        if ($id === null) {
            return null;
        }

        return TaxGroup::query()
            ->where('tenant_id', $tenantId)
            ->where(function (Builder $query) use ($organizationUnitId): void {
                $query->whereNull('organization_unit_id');
                if ($organizationUnitId !== null) {
                    $query->orWhere('organization_unit_id', $organizationUnitId);
                }
            })
            ->where('active', true)
            ->findOrFail($id);
    }

    public function vehicle(int $id, int $tenantId, ?int $organizationUnitId): Vehicle
    {
        return Vehicle::query()->forTenant($tenantId, $organizationUnitId)->findOrFail($id);
    }

    public function vehicleCategory(?int $id, int $tenantId, ?int $organizationUnitId): ?VehicleCategory
    {
        if ($id === null) {
            return null;
        }

        return VehicleCategory::query()
            ->where('tenant_id', $tenantId)
            ->where(function (Builder $query) use ($organizationUnitId): void {
                $query->whereNull('organization_unit_id');
                if ($organizationUnitId !== null) {
                    $query->orWhere('organization_unit_id', $organizationUnitId);
                }
            })
            ->where('is_active', true)
            ->findOrFail($id);
    }

    public function employee(?int $id, int $tenantId, ?int $organizationUnitId, bool $activeOnly = true): ?HrEmployee
    {
        if ($id === null) {
            return null;
        }

        return HrEmployee::query()
            ->forTenant($tenantId, $organizationUnitId)
            ->when($activeOnly, fn (Builder $query) => $query->where('status', 'active'))
            ->findOrFail($id);
    }

    public function agreement(int $id, int $tenantId, ?int $organizationUnitId): RentalAgreement
    {
        return RentalAgreement::query()->forContext($tenantId, $organizationUnitId)->findOrFail($id);
    }

    public function allocation(int $id, int $tenantId, ?int $organizationUnitId): RentalVehicleAllocation
    {
        return RentalVehicleAllocation::query()->forContext($tenantId, $organizationUnitId)->findOrFail($id);
    }

    public function usageLog(int $id, int $tenantId, ?int $organizationUnitId): RentalUsageLog
    {
        return RentalUsageLog::query()->forContext($tenantId, $organizationUnitId)->findOrFail($id);
    }
}
