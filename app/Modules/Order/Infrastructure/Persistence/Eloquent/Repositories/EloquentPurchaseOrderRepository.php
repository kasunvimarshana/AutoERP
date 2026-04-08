<?php

declare(strict_types=1);

namespace Modules\Order\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Order\Domain\Entities\PurchaseOrder;
use Modules\Order\Domain\RepositoryInterfaces\PurchaseOrderRepositoryInterface;
use Modules\Order\Infrastructure\Persistence\Eloquent\Models\PurchaseOrderModel;

class EloquentPurchaseOrderRepository implements PurchaseOrderRepositoryInterface
{
    public function findById(string $tenantId, string $id): ?PurchaseOrder
    {
        $model = PurchaseOrderModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->find($id);

        return $model !== null ? $this->mapToEntity($model) : null;
    }

    public function findAll(string $tenantId): array
    {
        return PurchaseOrderModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->get()
            ->map(fn(PurchaseOrderModel $m) => $this->mapToEntity($m))
            ->all();
    }

    public function findBySupplier(string $tenantId, string $supplierId): array
    {
        return PurchaseOrderModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('supplier_id', $supplierId)
            ->get()
            ->map(fn(PurchaseOrderModel $m) => $this->mapToEntity($m))
            ->all();
    }

    public function findByStatus(string $tenantId, string $status): array
    {
        return PurchaseOrderModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('status', $status)
            ->get()
            ->map(fn(PurchaseOrderModel $m) => $this->mapToEntity($m))
            ->all();
    }

    public function findByReference(string $tenantId, string $reference): ?PurchaseOrder
    {
        $model = PurchaseOrderModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('reference', $reference)
            ->first();

        return $model !== null ? $this->mapToEntity($model) : null;
    }

    public function save(PurchaseOrder $purchaseOrder): void
    {
        /** @var PurchaseOrderModel $model */
        $model = PurchaseOrderModel::withoutGlobalScopes()->findOrNew($purchaseOrder->id);
        $model->fill([
            'tenant_id'     => $purchaseOrder->tenantId,
            'supplier_id'   => $purchaseOrder->supplierId,
            'warehouse_id'  => $purchaseOrder->warehouseId,
            'reference'     => $purchaseOrder->reference,
            'status'        => $purchaseOrder->status,
            'order_date'    => $purchaseOrder->orderDate,
            'expected_date' => $purchaseOrder->expectedDate,
            'notes'         => $purchaseOrder->notes,
            'total_amount'  => $purchaseOrder->totalAmount,
        ]);
        if (!$model->exists) {
            $model->id = $purchaseOrder->id;
        }
        $model->save();
    }

    public function delete(string $tenantId, string $id): void
    {
        PurchaseOrderModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->find($id)?->delete();
    }

    private function mapToEntity(PurchaseOrderModel $model): PurchaseOrder
    {
        return new PurchaseOrder(
            id: (string) $model->id,
            tenantId: (string) $model->tenant_id,
            supplierId: (string) $model->supplier_id,
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
