<?php

declare(strict_types=1);

namespace Modules\Rental\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Rental\Domain\Entities\RentalExpense;
use Modules\Rental\Domain\RepositoryInterfaces\RentalExpenseRepositoryInterface;
use Modules\Rental\Infrastructure\Persistence\Eloquent\Models\RentalExpenseModel;

class EloquentRentalExpenseRepository implements RentalExpenseRepositoryInterface
{
    public function __construct(private readonly RentalExpenseModel $model) {}

    public function findById(int $tenantId, int $id): ?RentalExpense
    {
        /** @var RentalExpenseModel|null $model */
        $model = $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('id', $id)
            ->first();

        return $model !== null ? $this->mapModelToEntity($model) : null;
    }

    public function findByTenant(int $tenantId, array $filters = []): array
    {
        $query = $this->model->newQuery()->where('tenant_id', $tenantId);

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['expense_type'])) {
            $query->where('expense_type', $filters['expense_type']);
        }

        return $query->orderByDesc('id')
            ->get()
            ->map(fn (RentalExpenseModel $m) => $this->mapModelToEntity($m))
            ->all();
    }

    public function findByBooking(int $tenantId, int $bookingId): array
    {
        return $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('rental_booking_id', $bookingId)
            ->orderBy('id')
            ->get()
            ->map(fn (RentalExpenseModel $m) => $this->mapModelToEntity($m))
            ->all();
    }

    public function save(RentalExpense $expense): RentalExpense
    {
        $payload = [
            'tenant_id' => $expense->getTenantId(),
            'org_unit_id' => $expense->getOrgUnitId(),
            'row_version' => $expense->getRowVersion(),
            'rental_booking_id' => $expense->getRentalBookingId(),
            'asset_id' => $expense->getAssetId(),
            'expense_type' => $expense->getExpenseType(),
            'incurred_at' => $expense->getIncurredAt(),
            'supplier_id' => $expense->getSupplierId(),
            'employee_id' => $expense->getEmployeeId(),
            'currency_id' => $expense->getCurrencyId(),
            'amount' => $expense->getAmount(),
            'tax_amount' => $expense->getTaxAmount(),
            'total_amount' => $expense->getTotalAmount(),
            'status' => $expense->getStatus(),
            'journal_entry_id' => $expense->getJournalEntryId(),
            'payment_id' => $expense->getPaymentId(),
            'reversal_of_id' => $expense->getReversalOfId(),
            'notes' => $expense->getNotes(),
            'metadata' => $expense->getMetadata(),
        ];

        $id = $expense->getId();

        if ($id !== null) {
            $this->model->newQuery()
                ->where('tenant_id', $expense->getTenantId())
                ->where('id', $id)
                ->update($payload);

            /** @var RentalExpenseModel $saved */
            $saved = $this->model->newQuery()
                ->where('tenant_id', $expense->getTenantId())
                ->where('id', $id)
                ->firstOrFail();
        } else {
            /** @var RentalExpenseModel $saved */
            $saved = $this->model->newQuery()->create($payload);
        }

        return $this->mapModelToEntity($saved);
    }

    public function delete(int $tenantId, int $id): bool
    {
        return (bool) $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('id', $id)
            ->delete();
    }

    private function mapModelToEntity(RentalExpenseModel $model): RentalExpense
    {
        return new RentalExpense(
            tenantId: (int) $model->tenant_id,
            expenseType: (string) $model->expense_type,
            status: (string) $model->status,
            orgUnitId: $model->org_unit_id !== null ? (int) $model->org_unit_id : null,
            rentalBookingId: $model->rental_booking_id !== null ? (int) $model->rental_booking_id : null,
            assetId: $model->asset_id !== null ? (int) $model->asset_id : null,
            incurredAt: $model->incurred_at !== null ? (string) $model->incurred_at : null,
            supplierId: $model->supplier_id !== null ? (int) $model->supplier_id : null,
            employeeId: $model->employee_id !== null ? (int) $model->employee_id : null,
            currencyId: $model->currency_id !== null ? (int) $model->currency_id : null,
            amount: (float) $model->amount,
            taxAmount: (float) $model->tax_amount,
            totalAmount: (float) $model->total_amount,
            journalEntryId: $model->journal_entry_id !== null ? (int) $model->journal_entry_id : null,
            paymentId: $model->payment_id !== null ? (int) $model->payment_id : null,
            reversalOfId: $model->reversal_of_id !== null ? (int) $model->reversal_of_id : null,
            notes: $model->notes,
            metadata: is_array($model->metadata) ? $model->metadata : null,
            rowVersion: (int) $model->row_version,
            createdAt: $model->created_at !== null ? new \DateTimeImmutable($model->created_at) : null,
            updatedAt: $model->updated_at !== null ? new \DateTimeImmutable($model->updated_at) : null,
            id: (int) $model->id,
        );
    }
}
