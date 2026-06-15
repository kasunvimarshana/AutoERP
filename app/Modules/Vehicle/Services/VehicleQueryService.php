<?php

declare(strict_types=1);

namespace Modules\Vehicle\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Modules\Vehicle\Enums\VehicleOwnershipType;
use Modules\Vehicle\Enums\VehicleOwnerType;
use Modules\Vehicle\Enums\VehicleStatus;
use Modules\Vehicle\Models\Vehicle;

final class VehicleQueryService
{
    public function paginate(array $criteria, int $tenantId, ?int $organizationUnitId, int $perPage): LengthAwarePaginator
    {
        $query = $this->baseQuery($tenantId, $organizationUnitId)->with([
            'make', 'model', 'type', 'category', 'customer',
            'currentOwnership.customer', 'currentOwnership.supplier',
        ]);
        $this->applyCriteria($query, $criteria);
        $sort = in_array(($criteria['sort'] ?? null), ['vehicle_number', 'code', 'registration_number', 'status', 'created_at'], true) ? (string) $criteria['sort'] : 'vehicle_number';
        $direction = ($criteria['direction'] ?? null) === 'desc' ? 'desc' : 'asc';

        return $query->orderBy($sort, $direction)->paginate($perPage);
    }

    public function lookup(array $criteria, int $tenantId, ?int $organizationUnitId, int $perPage, string $kind): LengthAwarePaginator
    {
        if ($kind === 'active') {
            $criteria['status'] = VehicleStatus::Active->value;
        } elseif ($kind === 'by-customer' && empty($criteria['customer_id'])) {
            throw new \InvalidArgumentException('Customer is required for vehicle by-customer lookup.');
        } elseif ($kind === 'service-available') {
            $criteria['available_for_service'] = true;
        } elseif ($kind === 'rental-available') {
            $criteria['available_for_rental'] = true;
        }

        return $this->paginate($criteria, $tenantId, $organizationUnitId, min($perPage, 50));
    }

    public function find(int $id, int $tenantId, ?int $organizationUnitId): Vehicle
    {
        return $this->baseQuery($tenantId, $organizationUnitId)
            ->with(['make', 'model', 'type', 'category', 'customer', 'currentOwnership.customer', 'currentOwnership.supplier'])
            ->findOrFail($id);
    }

    public function vehicle(int $id, int $tenantId, ?int $organizationUnitId): Vehicle
    {
        return $this->baseQuery($tenantId, $organizationUnitId)->findOrFail($id);
    }

    public function delete(Vehicle $vehicle): void
    {
        $vehicle->delete();
    }

    private function baseQuery(int $tenantId, ?int $organizationUnitId): Builder
    {
        return Vehicle::query()->forTenant($tenantId, $organizationUnitId);
    }

    private function applyCriteria(Builder $query, array $criteria): void
    {
        $search = trim((string) ($criteria['search'] ?? ''));
        if ($search !== '') {
            $query->where(function (Builder $scope) use ($search): void {
                $scope->where('vehicle_number', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('registration_number', 'like', "%{$search}%")
                    ->orWhere('chassis_number', 'like', "%{$search}%")
                    ->orWhere('engine_number', 'like', "%{$search}%")
                    ->orWhere('vin_number', 'like', "%{$search}%")
                    ->orWhereHas('currentOwnership.customer', fn (Builder $owner): Builder => $owner->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('currentOwnership.supplier', fn (Builder $owner): Builder => $owner->where('name', 'like', "%{$search}%"));
            });
        }
        foreach (['status', 'vehicle_make_id', 'vehicle_model_id', 'vehicle_type_id', 'vehicle_category_id', 'customer_id'] as $filter) {
            if (array_key_exists($filter, $criteria) && $criteria[$filter] !== null && $criteria[$filter] !== '') {
                $query->where($filter, $criteria[$filter]);
            }
        }
        if (! empty($criteria['available_for_service'])) {
            $query->whereIn('status', [VehicleStatus::Active->value, VehicleStatus::UnderService->value]);
        }
        if (! empty($criteria['available_for_rental'])) {
            $query->where('status', VehicleStatus::Active->value);
        }
        $this->applyOwnershipScope($query, (string) ($criteria['scope'] ?? 'all'));
        if (! empty($criteria['owner_type']) || ! empty($criteria['owner_id'])) {
            $query->whereHas('currentOwnership', function (Builder $ownership) use ($criteria): void {
                if (! empty($criteria['owner_type'])) {
                    $ownership->where('owner_type', $criteria['owner_type']);
                }
                if (! empty($criteria['owner_id'])) {
                    $ownership->where('owner_id', $criteria['owner_id']);
                }
            });
        }
    }

    private function applyOwnershipScope(Builder $query, string $scope): void
    {
        if ($scope === 'fleet') {
            $query->whereHas('currentOwnership', fn (Builder $ownership): Builder => $ownership
                ->where(function (Builder $fleet): void {
                    $fleet->where('ownership_type', VehicleOwnershipType::Leased->value)
                        ->orWhere(function (Builder $company): void {
                            $company->whereIn('ownership_type', [
                                VehicleOwnershipType::Owned->value,
                                VehicleOwnershipType::CompanyOwned->value,
                            ])->where('owner_type', VehicleOwnerType::Company->value);
                        });
                }));
        } elseif ($scope === 'customer') {
            $query->whereHas('currentOwnership', fn (Builder $ownership): Builder => $ownership
                ->where(function (Builder $customer): void {
                    $customer->where('owner_type', VehicleOwnerType::Customer->value)
                        ->orWhere('ownership_type', VehicleOwnershipType::CustomerOwned->value);
                }));
        } elseif ($scope === 'supplier_owner') {
            $query->whereHas('currentOwnership', fn (Builder $ownership): Builder => $ownership
                ->where(function (Builder $supplier): void {
                    $supplier->whereIn('owner_type', [
                        VehicleOwnerType::Supplier->value,
                        VehicleOwnerType::ThirdParty->value,
                    ])->orWhereIn('ownership_type', [
                        VehicleOwnershipType::Rented->value,
                        VehicleOwnershipType::ThirdParty->value,
                    ]);
                }));
        }
    }
}
