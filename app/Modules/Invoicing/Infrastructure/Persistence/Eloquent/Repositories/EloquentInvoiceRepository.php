<?php

declare(strict_types=1);

namespace Modules\Invoicing\Infrastructure\Persistence\Eloquent\Repositories;

use DateTimeImmutable;
use Modules\Invoicing\Domain\Entities\Invoice;
use Modules\Invoicing\Domain\RepositoryInterfaces\InvoiceRepositoryInterface;
use Modules\Invoicing\Domain\ValueObjects\InvoiceEntityType;
use Modules\Invoicing\Domain\ValueObjects\InvoiceStatus;
use Modules\Invoicing\Domain\ValueObjects\InvoiceType;
use Modules\Invoicing\Infrastructure\Persistence\Eloquent\Models\InvoiceModel;

class EloquentInvoiceRepository implements InvoiceRepositoryInterface
{
    public function findById(string $id): ?Invoice
    {
        $model = InvoiceModel::withoutGlobalScope('tenant')->find($id);

        return $model ? $this->toEntity($model) : null;
    }

    public function findByTenant(string $tenantId, string $orgUnitId): array
    {
        return InvoiceModel::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->where('org_unit_id', $orgUnitId)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (InvoiceModel $model) => $this->toEntity($model))
            ->all();
    }

    public function findByEntity(string $tenantId, string $entityType, string $entityId): array
    {
        return InvoiceModel::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (InvoiceModel $model) => $this->toEntity($model))
            ->all();
    }

    public function save(Invoice $invoice): Invoice
    {
        $model = InvoiceModel::withoutGlobalScope('tenant')->firstOrNew(['id' => $invoice->id]);

        $model->fill([
            'id' => $invoice->id,
            'tenant_id' => $invoice->tenantId,
            'org_unit_id' => $invoice->orgUnitId,
            'row_version' => $invoice->rowVersion,
            'invoice_number' => $invoice->invoiceNumber,
            'invoice_type' => $invoice->invoiceType->value,
            'entity_type' => $invoice->entityType->value,
            'entity_id' => $invoice->entityId,
            'status' => $invoice->status->value,
            'issue_date' => $invoice->issueDate->format('Y-m-d'),
            'due_date' => $invoice->dueDate->format('Y-m-d'),
            'subtotal_amount' => $invoice->subtotalAmount,
            'tax_amount' => $invoice->taxAmount,
            'total_amount' => $invoice->totalAmount,
            'paid_amount' => $invoice->paidAmount,
            'balance_amount' => $invoice->balanceAmount,
            'currency' => $invoice->currency,
            'notes' => $invoice->notes,
            'metadata' => $invoice->metadata,
            'is_active' => $invoice->isActive,
        ]);

        $model->save();

        return $this->toEntity($model->fresh());
    }

    public function updateStatus(string $id, string $status): Invoice
    {
        $model = InvoiceModel::withoutGlobalScope('tenant')->findOrFail($id);
        $model->update(['status' => $status]);
        $model->increment('row_version');

        return $this->toEntity($model->fresh());
    }

    public function recordPayment(string $id, string $amount): Invoice
    {
        $model = InvoiceModel::withoutGlobalScope('tenant')->findOrFail($id);

        $currentPaid = (float) $model->paid_amount;
        $total = (float) $model->total_amount;
        $newPaid = $currentPaid + (float) $amount;
        $balance = $total - $newPaid;

        $model->update([
            'paid_amount' => number_format($newPaid, 6, '.', ''),
            'balance_amount' => number_format($balance, 6, '.', ''),
            'status' => $balance <= 0.0 ? InvoiceStatus::Paid->value : $model->status,
        ]);
        $model->increment('row_version');

        return $this->toEntity($model->fresh());
    }

    public function delete(string $id): void
    {
        InvoiceModel::withoutGlobalScope('tenant')->where('id', $id)->delete();
    }

    private function toEntity(InvoiceModel $model): Invoice
    {
        return new Invoice(
            id: $model->id,
            tenantId: (string) $model->tenant_id,
            orgUnitId: (string) $model->org_unit_id,
            rowVersion: (int) $model->row_version,
            invoiceNumber: $model->invoice_number,
            invoiceType: InvoiceType::from($model->invoice_type),
            entityType: InvoiceEntityType::from($model->entity_type),
            entityId: $model->entity_id,
            status: InvoiceStatus::from($model->status),
            issueDate: DateTimeImmutable::createFromFormat('Y-m-d', $model->issue_date->format('Y-m-d')),
            dueDate: DateTimeImmutable::createFromFormat('Y-m-d', $model->due_date->format('Y-m-d')),
            subtotalAmount: number_format((float) $model->subtotal_amount, 6, '.', ''),
            taxAmount: number_format((float) $model->tax_amount, 6, '.', ''),
            totalAmount: number_format((float) $model->total_amount, 6, '.', ''),
            paidAmount: number_format((float) $model->paid_amount, 6, '.', ''),
            balanceAmount: number_format((float) $model->balance_amount, 6, '.', ''),
            currency: $model->currency,
            notes: $model->notes,
            metadata: $model->metadata,
            isActive: (bool) $model->is_active,
            createdAt: DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $model->created_at->format('Y-m-d H:i:s')),
            updatedAt: DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $model->updated_at->format('Y-m-d H:i:s')),
        );
    }
}
