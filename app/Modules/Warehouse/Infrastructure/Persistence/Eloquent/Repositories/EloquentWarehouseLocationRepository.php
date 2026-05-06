<?php

declare(strict_types=1);

namespace Modules\Warehouse\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Warehouse\Domain\Entities\WarehouseLocation;
use Modules\Warehouse\Domain\RepositoryInterfaces\WarehouseLocationRepositoryInterface;
use Modules\Warehouse\Infrastructure\Persistence\Eloquent\Models\WarehouseLocationModel;

class EloquentWarehouseLocationRepository extends EloquentRepository implements WarehouseLocationRepositoryInterface
{
    private readonly WarehouseLocationModel $warehouseLocationModel;

    public function __construct(WarehouseLocationModel $model)
    {
        $this->warehouseLocationModel = $model;
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (WarehouseLocationModel $model): WarehouseLocation => $this->mapModelToEntity($model));
    }

    public function save(WarehouseLocation $location): WarehouseLocation
    {
        $data = [
            'tenant_id' => $location->getTenantId(),
            'warehouse_id' => $location->getWarehouseId(),
            'parent_id' => $location->getParentId(),
            'name' => $location->getName(),
            'code' => $location->getCode(),
            'path' => $location->getPath(),
            'depth' => $location->getDepth(),
            'type' => $location->getType(),
            'is_active' => $location->isActive(),
            'is_pickable' => $location->isPickable(),
            'is_receivable' => $location->isReceivable(),
            'capacity' => $location->getCapacity(),
            'metadata' => $location->getMetadata(),
        ];

        if ($location->getId() !== null) {
            $model = $this->update($location->getId(), $data);
        } else {
            $model = $this->create($data);
        }

        /** @var WarehouseLocationModel $model */

        return $this->toDomainEntity($model);
    }

    public function findByTenantWarehouseAndCode(int $tenantId, int $warehouseId, string $code): ?WarehouseLocation
    {
        /** @var WarehouseLocationModel|null $model */
        $model = $this->warehouseLocationModel->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('warehouse_id', $warehouseId)
            ->where('code', $code)
            ->first();

        return $model !== null ? $this->toDomainEntity($model) : null;
    }

    public function listByWarehouse(int $tenantId, int $warehouseId): array
    {
        /** @var Collection<int, WarehouseLocation> $locations */
        $locations = $this->warehouseLocationModel->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('warehouse_id', $warehouseId)
            ->orderBy('path')
            ->get()
            ->map(fn (WarehouseLocationModel $model): WarehouseLocation => $this->toDomainEntity($model));

        return $locations->all();
    }

    public function updateDescendantPaths(int $tenantId, int $warehouseId, string $oldPrefix, string $newPrefix): void
    {
        $descendants = $this->warehouseLocationModel->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('warehouse_id', $warehouseId)
            ->where('path', 'like', $oldPrefix.'/%')
            ->get();

        foreach ($descendants as $descendant) {
            $path = (string) $descendant->path;
            $updatedPath = preg_replace('/^'.preg_quote($oldPrefix, '/').'\//', $newPrefix.'/', $path, 1);

            if (! is_string($updatedPath)) {
                continue;
            }

            $depth = substr_count($updatedPath, '/');

            DB::table($this->warehouseLocationModel->getTable())
                ->where('id', $descendant->id)
                ->update([
                    'path' => $updatedPath,
                    'depth' => $depth,
                    'updated_at' => now(),
                ]);
        }
    }

    private function mapModelToEntity(WarehouseLocationModel $model): WarehouseLocation
    {
        return new WarehouseLocation(
            tenantId: (int) $model->tenant_id,
            warehouseId: (int) $model->warehouse_id,
            parentId: $model->parent_id !== null ? (int) $model->parent_id : null,
            name: (string) $model->name,
            code: $model->code,
            path: $model->path,
            depth: (int) $model->depth,
            type: (string) $model->type,
            isActive: (bool) $model->is_active,
            isPickable: (bool) $model->is_pickable,
            isReceivable: (bool) $model->is_receivable,
            capacity: $model->capacity !== null ? (string) $model->capacity : null,
            metadata: is_array($model->metadata) ? $model->metadata : null,
            id: (int) $model->id,
            createdAt: $model->created_at,
            updatedAt: $model->updated_at,
        );
    }
}
