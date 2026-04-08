<?php

declare(strict_types=1);

namespace Modules\Warehouse\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Support\Collection;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Warehouse\Domain\RepositoryInterfaces\LocationRepositoryInterface;
use Modules\Warehouse\Infrastructure\Persistence\Eloquent\Models\LocationModel;

final class EloquentLocationRepository extends EloquentRepository implements LocationRepositoryInterface
{
    public function __construct(LocationModel $model)
    {
        parent::__construct($model);
    }

    public function findByCode(string $code, int $tenantId): mixed
    {
        return $this->model->newQuery()
            ->withoutGlobalScope('tenant')
            ->where('code', $code)
            ->where('tenant_id', $tenantId)
            ->first();
    }

    public function findByWarehouse(int $warehouseId): Collection
    {
        return $this->model->newQuery()
            ->withoutGlobalScope('tenant')
            ->where('warehouse_id', $warehouseId)
            ->orderBy('path')
            ->orderBy('name')
            ->get();
    }

    public function getLocationTree(int $warehouseId): Collection
    {
        $all = $this->model->newQuery()
            ->withoutGlobalScope('tenant')
            ->where('warehouse_id', $warehouseId)
            ->orderBy('level')
            ->orderBy('name')
            ->get();

        return $this->buildTree($all, null);
    }

    public function findChildren(int $parentId): Collection
    {
        return $this->model->newQuery()
            ->withoutGlobalScope('tenant')
            ->where('parent_id', $parentId)
            ->orderBy('name')
            ->get();
    }

    private function buildTree(Collection $all, ?int $parentId): Collection
    {
        return $all->where('parent_id', $parentId)->map(function ($location) use ($all) {
            $location->children_tree = $this->buildTree($all, $location->id);

            return $location;
        })->values();
    }
}
