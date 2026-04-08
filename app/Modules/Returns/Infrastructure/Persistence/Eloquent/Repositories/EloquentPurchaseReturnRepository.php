<?php

declare(strict_types=1);

namespace Modules\Returns\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Returns\Domain\Entities\PurchaseReturn;
use Modules\Returns\Domain\RepositoryInterfaces\PurchaseReturnRepositoryInterface;
use Modules\Returns\Infrastructure\Persistence\Eloquent\Models\PurchaseReturnModel;

class EloquentPurchaseReturnRepository implements PurchaseReturnRepositoryInterface
{
    public function findById(string $tenantId, string $id): ?PurchaseReturn
    {
        $model = PurchaseReturnModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->find($id);

        return $model !== null ? $this->mapToEntity($model) : null;
    }

    public function findAll(string $tenantId): array
    {
        return PurchaseReturnModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->get()
            ->map(fn (PurchaseReturnModel $model): PurchaseReturn => $this->mapToEntity($model))
            ->all();
    }

    public function findBySupplier(string $tenantId, string $supplierId): array
    {
        return PurchaseReturnModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('supplier_id', $supplierId)
            ->get()
            ->map(fn (PurchaseReturnModel $model): PurchaseReturn => $this->mapToEntity($model))
            ->all();
    }

    public function findByStatus(string $tenantId, string $status): array
    {
        return PurchaseReturnModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('status', $status)
            ->get()
            ->map(fn (PurchaseReturnModel $model): PurchaseReturn => $this->mapToEntity($model))
            ->all();
    }

    public function findByReference(string $tenantId, string $reference): ?PurchaseReturn
    {
        $model = PurchaseReturnModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('reference', $reference)
            ->first();

        return $model !== null ? $this->mapToEntity($model) : null;
    }

    public function save(PurchaseReturn $return): void
    {
        $model = PurchaseReturnModel::withoutGlobalScopes()->findOrNew($return->id);
        $model->fill([
            'tenant_id'          => $return->tenantId,
            'purchase_order_id'  => $return->purchaseOrderId,
            'supplier_id'        => $return->supplierId,
            'warehouse_id'       => $return->warehouseId,
            'reference'          => $return->reference,
            'status'             => $return->status,
            'return_date'        => $return->returnDate->format('Y-m-d'),
            'reason'             => $return->reason,
            'total_amount'       => $return->totalAmount,
            'credit_memo_number' => $return->creditMemoNumber,
            'refund_amount'      => $return->refundAmount,
            'notes'              => $return->notes,
        ]);

        if (! $model->exists) {
            $model->id = $return->id;
        }

        $model->save();
    }

    public function delete(string $tenantId, string $id): void
    {
        PurchaseReturnModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->find($id)?->delete();
    }

    private function mapToEntity(PurchaseReturnModel $model): PurchaseReturn
    {
        return new PurchaseReturn(
            id: (string) $model->id,
            tenantId: (string) $model->tenant_id,
            purchaseOrderId: $model->purchase_order_id !== null ? (string) $model->purchase_order_id : null,
            supplierId: (string) $model->supplier_id,
            warehouseId: (string) $model->warehouse_id,
            reference: (string) $model->reference,
            status: (string) $model->status,
            returnDate: $model->return_date instanceof \DateTimeInterface
                ? $model->return_date
                : new \DateTimeImmutable((string) $model->return_date),
            reason: $model->reason !== null ? (string) $model->reason : null,
            totalAmount: (float) $model->total_amount,
            creditMemoNumber: $model->credit_memo_number !== null ? (string) $model->credit_memo_number : null,
            refundAmount: (float) $model->refund_amount,
            notes: $model->notes !== null ? (string) $model->notes : null,
            createdAt: $model->created_at ?? now(),
            updatedAt: $model->updated_at ?? now(),
        );
    }
}
