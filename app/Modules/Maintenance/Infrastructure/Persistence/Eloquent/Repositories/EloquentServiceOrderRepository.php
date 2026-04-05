<?php
declare(strict_types=1);
namespace Modules\Maintenance\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Maintenance\Domain\Entities\ServiceOrder;
use Modules\Maintenance\Domain\RepositoryInterfaces\ServiceOrderRepositoryInterface;
use Modules\Maintenance\Infrastructure\Persistence\Eloquent\Models\ServiceOrderModel;

class EloquentServiceOrderRepository implements ServiceOrderRepositoryInterface
{
    public function __construct(private readonly ServiceOrderModel $model) {}

    public function findById(int $id): ?ServiceOrder
    {
        $m = $this->model->newQuery()->find($id);
        return $m ? $this->toEntity($m) : null;
    }

    public function findByNumber(int $tenantId, string $number): ?ServiceOrder
    {
        $m = $this->model->newQuery()->where('tenant_id', $tenantId)->where('order_number', $number)->first();
        return $m ? $this->toEntity($m) : null;
    }

    public function findAllByTenant(int $tenantId, array $filters = []): array
    {
        $q = $this->model->newQuery()->where('tenant_id', $tenantId);
        if (!empty($filters['status']))   $q->where('status', $filters['status']);
        if (!empty($filters['type']))     $q->where('type', $filters['type']);
        if (!empty($filters['priority'])) $q->where('priority', $filters['priority']);
        return $q->orderByDesc('created_at')->get()->map(fn($m) => $this->toEntity($m))->all();
    }

    public function create(array $data): ServiceOrder
    {
        return $this->toEntity($this->model->newQuery()->create($data));
    }

    public function update(int $id, array $data): ?ServiceOrder
    {
        $m = $this->model->newQuery()->find($id);
        if (!$m) return null;
        $m->update($data);
        return $this->toEntity($m->fresh());
    }

    public function delete(int $id): bool
    {
        return (bool) $this->model->newQuery()->find($id)?->delete();
    }

    private function toEntity(ServiceOrderModel $m): ServiceOrder
    {
        return new ServiceOrder(
            $m->id, $m->tenant_id, $m->order_number,
            $m->type, $m->status, $m->priority, $m->title, $m->description,
            $m->asset_id, $m->warehouse_id, $m->assigned_to, $m->customer_id,
            (float) $m->estimated_hours, (float) $m->actual_hours,
            (float) $m->labor_cost, (float) $m->parts_cost,
            $m->scheduled_at, $m->started_at, $m->completed_at,
            $m->created_at, $m->updated_at,
        );
    }
}
