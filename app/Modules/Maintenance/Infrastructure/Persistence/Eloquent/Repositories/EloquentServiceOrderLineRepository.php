<?php
declare(strict_types=1);
namespace Modules\Maintenance\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Maintenance\Domain\Entities\ServiceOrderLine;
use Modules\Maintenance\Domain\RepositoryInterfaces\ServiceOrderLineRepositoryInterface;
use Modules\Maintenance\Infrastructure\Persistence\Eloquent\Models\ServiceOrderLineModel;

class EloquentServiceOrderLineRepository implements ServiceOrderLineRepositoryInterface
{
    public function __construct(private readonly ServiceOrderLineModel $model) {}

    public function findByServiceOrder(int $serviceOrderId): array
    {
        return $this->model->newQuery()->where('service_order_id', $serviceOrderId)
            ->get()->map(fn($m) => $this->toEntity($m))->all();
    }

    public function create(array $data): ServiceOrderLine
    {
        return $this->toEntity($this->model->newQuery()->create($data));
    }

    public function delete(int $id): bool
    {
        return (bool) $this->model->newQuery()->find($id)?->delete();
    }

    private function toEntity(ServiceOrderLineModel $m): ServiceOrderLine
    {
        return new ServiceOrderLine(
            $m->id, $m->service_order_id, $m->description, $m->product_id,
            (float) $m->quantity, (float) $m->unit_cost, (float) $m->total_cost, $m->created_at,
        );
    }
}
