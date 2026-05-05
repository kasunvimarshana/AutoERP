<?php

declare(strict_types=1);

namespace Modules\Rental\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Rental\Domain\Entities\RentalSettlement;
use Modules\Rental\Domain\RepositoryInterfaces\RentalSettlementRepositoryInterface;
use Modules\Rental\Infrastructure\Persistence\Eloquent\Models\RentalSettlementModel;

class EloquentRentalSettlementRepository implements RentalSettlementRepositoryInterface
{
    public function __construct(private readonly RentalSettlementModel $model) {}

    public function findById(int $tenantId, int $id): ?RentalSettlement
    {
        /** @var RentalSettlementModel|null $model */
        $model = $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('id', $id)
            ->first();

        return $model !== null ? $this->mapModelToEntity($model) : null;
    }

    public function findByBooking(int $tenantId, int $bookingId): array
    {
        return $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('rental_booking_id', $bookingId)
            ->orderBy('id')
            ->get()
            ->map(fn (RentalSettlementModel $m) => $this->mapModelToEntity($m))
            ->all();
    }

    public function save(RentalSettlement $settlement): RentalSettlement
    {
        $payload = [
            'tenant_id' => $settlement->getTenantId(),
            'org_unit_id' => $settlement->getOrgUnitId(),
            'row_version' => $settlement->getRowVersion(),
            'rental_booking_id' => $settlement->getRentalBookingId(),
            'settlement_party_type' => $settlement->getSettlementPartyType(),
            'employee_id' => $settlement->getEmployeeId(),
            'supplier_id' => $settlement->getSupplierId(),
            'settlement_type' => $settlement->getSettlementType(),
            'currency_id' => $settlement->getCurrencyId(),
            'amount' => $settlement->getAmount(),
            'tax_amount' => $settlement->getTaxAmount(),
            'total_amount' => $settlement->getTotalAmount(),
            'status' => $settlement->getStatus(),
            'journal_entry_id' => $settlement->getJournalEntryId(),
            'payment_id' => $settlement->getPaymentId(),
            'reversal_of_id' => $settlement->getReversalOfId(),
            'notes' => $settlement->getNotes(),
            'metadata' => $settlement->getMetadata(),
        ];

        $id = $settlement->getId();

        if ($id !== null) {
            $this->model->newQuery()
                ->where('tenant_id', $settlement->getTenantId())
                ->where('id', $id)
                ->update($payload);

            /** @var RentalSettlementModel $saved */
            $saved = $this->model->newQuery()
                ->where('tenant_id', $settlement->getTenantId())
                ->where('id', $id)
                ->firstOrFail();
        } else {
            /** @var RentalSettlementModel $saved */
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

    private function mapModelToEntity(RentalSettlementModel $model): RentalSettlement
    {
        return new RentalSettlement(
            tenantId: (int) $model->tenant_id,
            rentalBookingId: (int) $model->rental_booking_id,
            settlementPartyType: (string) $model->settlement_party_type,
            settlementType: (string) $model->settlement_type,
            status: (string) $model->status,
            orgUnitId: $model->org_unit_id !== null ? (int) $model->org_unit_id : null,
            employeeId: $model->employee_id !== null ? (int) $model->employee_id : null,
            supplierId: $model->supplier_id !== null ? (int) $model->supplier_id : null,
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
