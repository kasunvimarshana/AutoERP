<?php

namespace Modules\Dispatch\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Dispatch\Domain\Entities\Dispatch;
use Modules\Dispatch\Domain\RepositoryInterfaces\DispatchRepositoryInterface;
use Modules\Dispatch\Infrastructure\Persistence\Eloquent\Models\DispatchModel;

class EloquentDispatchRepository extends EloquentRepository implements DispatchRepositoryInterface
{
    public function __construct(DispatchModel $model)
    {
        parent::__construct($model);
    }

    public function findById(int $id): ?Dispatch
    {
        $model = parent::findById($id);
        return $model ? $this->toEntity($model) : null;
    }

    public function findByDispatchNumber(int $tenantId, string $dispatchNumber): ?Dispatch
    {
        $model = $this->model->where('tenant_id', $tenantId)
            ->where('dispatch_number', $dispatchNumber)
            ->first();
        return $model ? $this->toEntity($model) : null;
    }

    public function findAll(int $tenantId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->where('tenant_id', $tenantId);
        $this->applyFilters($query, $filters);
        return $query->paginate($perPage);
    }

    public function create(array $data): Dispatch
    {
        $model = parent::create($data);
        return $this->toEntity($model);
    }

    public function update(Dispatch $dispatch, array $data): Dispatch
    {
        $model = $this->model->findOrFail($dispatch->id);
        $updated = parent::update($model, $data);
        return $this->toEntity($updated);
    }

    public function save(Dispatch $dispatch): Dispatch
    {
        $model = $this->model->findOrFail($dispatch->id);
        $updated = parent::update($model, [
            'status'        => $dispatch->status,
            'dispatched_at' => $dispatch->dispatchedAt,
            'dispatched_by' => $dispatch->dispatchedBy,
            'delivered_at'  => $dispatch->deliveredAt,
        ]);
        return $this->toEntity($updated);
    }

    private function toEntity(object $model): Dispatch
    {
        return new Dispatch(
            id: $model->id,
            tenantId: $model->tenant_id,
            salesOrderId: $model->sales_order_id,
            warehouseId: $model->warehouse_id,
            dispatchNumber: $model->dispatch_number,
            status: $model->status,
            trackingNumber: $model->tracking_number,
            carrier: $model->carrier,
            shippingAddress: $model->shipping_address,
            notes: $model->notes,
            dispatchedAt: $model->dispatched_at ? new \DateTimeImmutable((string) $model->dispatched_at) : null,
            deliveredAt: $model->delivered_at ? new \DateTimeImmutable((string) $model->delivered_at) : null,
            dispatchedBy: $model->dispatched_by,
        );
    }
}
