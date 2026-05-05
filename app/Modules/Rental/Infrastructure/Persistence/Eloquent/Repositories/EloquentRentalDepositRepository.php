<?php

declare(strict_types=1);

namespace Modules\Rental\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Rental\Domain\Entities\RentalDeposit;
use Modules\Rental\Domain\RepositoryInterfaces\RentalDepositRepositoryInterface;
use Modules\Rental\Infrastructure\Persistence\Eloquent\Models\RentalDepositModel;

class EloquentRentalDepositRepository implements RentalDepositRepositoryInterface
{
    public function __construct(private readonly RentalDepositModel $model) {}

    public function findById(int $tenantId, int $id): ?RentalDeposit
    {
        /** @var RentalDepositModel|null $model */
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
            ->orderByDesc('held_at')
            ->get()
            ->map(fn (RentalDepositModel $m) => $this->mapModelToEntity($m))
            ->all();
    }

    public function save(RentalDeposit $deposit): RentalDeposit
    {
        $payload = [
            'tenant_id' => $deposit->getTenantId(),
            'org_unit_id' => $deposit->getOrgUnitId(),
            'row_version' => $deposit->getRowVersion(),
            'rental_booking_id' => $deposit->getRentalBookingId(),
            'currency_id' => $deposit->getCurrencyId(),
            'held_amount' => $deposit->getHeldAmount(),
            'released_amount' => $deposit->getReleasedAmount(),
            'forfeited_amount' => $deposit->getForfeitedAmount(),
            'status' => $deposit->getStatus(),
            'held_at' => $deposit->getHeldAt(),
            'released_at' => $deposit->getReleasedAt(),
            'payment_id' => $deposit->getPaymentId(),
            'journal_entry_id' => $deposit->getJournalEntryId(),
            'metadata' => $deposit->getMetadata(),
        ];

        $id = $deposit->getId();

        if ($id !== null) {
            $this->model->newQuery()
                ->where('tenant_id', $deposit->getTenantId())
                ->where('id', $id)
                ->update($payload);

            /** @var RentalDepositModel $saved */
            $saved = $this->model->newQuery()
                ->where('tenant_id', $deposit->getTenantId())
                ->where('id', $id)
                ->firstOrFail();
        } else {
            /** @var RentalDepositModel $saved */
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

    private function mapModelToEntity(RentalDepositModel $model): RentalDeposit
    {
        return new RentalDeposit(
            tenantId: (int) $model->tenant_id,
            rentalBookingId: (int) $model->rental_booking_id,
            currencyId: (int) $model->currency_id,
            heldAmount: (float) $model->held_amount,
            status: (string) $model->status,
            orgUnitId: $model->org_unit_id !== null ? (int) $model->org_unit_id : null,
            releasedAmount: (float) $model->released_amount,
            forfeitedAmount: (float) $model->forfeited_amount,
            heldAt: $model->held_at !== null ? (string) $model->held_at : null,
            releasedAt: $model->released_at !== null ? (string) $model->released_at : null,
            paymentId: $model->payment_id !== null ? (int) $model->payment_id : null,
            journalEntryId: $model->journal_entry_id !== null ? (int) $model->journal_entry_id : null,
            metadata: is_array($model->metadata) ? $model->metadata : null,
            rowVersion: (int) $model->row_version,
            id: (int) $model->id,
        );
    }
}
