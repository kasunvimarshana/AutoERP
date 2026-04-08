<?php

declare(strict_types=1);

namespace Modules\Order\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Order\Domain\Entities\SalesOrder;
use Modules\Order\Domain\RepositoryInterfaces\SalesOrderRepositoryInterface;
use Modules\Order\Infrastructure\Persistence\Eloquent\Models\SalesOrderModel;

class EloquentSalesOrderRepository implements SalesOrderRepositoryInterface
{
    public function findById(string $tenantId, string $id): ?SalesOrder
    {
        $model = SalesOrderModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->find($id);

        return $model !== null ? $this->mapToEntity($model) : null;
    }

    public function findAll(string $tenantId): array
    {
        return SalesOrderModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->get()
            ->map(fn(SalesOrderModel $m) => $this->mapToEntity($m))
            ->all();
    }

    public function findByCustomer(string $tenantId, string $customerId): array
    {
        return SalesOrderModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('customer_id', $customerId)
            ->get()
            ->map(fn(SalesOrderModel $m) => $this->mapToEntity($m))
            ->all();
    }

    public function findByStatus(string $tenantId, string $status): array
    {
        return SalesOrderModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('status', $status)
            ->get()
            ->map(fn(SalesOrderModel $m) => $this->mapToEntity($m))
            ->all();
    }

    public function findByReference(string $tenantId, string $reference): ?SalesOrder
    {
        $model = SalesOrderModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('reference', $reference)
            ->first();

        return $model !== null ? $this->mapToEntity($model) : null;
    }

    public function save(SalesOrder $salesOrder): void
    {
        /** @var SalesOrderModel $model */
        $model = SalesOrderModel::withoutGlobalScopes()->findOrNew($salesOrder->id);
        $model->fill([
            'tenant_id'     => $salesOrder->tenantId,
            'customer_id'   => $salesOrder->customerId,
            'warehouse_id'  => $salesOrder->warehouseId,
            'reference'     => $salesOrder->reference,
            'status'        => $salesOrder->status,
            'order_date'    => $salesOrder->orderDate,
            'expected_date' => $salesOrder->expectedDate,
            'notes'         => $salesOrder->notes,
            'total_amount'  => $salesOrder->totalAmount,
        ]);
        if (!$model->exists) {
            $model->id = $salesOrder->id;
        }
        $model->save();
    }

    public function delete(string $tenantId, string $id): void
    {
        SalesOrderModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->find($id)?->delete();
    }

    private function mapToEntity(SalesOrderModel $model): SalesOrder
    {
        return new SalesOrder(
            id: (string) $model->id,
            tenantId: (string) $model->tenant_id,
            customerId: (string) $model->customer_id,
            warehouseId: (string) $model->warehouse_id,
            reference: (string) $model->reference,
            status: (string) $model->status,
            orderDate: new \DateTimeImmutable($model->order_date->toDateTimeString()),
            expectedDate: $model->expected_date !== null
                ? new \DateTimeImmutable($model->expected_date->toDateTimeString())
                : null,
            notes: $model->notes !== null ? (string) $model->notes : null,
            totalAmount: (float) $model->total_amount,
            createdAt: $model->created_at ?? now(),
            updatedAt: $model->updated_at ?? now(),
        );
    }
}
