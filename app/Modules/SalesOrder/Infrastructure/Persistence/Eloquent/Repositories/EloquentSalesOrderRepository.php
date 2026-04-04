<?php

namespace Modules\SalesOrder\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\SalesOrder\Domain\Entities\SalesOrder;
use Modules\SalesOrder\Domain\RepositoryInterfaces\SalesOrderRepositoryInterface;
use Modules\SalesOrder\Infrastructure\Persistence\Eloquent\Models\SalesOrderModel;

class EloquentSalesOrderRepository extends EloquentRepository implements SalesOrderRepositoryInterface
{
    public function __construct(SalesOrderModel $model)
    {
        parent::__construct($model);
    }

    public function findById(int $id): ?SalesOrder
    {
        $model = parent::findById($id);
        return $model ? $this->toEntity($model) : null;
    }

    public function findBySoNumber(int $tenantId, string $soNumber): ?SalesOrder
    {
        $model = $this->model->where('tenant_id', $tenantId)->where('so_number', $soNumber)->first();
        return $model ? $this->toEntity($model) : null;
    }

    public function findAll(int $tenantId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->where('tenant_id', $tenantId);
        $this->applyFilters($query, $filters);
        return $query->paginate($perPage);
    }

    public function create(array $data): SalesOrder
    {
        $model = parent::create($data);
        return $this->toEntity($model);
    }

    public function update(SalesOrder $so, array $data): SalesOrder
    {
        $model = $this->model->findOrFail($so->id);
        $updated = parent::update($model, $data);
        return $this->toEntity($updated);
    }

    public function save(SalesOrder $so): SalesOrder
    {
        $model = $this->model->findOrFail($so->id);
        $updated = parent::update($model, [
            'status'    => $so->status,
            'picked_by' => $so->pickedBy,
            'picked_at' => $so->pickedAt,
            'packed_by' => $so->packedBy,
            'packed_at' => $so->packedAt,
        ]);
        return $this->toEntity($updated);
    }

    private function toEntity(object $model): SalesOrder
    {
        return new SalesOrder(
            id: $model->id,
            tenantId: $model->tenant_id,
            warehouseId: $model->warehouse_id,
            customerId: $model->customer_id,
            soNumber: $model->so_number,
            status: $model->status,
            totalAmount: $model->total_amount !== null ? (float) $model->total_amount : null,
            taxAmount: $model->tax_amount !== null ? (float) $model->tax_amount : null,
            discountAmount: $model->discount_amount !== null ? (float) $model->discount_amount : null,
            currency: $model->currency,
            shippingAddress: $model->shipping_address,
            notes: $model->notes,
            expectedDeliveryDate: $model->expected_delivery_date
                ? new \DateTimeImmutable((string) $model->expected_delivery_date)
                : null,
            pickedBy: $model->picked_by,
            pickedAt: $model->picked_at ? new \DateTimeImmutable((string) $model->picked_at) : null,
            packedBy: $model->packed_by,
            packedAt: $model->packed_at ? new \DateTimeImmutable((string) $model->packed_at) : null,
        );
    }
}
