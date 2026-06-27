<?php

declare(strict_types=1);

namespace Modules\Vehicle\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Modules\Vehicle\Models\VehicleOwnership;

final class VehicleOwnershipQueryService
{
    public function paginate(array $criteria, int $tenantId, ?int $organizationUnitId, int $perPage): LengthAwarePaginator
    {
        $query = $this->base($tenantId, $organizationUnitId)->with(['vehicle.make', 'vehicle.model']);
        foreach (['owner_type', 'owner_id', 'vehicle_id'] as $field) {
            if (($criteria[$field] ?? null) !== null && $criteria[$field] !== '') {
                $query->where($field, $criteria[$field]);
            }
        }
        if (($criteria['is_current'] ?? null) !== null && $criteria['is_current'] !== '') {
            $query->where('is_current', (bool) $criteria['is_current']);
        }
        if (($criteria['status'] ?? null) === 'active') {
            $query->whereNull('ended_at');
        } elseif (($criteria['status'] ?? null) === 'ended') {
            $query->whereNotNull('ended_at');
        }
        $search = trim((string) ($criteria['search'] ?? ''));
        if ($search !== '') {
            $query->where(function (Builder $scope) use ($search): void {
                $scope->where('owner_code_snapshot', 'like', "%{$search}%")
                    ->orWhere('owner_name_snapshot', 'like', "%{$search}%")
                    ->orWhereHas('vehicle', function (Builder $vehicle) use ($search): void {
                        $vehicle->where('vehicle_number', 'like', "%{$search}%")
                            ->orWhere('registration_number', 'like', "%{$search}%")
                            ->orWhere('chassis_number', 'like', "%{$search}%");
                    });
            });
        }
        $sort = in_array(($criteria['sort'] ?? null), ['started_at', 'ended_at', 'created_at'], true)
            ? (string) $criteria['sort'] : 'started_at';
        $direction = ($criteria['direction'] ?? null) === 'asc' ? 'asc' : 'desc';

        return $query->orderBy($sort, $direction)->orderByDesc('id')->paginate($perPage);
    }

    public function find(int $id, int $tenantId, ?int $organizationUnitId): VehicleOwnership
    {
        return $this->base($tenantId, $organizationUnitId)
            ->with(['vehicle.make', 'vehicle.model'])
            ->findOrFail($id);
    }

    private function base(int $tenantId, ?int $organizationUnitId): Builder
    {
        return VehicleOwnership::query()
            ->where('tenant_id', $tenantId)
            ->where(function (Builder $query) use ($organizationUnitId): void {
                $query->whereNull('organization_unit_id');
                if ($organizationUnitId !== null) {
                    $query->orWhere('organization_unit_id', $organizationUnitId);
                }
            });
    }
}
