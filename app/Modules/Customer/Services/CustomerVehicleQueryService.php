<?php

declare(strict_types=1);

namespace Modules\Customer\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Modules\Customer\Models\CustomerVehicle;

final class CustomerVehicleQueryService
{
    public function paginate(array $criteria, int $tenantId, ?int $organizationUnitId, int $perPage): LengthAwarePaginator
    {
        $query = $this->scope($tenantId, $organizationUnitId)->with(['customer', 'vehicle.make', 'vehicle.model', 'organizationUnit']);
        if (($search = trim((string) ($criteria['search'] ?? ''))) !== '') {
            $query->where(fn (Builder $q): Builder => $q->whereHas('customer', fn (Builder $p): Builder => $p->withTrashed()->where(fn (Builder $s): Builder => $s->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%")->orWhere('customer_number', 'like', "%{$search}%")))->orWhereHas('vehicle', fn (Builder $v): Builder => $v->withTrashed()->where(fn (Builder $s): Builder => $s->where('registration_number', 'like', "%{$search}%")->orWhere('vehicle_number', 'like', "%{$search}%")->orWhere('chassis_number', 'like', "%{$search}%"))));
        }
        foreach (['customer_id', 'vehicle_id', 'is_current'] as $field) {
            if (array_key_exists($field, $criteria) && $criteria[$field] !== null && $criteria[$field] !== '') {
                $query->where($field, $criteria[$field]);
            }
        }
        if (($criteria['status'] ?? null) === 'active') {
            $query->whereNull('ended_at');
        }
        if (($criteria['status'] ?? null) === 'ended') {
            $query->whereNotNull('ended_at');
        }
        $sort = in_array($criteria['sort'] ?? null, ['started_at', 'ended_at', 'created_at'], true) ? $criteria['sort'] : 'started_at';

        return $query->orderBy($sort, ($criteria['direction'] ?? null) === 'asc' ? 'asc' : 'desc')->orderByDesc('id')->paginate($perPage);
    }

    public function find(int $id, int $tenantId, ?int $organizationUnitId): CustomerVehicle
    {
        return $this->scope($tenantId, $organizationUnitId)->with(['customer', 'vehicle.make', 'vehicle.model', 'organizationUnit'])->findOrFail($id);
    }

    private function scope(int $tenantId, ?int $organizationUnitId): Builder
    {
        return CustomerVehicle::query()->where('tenant_id', $tenantId)->where(fn (Builder $q): Builder => $organizationUnitId === null ? $q->whereNull('organization_unit_id') : $q->whereNull('organization_unit_id')->orWhere('organization_unit_id', $organizationUnitId));
    }
}
