<?php

declare(strict_types=1);

namespace Modules\Rental\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Rental\Domain\Entities\RentalCharge;
use Modules\Rental\Domain\RepositoryInterfaces\RentalChargeRepositoryInterface;
use Modules\Rental\Infrastructure\Persistence\Eloquent\Models\RentalChargeModel;

class EloquentRentalChargeRepository implements RentalChargeRepositoryInterface
{
    public function __construct(private readonly RentalChargeModel $model) {}

    public function findById(int $tenantId, int $id): ?RentalCharge
    {
        /** @var RentalChargeModel|null $model */
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
            ->map(fn (RentalChargeModel $m) => $this->mapModelToEntity($m))
            ->all();
    }

    public function save(RentalCharge $charge): RentalCharge
    {
        $payload = [
            'tenant_id' => $charge->getTenantId(),
            'org_unit_id' => $charge->getOrgUnitId(),
            'row_version' => $charge->getRowVersion(),
            'rental_booking_id' => $charge->getRentalBookingId(),
            'rental_incident_id' => $charge->getRentalIncidentId(),
            'charge_type' => $charge->getChargeType(),
            'charge_direction' => $charge->getChargeDirection(),
            'currency_id' => $charge->getCurrencyId(),
            'amount' => $charge->getAmount(),
            'tax_amount' => $charge->getTaxAmount(),
            'total_amount' => $charge->getTotalAmount(),
            'due_date' => $charge->getDueDate(),
            'status' => $charge->getStatus(),
            'journal_entry_id' => $charge->getJournalEntryId(),
            'payment_id' => $charge->getPaymentId(),
            'reversal_of_id' => $charge->getReversalOfId(),
            'metadata' => $charge->getMetadata(),
        ];

        $id = $charge->getId();

        if ($id !== null) {
            $this->model->newQuery()
                ->where('tenant_id', $charge->getTenantId())
                ->where('id', $id)
                ->update($payload);

            /** @var RentalChargeModel $saved */
            $saved = $this->model->newQuery()
                ->where('tenant_id', $charge->getTenantId())
                ->where('id', $id)
                ->firstOrFail();
        } else {
            /** @var RentalChargeModel $saved */
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

    private function mapModelToEntity(RentalChargeModel $model): RentalCharge
    {
        return new RentalCharge(
            tenantId: (int) $model->tenant_id,
            rentalBookingId: (int) $model->rental_booking_id,
            chargeType: (string) $model->charge_type,
            chargeDirection: (string) $model->charge_direction,
            status: (string) $model->status,
            orgUnitId: $model->org_unit_id !== null ? (int) $model->org_unit_id : null,
            rentalIncidentId: $model->rental_incident_id !== null ? (int) $model->rental_incident_id : null,
            currencyId: $model->currency_id !== null ? (int) $model->currency_id : null,
            amount: (float) $model->amount,
            taxAmount: (float) $model->tax_amount,
            totalAmount: (float) $model->total_amount,
            dueDate: $model->due_date !== null ? (string) $model->due_date : null,
            journalEntryId: $model->journal_entry_id !== null ? (int) $model->journal_entry_id : null,
            paymentId: $model->payment_id !== null ? (int) $model->payment_id : null,
            reversalOfId: $model->reversal_of_id !== null ? (int) $model->reversal_of_id : null,
            metadata: is_array($model->metadata) ? $model->metadata : null,
            rowVersion: (int) $model->row_version,
            createdAt: $model->created_at !== null ? new \DateTimeImmutable($model->created_at) : null,
            updatedAt: $model->updated_at !== null ? new \DateTimeImmutable($model->updated_at) : null,
            id: (int) $model->id,
        );
    }
}
