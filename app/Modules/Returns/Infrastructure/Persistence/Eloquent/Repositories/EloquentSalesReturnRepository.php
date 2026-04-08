<?php

declare(strict_types=1);

namespace Modules\Returns\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Returns\Domain\Entities\SalesReturn;
use Modules\Returns\Domain\RepositoryInterfaces\SalesReturnRepositoryInterface;
use Modules\Returns\Infrastructure\Persistence\Eloquent\Models\SalesReturnModel;

class EloquentSalesReturnRepository implements SalesReturnRepositoryInterface
{
    public function findById(string $tenantId, string $id): ?SalesReturn
    {
        $model = SalesReturnModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->find($id);

        return $model !== null ? $this->mapToEntity($model) : null;
    }

    public function findAll(string $tenantId): array
    {
        return SalesReturnModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->get()
            ->map(fn (SalesReturnModel $model): SalesReturn => $this->mapToEntity($model))
            ->all();
    }

    public function findByCustomer(string $tenantId, string $customerId): array
    {
        return SalesReturnModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('customer_id', $customerId)
            ->get()
            ->map(fn (SalesReturnModel $model): SalesReturn => $this->mapToEntity($model))
            ->all();
    }

    public function findByStatus(string $tenantId, string $status): array
    {
        return SalesReturnModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('status', $status)
            ->get()
            ->map(fn (SalesReturnModel $model): SalesReturn => $this->mapToEntity($model))
            ->all();
    }

    public function findByReference(string $tenantId, string $reference): ?SalesReturn
    {
        $model = SalesReturnModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('reference', $reference)
            ->first();

        return $model !== null ? $this->mapToEntity($model) : null;
    }

    public function save(SalesReturn $return): void
    {
        $model = SalesReturnModel::withoutGlobalScopes()->findOrNew($return->id);
        $model->fill([
            'tenant_id'          => $return->tenantId,
            'sales_order_id'     => $return->salesOrderId,
            'customer_id'        => $return->customerId,
            'warehouse_id'       => $return->warehouseId,
            'reference'          => $return->reference,
            'status'             => $return->status,
            'return_date'        => $return->returnDate->format('Y-m-d'),
            'reason'             => $return->reason,
            'total_amount'       => $return->totalAmount,
            'credit_memo_number' => $return->creditMemoNumber,
            'refund_amount'      => $return->refundAmount,
            'restocking_fee'     => $return->restockingFee,
            'notes'              => $return->notes,
        ]);

        if (! $model->exists) {
            $model->id = $return->id;
        }

        $model->save();
    }

    public function delete(string $tenantId, string $id): void
    {
        SalesReturnModel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->find($id)?->delete();
    }

    private function mapToEntity(SalesReturnModel $model): SalesReturn
    {
        return new SalesReturn(
            id: (string) $model->id,
            tenantId: (string) $model->tenant_id,
            salesOrderId: $model->sales_order_id !== null ? (string) $model->sales_order_id : null,
            customerId: (string) $model->customer_id,
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
            restockingFee: (float) $model->restocking_fee,
            notes: $model->notes !== null ? (string) $model->notes : null,
            createdAt: $model->created_at ?? now(),
            updatedAt: $model->updated_at ?? now(),
        );
    }
}
