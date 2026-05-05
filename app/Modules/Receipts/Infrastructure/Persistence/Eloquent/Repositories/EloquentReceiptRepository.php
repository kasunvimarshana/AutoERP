<?php

declare(strict_types=1);

namespace Modules\Receipts\Infrastructure\Persistence\Eloquent\Repositories;

use DateTimeImmutable;
use Illuminate\Database\Eloquent\Builder;
use Modules\Receipts\Domain\Entities\Receipt;
use Modules\Receipts\Domain\RepositoryInterfaces\ReceiptRepositoryInterface;
use Modules\Receipts\Domain\ValueObjects\ReceiptStatus;
use Modules\Receipts\Domain\ValueObjects\ReceiptType;
use Modules\Receipts\Infrastructure\Persistence\Eloquent\Models\ReceiptModel;

class EloquentReceiptRepository implements ReceiptRepositoryInterface
{
    public function findById(string $id): ?Receipt
    {
        /** @var ReceiptModel|null $model */
        $model = $this->tenantScopedQuery()->where('id', $id)->first();

        return $model ? $this->toEntity($model) : null;
    }

    public function findByTenant(string $tenantId, string $orgUnitId): array
    {
        return $this->tenantScopedQuery()
            ->where('tenant_id', $tenantId)
            ->where('org_unit_id', $orgUnitId)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (ReceiptModel $model) => $this->toEntity($model))
            ->all();
    }

    public function findByPayment(string $tenantId, string $paymentId): array
    {
        return $this->tenantScopedQuery()
            ->where('tenant_id', $tenantId)
            ->where('payment_id', $paymentId)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (ReceiptModel $model) => $this->toEntity($model))
            ->all();
    }

    public function save(Receipt $receipt): Receipt
    {
        $model = $this->tenantScopedQuery()->firstOrNew([
            'id' => $receipt->id,
            'tenant_id' => $receipt->tenantId,
        ]);

        $model->fill([
            'id' => $receipt->id,
            'tenant_id' => $receipt->tenantId,
            'org_unit_id' => $receipt->orgUnitId,
            'row_version' => $receipt->rowVersion,
            'receipt_number' => $receipt->receiptNumber,
            'payment_id' => $receipt->paymentId,
            'invoice_id' => $receipt->invoiceId,
            'receipt_type' => $receipt->receiptType->value,
            'status' => $receipt->status->value,
            'amount' => $receipt->amount,
            'currency' => $receipt->currency,
            'issued_at' => $receipt->issuedAt?->format('Y-m-d H:i:s'),
            'notes' => $receipt->notes,
            'metadata' => $receipt->metadata,
            'is_active' => $receipt->isActive,
        ]);

        $model->save();

        return $this->toEntity($model->fresh());
    }

    public function updateStatus(string $id, string $status): Receipt
    {
        /** @var ReceiptModel $model */
        $model = $this->tenantScopedQuery()->where('id', $id)->firstOrFail();
        $model->update(['status' => $status]);
        $model->increment('row_version');

        return $this->toEntity($model->fresh());
    }

    public function delete(string $id): void
    {
        $this->tenantScopedQuery()->where('id', $id)->delete();
    }

    private function tenantScopedQuery(): Builder
    {
        /** @var Builder $query */
        $query = ReceiptModel::withoutGlobalScope('tenant')->newQuery();
        $tenantId = $this->resolveCurrentTenantId();

        if ($tenantId !== null) {
            $query->where('tenant_id', $tenantId);
        }

        return $query;
    }

    private function resolveCurrentTenantId(): ?string
    {
        if (! app()->bound('current_tenant_id')) {
            return null;
        }

        $tenantId = app('current_tenant_id');

        return is_scalar($tenantId) ? (string) $tenantId : null;
    }

    private function toEntity(ReceiptModel $model): Receipt
    {
        return new Receipt(
            id: $model->id,
            tenantId: (string) $model->tenant_id,
            orgUnitId: (string) $model->org_unit_id,
            rowVersion: (int) $model->row_version,
            receiptNumber: $model->receipt_number,
            paymentId: $model->payment_id,
            invoiceId: $model->invoice_id,
            receiptType: ReceiptType::from($model->receipt_type),
            status: ReceiptStatus::from($model->status),
            amount: number_format((float) $model->amount, 6, '.', ''),
            currency: $model->currency,
            issuedAt: $model->issued_at
                ? DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $model->issued_at->format('Y-m-d H:i:s'))
                : null,
            notes: $model->notes,
            metadata: $model->metadata,
            isActive: (bool) $model->is_active,
            createdAt: DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $model->created_at->format('Y-m-d H:i:s')),
            updatedAt: DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $model->updated_at->format('Y-m-d H:i:s')),
        );
    }
}
