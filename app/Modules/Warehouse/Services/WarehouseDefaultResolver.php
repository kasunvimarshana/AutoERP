<?php

declare(strict_types=1);

namespace Modules\Warehouse\Services;

use Illuminate\Database\Eloquent\Builder;
use Modules\Warehouse\Models\WarehouseLocationModel;
use Modules\Warehouse\Models\WarehouseModel;

final class WarehouseDefaultResolver
{
    public function resolveDefaultWarehouse(int $tenantId, ?int $organizationUnitId): ?WarehouseModel
    {
        return WarehouseModel::query()
            ->inExactScope($tenantId, $organizationUnitId)
            ->where('is_active', true)
            ->where('is_default', true)
            ->orderBy('name')
            ->first();
    }

    public function resolveDefaultLocation(WarehouseModel|int $warehouse): ?WarehouseLocationModel
    {
        $warehouseId = $warehouse instanceof WarehouseModel ? (int) $warehouse->getKey() : $warehouse;
        if ($warehouseId < 1) {
            return null;
        }

        return WarehouseLocationModel::query()
            ->where('warehouse_id', $warehouseId)
            ->where('is_active', true)
            ->where('is_default', true)
            ->orderBy('path')
            ->orderBy('name')
            ->first();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function activeWarehouseOptions(int $tenantId, ?int $organizationUnitId, string $search, int $limit): array
    {
        return WarehouseModel::query()
            ->forTenant($tenantId, $organizationUnitId)
            ->where('is_active', true)
            ->when($search !== '', fn (Builder $query) => $query->where(function (Builder $scope) use ($search): void {
                $scope->where('code', 'like', '%'.$search.'%')
                    ->orWhere('name', 'like', '%'.$search.'%');
            }))
            ->orderByDesc('is_default')
            ->orderBy('code')
            ->orderBy('name')
            ->limit(max(1, $limit))
            ->get(['id', 'code', 'name', 'is_default'])
            ->map(fn (WarehouseModel $warehouse): array => [
                'id' => (int) $warehouse->getKey(),
                'code' => $warehouse->code,
                'name' => $warehouse->name,
                'is_default' => (bool) $warehouse->is_default,
            ])
            ->all();
    }
}
