<?php

namespace App\Repositories;

use App\Models\Warehouse;
use App\Repositories\Interfaces\WarehouseRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class WarehouseRepository implements WarehouseRepositoryInterface
{
    public function allForTenant(int $tenantId, int $perPage = 15): LengthAwarePaginator
    {
        return Warehouse::where('tenant_id', $tenantId)
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function findById(int $id, int $tenantId): ?Warehouse
    {
        return Warehouse::where('id', $id)
            ->where('tenant_id', $tenantId)
            ->first();
    }

    public function findByCode(string $code, int $tenantId): ?Warehouse
    {
        return Warehouse::where('code', $code)
            ->where('tenant_id', $tenantId)
            ->first();
    }

    public function create(array $data): Warehouse
    {
        return Warehouse::create($data);
    }

    public function update(Warehouse $warehouse, array $data): Warehouse
    {
        $warehouse->update($data);

        return $warehouse->fresh();
    }

    public function delete(Warehouse $warehouse): bool
    {
        return (bool) $warehouse->delete();
    }

    public function getActiveForTenant(int $tenantId): Collection
    {
        return Warehouse::forTenant($tenantId)
            ->active()
            ->orderBy('name')
            ->get();
    }
}
