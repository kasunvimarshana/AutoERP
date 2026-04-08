<?php

declare(strict_types=1);

namespace Modules\Warehouse\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Warehouse\Domain\Entities\Warehouse;
use Modules\Warehouse\Domain\RepositoryInterfaces\WarehouseRepositoryInterface;
use Modules\Warehouse\Infrastructure\Persistence\Eloquent\Models\WarehouseModel;

class EloquentWarehouseRepository implements WarehouseRepositoryInterface
{
    public function findById(string $tenantId, string $id): ?Warehouse
    {
        $model = WarehouseModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->find($id);
        return $model !== null ? $this->mapToEntity($model) : null;
    }

    public function findAll(string $tenantId): array
    {
        return WarehouseModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->get()
            ->map(fn(WarehouseModel $m) => $this->mapToEntity($m))
            ->all();
    }

    public function findByCode(string $tenantId, string $code): ?Warehouse
    {
        $model = WarehouseModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('code', $code)
            ->first();
        return $model !== null ? $this->mapToEntity($model) : null;
    }

    public function save(Warehouse $warehouse): void
    {
        $model = WarehouseModel::withoutGlobalScopes()->findOrNew($warehouse->id);
        $model->fill([
            'tenant_id' => $warehouse->tenantId,
            'name'      => $warehouse->name,
            'code'      => $warehouse->code,
            'address'   => $warehouse->address,
            'is_active' => $warehouse->isActive,
        ]);
        if (!$model->exists) {
            $model->id = $warehouse->id;
        }
        $model->save();
    }

    public function delete(string $tenantId, string $id): void
    {
        WarehouseModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->find($id)?->delete();
    }

    private function mapToEntity(WarehouseModel $model): Warehouse
    {
        return new Warehouse(
            id: $model->id,
            tenantId: $model->tenant_id,
            name: $model->name,
            code: $model->code,
            address: $model->address,
            isActive: (bool) $model->is_active,
            createdAt: $model->created_at,
            updatedAt: $model->updated_at,
        );
    }
}
