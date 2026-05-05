<?php

declare(strict_types=1);

namespace Modules\ReturnRefund\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\ReturnRefund\Domain\Entities\RefundTransaction;
use Modules\ReturnRefund\Domain\RepositoryInterfaces\RefundTransactionRepositoryInterface;
use Modules\ReturnRefund\Infrastructure\Persistence\Eloquent\Models\RefundTransactionModel;

class EloquentRefundTransactionRepository implements RefundTransactionRepositoryInterface
{
    public function create(RefundTransaction $refund): void
    {
        RefundTransactionModel::create([
            'id' => $refund->getId(),
            'tenant_id' => $refund->getTenantId(),
            'rental_transaction_id' => $refund->getRentalTransactionId(),
            'refund_number' => $refund->getRefundNumber(),
            'gross_amount' => $refund->getGrossAmount(),
            'adjustment_amount' => $refund->getAdjustmentAmount(),
            'net_refund_amount' => $refund->getNetRefundAmount(),
            'status' => $refund->getStatus(),
            'finance_reference_id' => $refund->getFinanceReferenceId(),
            'processed_at' => $refund->getProcessedAt(),
        ]);
    }

    public function findById(string $id): ?RefundTransaction
    {
        $model = RefundTransactionModel::find($id);

        return $model ? $this->toDomain($model) : null;
    }

    public function findByRefundNumber(string $tenantId, string $refundNumber): ?RefundTransaction
    {
        $model = RefundTransactionModel::byTenant($tenantId)
            ->where('refund_number', $refundNumber)
            ->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function getByStatus(string $tenantId, string $status, int $page = 1, int $limit = 50): array
    {
        $query = RefundTransactionModel::byTenant($tenantId)->byStatus($status);
        $total = $query->count();
        $data = $query->paginate($limit, ['*'], 'page', $page)->items();

        return [
            'data' => array_map(fn ($m) => $this->toDomain($m), $data),
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
        ];
    }

    public function update(RefundTransaction $refund): void
    {
        RefundTransactionModel::findOrFail($refund->getId())->update([
            'status' => $refund->getStatus(),
            'finance_reference_id' => $refund->getFinanceReferenceId(),
            'processed_at' => $refund->getProcessedAt(),
        ]);
    }

    private function toDomain(RefundTransactionModel $model): RefundTransaction
    {
        return new RefundTransaction(
            id: (string) $model->id,
            tenantId: (string) $model->tenant_id,
            rentalTransactionId: (string) $model->rental_transaction_id,
            refundNumber: (string) $model->refund_number,
            grossAmount: (string) $model->gross_amount,
            adjustmentAmount: (string) $model->adjustment_amount,
            netRefundAmount: (string) $model->net_refund_amount,
            status: (string) $model->status,
            financeReferenceId: $model->finance_reference_id !== null ? (string) $model->finance_reference_id : null,
            createdAt: $model->created_at,
            processedAt: $model->processed_at,
        );
    }
}
