<?php

declare(strict_types=1);

namespace Modules\Vehicle\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Modules\Vehicle\Enums\VehicleStatus;
use Modules\Vehicle\Models\Vehicle;
use Modules\Vehicle\Enums\VehicleOwnerType;

final class VehicleQueryService
{
    public function paginate(array $criteria, int $tenantId, ?int $organizationUnitId, int $perPage): LengthAwarePaginator
    {
        $query = $this->baseQuery($tenantId, $organizationUnitId)
            ->with(['make', 'model', 'type', 'category', 'currentOwnerships']);
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
            ->with([
                'make',
                'model',
                'type',
                'category',
                'documents',
                'attributes',
                'ownerships',
                'currentOwnerships',
                'statusHistories',
            ])
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
                    ->orWhere('vin_number', 'like', "%{$search}%");
            });
        }
        foreach (['status', 'vehicle_make_id', 'vehicle_model_id', 'vehicle_type_id', 'vehicle_category_id'] as $filter) {
            if (array_key_exists($filter, $criteria) && $criteria[$filter] !== null && $criteria[$filter] !== '') {
                $query->where($filter, $criteria[$filter]);
            }
        }
        if (! empty($criteria['customer_id'])) {
            $query->whereCurrentOwner(VehicleOwnerType::Customer->value, (int) $criteria['customer_id']);
        }
        if (($criteria['ownership_scope'] ?? null) === 'customer') {
            $query->whereCurrentOwner(VehicleOwnerType::Customer->value);
        }
        if (($criteria['ownership_scope'] ?? null) === 'supplier') {
            $query->whereCurrentOwner(VehicleOwnerType::Supplier->value);
        }
        if (($criteria['ownership_scope'] ?? null) === 'company') {
            $query->whereCurrentOwner(VehicleOwnerType::Company->value);
        }
        if (! empty($criteria['available_for_service'])) {
            $query->whereIn('status', [VehicleStatus::Active->value, VehicleStatus::UnderService->value]);
        }
        if (! empty($criteria['available_for_rental'])) {
            $query->where('status', VehicleStatus::Active->value);
        }
    }
}
