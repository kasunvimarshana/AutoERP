<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Inventory\Domain\Entities\InventoryCycleCount;
use Modules\Inventory\Domain\RepositoryInterfaces\InventoryCycleCountRepositoryInterface;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\InventoryCycleCountModel;

class EloquentInventoryCycleCountRepository implements InventoryCycleCountRepositoryInterface
{
    public function __construct(private readonly InventoryCycleCountModel $model) {}

    private function toEntity(InventoryCycleCountModel $m): InventoryCycleCount
    {
        return new InventoryCycleCount($m->id, $m->tenant_id, $m->warehouse_id, $m->product_id,
            $m->status, $m->counted_by, $m->started_at, $m->completed_at, $m->notes,
            $m->created_at, $m->updated_at);
    }

    public function findById(int $id): ?InventoryCycleCount
    {
        $m = $this->model->newQuery()->find($id);
        return $m ? $this->toEntity($m) : null;
    }

    public function findByWarehouse(int $tenantId, int $warehouseId, int $perPage = 15, int $page = 1): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('warehouse_id', $warehouseId)
            ->paginate($perPage, ['*'], 'page', $page);
    }

    public function create(array $data): InventoryCycleCount
    {
        $m = $this->model->newQuery()->create($data);
        return $this->toEntity($m);
    }

    public function update(int $id, array $data): ?InventoryCycleCount
    {
        $m = $this->model->newQuery()->find($id);
        if (!$m) return null;
        $m->update($data);
        return $this->toEntity($m->fresh());
    }

    public function delete(int $id): bool
    {
        $m = $this->model->newQuery()->find($id);
        return $m ? (bool)$m->delete() : false;
    }
}
