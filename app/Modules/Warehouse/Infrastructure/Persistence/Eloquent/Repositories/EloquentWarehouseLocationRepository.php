<?php

declare(strict_types=1);

namespace Modules\Warehouse\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Warehouse\Domain\Entities\WarehouseLocation;
use Modules\Warehouse\Domain\RepositoryInterfaces\WarehouseLocationRepositoryInterface;
use Modules\Warehouse\Infrastructure\Persistence\Eloquent\Models\WarehouseLocationModel;

class EloquentWarehouseLocationRepository implements WarehouseLocationRepositoryInterface
{
    public function findById(string $tenantId, string $id): ?WarehouseLocation
    {
        $model = WarehouseLocationModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->find($id);
        return $model !== null ? $this->mapToEntity($model) : null;
    }

    public function findAll(string $tenantId): array
    {
        return WarehouseLocationModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->get()
            ->map(fn(WarehouseLocationModel $m) => $this->mapToEntity($m))
            ->all();
    }

    public function findByWarehouse(string $tenantId, string $warehouseId): array
    {
        return WarehouseLocationModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('warehouse_id', $warehouseId)
            ->get()
            ->map(fn(WarehouseLocationModel $m) => $this->mapToEntity($m))
            ->all();
    }

    public function findChildren(string $tenantId, string $parentId): array
    {
        return WarehouseLocationModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('parent_id', $parentId)
            ->get()
            ->map(fn(WarehouseLocationModel $m) => $this->mapToEntity($m))
            ->all();
    }

    public function save(WarehouseLocation $location): void
    {
        $model = WarehouseLocationModel::withoutGlobalScopes()->findOrNew($location->id);
        $model->fill([
            'tenant_id'    => $location->tenantId,
            'warehouse_id' => $location->warehouseId,
            'parent_id'    => $location->parentId,
            'name'         => $location->name,
            'code'         => $location->code,
            'path'         => $location->path,
            'level'        => $location->level,
            'type'         => $location->type,
            'is_active'    => $location->isActive,
        ]);
        if (!$model->exists) {
            $model->id = $location->id;
        }
        $model->save();
    }

    public function delete(string $tenantId, string $id): void
    {
        WarehouseLocationModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->find($id)?->delete();
    }

    private function mapToEntity(WarehouseLocationModel $model): WarehouseLocation
    {
        return new WarehouseLocation(
            id: $model->id,
            tenantId: $model->tenant_id,
            warehouseId: $model->warehouse_id,
            parentId: $model->parent_id,
            name: $model->name,
            code: $model->code,
            path: $model->path,
            level: (int) $model->level,
            type: $model->type,
            isActive: (bool) $model->is_active,
            createdAt: $model->created_at,
            updatedAt: $model->updated_at,
        );
    }
}
