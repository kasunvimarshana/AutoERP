<?php

declare(strict_types=1);

namespace Modules\Payments\Infrastructure\Persistence\Eloquent\Repositories;

use DateTimeImmutable;
use Modules\Payments\Domain\Entities\Payment;
use Modules\Payments\Domain\RepositoryInterfaces\PaymentRepositoryInterface;
use Modules\Payments\Domain\ValueObjects\PaymentMethod;
use Modules\Payments\Domain\ValueObjects\PaymentStatus;
use Modules\Payments\Infrastructure\Persistence\Eloquent\Models\PaymentModel;

class EloquentPaymentRepository implements PaymentRepositoryInterface
{
    public function findById(string $tenantId, string $id): ?Payment
    {
        $model = PaymentModel::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->where('id', $id)
            ->first();

        return $model ? $this->toEntity($model) : null;
    }

    public function findByTenant(string $tenantId, string $orgUnitId): array
    {
        return PaymentModel::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->where('org_unit_id', $orgUnitId)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (PaymentModel $model) => $this->toEntity($model))
            ->all();
    }

    public function findByInvoice(string $tenantId, string $invoiceId): array
    {
        return PaymentModel::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->where('invoice_id', $invoiceId)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (PaymentModel $model) => $this->toEntity($model))
            ->all();
    }

    public function save(Payment $payment): Payment
    {
        $model = PaymentModel::withoutGlobalScope('tenant')->firstOrNew(['id' => $payment->id]);

        $model->fill([
            'id' => $payment->id,
            'tenant_id' => $payment->tenantId,
            'org_unit_id' => $payment->orgUnitId,
            'row_version' => $payment->rowVersion,
            'payment_number' => $payment->paymentNumber,
            'invoice_id' => $payment->invoiceId,
            'payment_method' => $payment->paymentMethod->value,
            'status' => $payment->status->value,
            'amount' => $payment->amount,
            'currency' => $payment->currency,
            'paid_at' => $payment->paidAt?->format('Y-m-d H:i:s'),
            'reference_number' => $payment->referenceNumber,
            'notes' => $payment->notes,
            'metadata' => $payment->metadata,
            'is_active' => $payment->isActive,
        ]);

        $model->save();

        return $this->toEntity($model->fresh());
    }

    public function updateStatus(string $tenantId, string $id, string $status): Payment
    {
        /** @var PaymentModel $model */
        $model = PaymentModel::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->where('id', $id)
            ->firstOrFail();

        $updates = ['status' => $status];
        if ($status === PaymentStatus::Completed->value) {
            $updates['paid_at'] = now()->format('Y-m-d H:i:s');
        }

        $model->update($updates);
        $model->increment('row_version');

        return $this->toEntity($model->fresh());
    }

    public function delete(string $tenantId, string $id): void
    {
        PaymentModel::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->where('id', $id)
            ->delete();
    }

    private function toEntity(PaymentModel $model): Payment
    {
        return new Payment(
            id: $model->id,
            tenantId: (string) $model->tenant_id,
            orgUnitId: (string) $model->org_unit_id,
            rowVersion: (int) $model->row_version,
            paymentNumber: $model->payment_number,
            invoiceId: $model->invoice_id,
            paymentMethod: PaymentMethod::from($model->payment_method),
            status: PaymentStatus::from($model->status),
            amount: number_format((float) $model->amount, 6, '.', ''),
            currency: $model->currency,
            paidAt: $model->paid_at
                ? DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $model->paid_at->format('Y-m-d H:i:s'))
                : null,
            referenceNumber: $model->reference_number,
            notes: $model->notes,
            metadata: $model->metadata,
            isActive: (bool) $model->is_active,
            createdAt: DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $model->created_at->format('Y-m-d H:i:s')),
            updatedAt: DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $model->updated_at->format('Y-m-d H:i:s')),
        );
    }
}
