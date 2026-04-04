<?php
namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Inventory\Domain\Entities\InventoryCycleCount;
use Modules\Inventory\Domain\RepositoryInterfaces\InventoryCycleCountRepositoryInterface;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\InventoryCycleCountModel;

class EloquentInventoryCycleCountRepository extends EloquentRepository implements InventoryCycleCountRepositoryInterface
{
    public function __construct(InventoryCycleCountModel $model)
    {
        parent::__construct($model);
    }

    public function findById(int $id): ?InventoryCycleCount
    {
        $model = parent::findById($id);
        return $model ? $this->toEntity($model) : null;
    }

    public function findAll(int $tenantId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->where('tenant_id', $tenantId);
        $this->applyFilters($query, $filters);
        return $query->paginate($perPage);
    }

    public function create(array $data): InventoryCycleCount
    {
        $model = parent::create($data);
        return $this->toEntity($model);
    }

    public function update(InventoryCycleCount $count, array $data): InventoryCycleCount
    {
        $model = $this->model->findOrFail($count->id);
        $updated = parent::update($model, $data);
        return $this->toEntity($updated);
    }

    public function save(InventoryCycleCount $count): InventoryCycleCount
    {
        $model = $this->model->findOrFail($count->id);
        $updated = parent::update($model, [
            'status'       => $count->status,
            'completed_at' => $count->completedAt,
            'scheduled_at' => $count->scheduledAt,
            'assigned_to'  => $count->assignedTo,
            'reference'    => $count->reference,
        ]);
        return $this->toEntity($updated);
    }

    private function toEntity(object $model): InventoryCycleCount
    {
        return new InventoryCycleCount(
            id: $model->id,
            tenantId: $model->tenant_id,
            warehouseId: $model->warehouse_id,
            method: $model->method,
            status: $model->status,
            reference: $model->reference,
            assignedTo: $model->assigned_to,
            scheduledAt: $model->scheduled_at ? new \DateTimeImmutable($model->scheduled_at) : null,
            completedAt: $model->completed_at ? new \DateTimeImmutable($model->completed_at) : null,
        );
    }
}
