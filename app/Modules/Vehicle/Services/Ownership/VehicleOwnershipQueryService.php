<?php

declare(strict_types=1);

namespace Modules\Vehicle\Services\Ownership;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Modules\Vehicle\Enums\VehicleOwnerType;
use Modules\Vehicle\Models\VehicleOwnership;

final class VehicleOwnershipQueryService
{
    public function paginate(array $criteria, int $tenantId, ?int $organizationUnitId, int $perPage): LengthAwarePaginator
    {
        $query = $this->scope($tenantId, $organizationUnitId)->with(['vehicle.make', 'vehicle.model', 'organizationUnit']);

        if (($search = trim((string) ($criteria['search'] ?? ''))) !== '') {
            $query->where(function (Builder $builder) use ($search): void {
                $builder
                    ->where('owner_code_snapshot', 'like', "%{$search}%")
                    ->orWhere('owner_name_snapshot', 'like', "%{$search}%")
                    ->orWhereHas('vehicle', function (Builder $vehicle) use ($search): void {
                        $vehicle->withTrashed()->where(function (Builder $value) use ($search): void {
                            $value->where('vehicle_number', 'like', "%{$search}%")
                                ->orWhere('registration_number', 'like', "%{$search}%")
                                ->orWhere('chassis_number', 'like', "%{$search}%");
                        });
                    });
            });
        }

        foreach (['vehicle_id', 'owner_id', 'is_current'] as $field) {
            if (array_key_exists($field, $criteria) && $criteria[$field] !== null && $criteria[$field] !== '') {
                $query->where($field, $criteria[$field]);
            }
        }

        if (($ownerType = $criteria['owner_type'] ?? null) !== null && $ownerType !== '') {
            $query->where('owner_type', VehicleOwnerType::from((string) $ownerType)->value);
        }

        if (($criteria['status'] ?? null) === 'active') {
            $query->whereNull('ended_at');
        } elseif (($criteria['status'] ?? null) === 'ended') {
            $query->whereNotNull('ended_at');
        }

        $sort = in_array($criteria['sort'] ?? null, ['started_at', 'ended_at', 'created_at'], true)
            ? (string) $criteria['sort']
            : 'started_at';

        return $query
            ->orderByDesc('is_current')
            ->orderBy($sort, ($criteria['direction'] ?? null) === 'asc' ? 'asc' : 'desc')
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function find(int $id, int $tenantId, ?int $organizationUnitId): VehicleOwnership
    {
        return $this->scope($tenantId, $organizationUnitId)
            ->with(['vehicle.make', 'vehicle.model', 'organizationUnit'])
            ->findOrFail($id);
    }

    private function scope(int $tenantId, ?int $organizationUnitId): Builder
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
