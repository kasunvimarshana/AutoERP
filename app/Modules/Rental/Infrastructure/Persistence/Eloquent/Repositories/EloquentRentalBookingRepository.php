<?php

declare(strict_types=1);

namespace Modules\Rental\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Rental\Domain\Entities\RentalBooking;
use Modules\Rental\Domain\RepositoryInterfaces\RentalBookingRepositoryInterface;
use Modules\Rental\Infrastructure\Persistence\Eloquent\Models\RentalBookingModel;

class EloquentRentalBookingRepository implements RentalBookingRepositoryInterface
{
    public function __construct(
        private readonly RentalBookingModel $model,
    ) {}

    public function save(RentalBooking $booking): RentalBooking
    {
        if ($booking->getId() !== null) {
            /** @var RentalBookingModel $record */
            $record = $this->model->newQuery()->findOrFail($booking->getId());
            $record->update($this->toArray($booking));
            $record->refresh();
        } else {
            /** @var RentalBookingModel $record */
            $record = $this->model->newQuery()->create($this->toArray($booking));
        }

        return $this->mapToEntity($record);
    }

    public function findById(int $tenantId, int $id): ?RentalBooking
    {
        /** @var RentalBookingModel|null $record */
        $record = $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->find($id);

        return $record !== null ? $this->mapToEntity($record) : null;
    }

    public function findByBookingNumber(int $tenantId, string $bookingNumber): ?RentalBooking
    {
        /** @var RentalBookingModel|null $record */
        $record = $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('booking_number', $bookingNumber)
            ->first();

        return $record !== null ? $this->mapToEntity($record) : null;
    }

    public function paginate(int $tenantId, array $filters, int $perPage, int $page): array
    {
        $query = $this->model->newQuery()->where('tenant_id', $tenantId);

        foreach ($filters as $field => $value) {
            if ($value !== null && $value !== '') {
                $query->where($field, $value);
            }
        }

        $paginator = $query->orderByDesc('id')->paginate($perPage, ['*'], 'page', $page);

        return [
            'data' => array_map(fn (RentalBookingModel $m): RentalBooking => $this->mapToEntity($m), $paginator->items()),
            'total' => $paginator->total(),
            'per_page' => $paginator->perPage(),
            'current_page' => $paginator->currentPage(),
        ];
    }

    public function existsByBookingNumber(int $tenantId, string $bookingNumber): bool
    {
        return $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('booking_number', $bookingNumber)
            ->exists();
    }

    private function toArray(RentalBooking $booking): array
    {
        return [
            'tenant_id' => $booking->getTenantId(),
            'org_unit_id' => $booking->getOrgUnitId(),
            'row_version' => $booking->getRowVersion(),
            'booking_number' => $booking->getBookingNumber(),
            'customer_id' => $booking->getCustomerId(),
            'booking_type' => $booking->getBookingType(),
            'fleet_source' => $booking->getFleetSource(),
            'status' => $booking->getStatus(),
            'scheduled_start_at' => $booking->getScheduledStartAt(),
            'scheduled_end_at' => $booking->getScheduledEndAt(),
            'actual_start_at' => $booking->getActualStartAt(),
            'actual_end_at' => $booking->getActualEndAt(),
            'subtotal' => $booking->getSubtotal(),
            'discount_amount' => $booking->getDiscountAmount(),
            'tax_amount' => $booking->getTaxAmount(),
            'deposit_amount' => $booking->getDepositAmount(),
            'total_amount' => $booking->getTotalAmount(),
            'deposit_status' => $booking->getDepositStatus(),
            'ar_transaction_id' => $booking->getArTransactionId(),
            'journal_entry_id' => $booking->getJournalEntryId(),
            'notes' => $booking->getNotes(),
            'metadata' => $booking->getMetadata(),
        ];
    }

    private function mapToEntity(RentalBookingModel $model): RentalBooking
    {
        return new RentalBooking(
            tenantId: (int) $model->tenant_id,
            customerId: (int) $model->customer_id,
            bookingNumber: (string) $model->booking_number,
            bookingType: (string) $model->booking_type,
            fleetSource: (string) $model->fleet_source,
            status: (string) $model->status,
            orgUnitId: $model->org_unit_id !== null ? (int) $model->org_unit_id : null,
            scheduledStartAt: $model->scheduled_start_at,
            scheduledEndAt: $model->scheduled_end_at,
            actualStartAt: $model->actual_start_at,
            actualEndAt: $model->actual_end_at,
            subtotal: (string) $model->subtotal,
            discountAmount: (string) $model->discount_amount,
            taxAmount: (string) $model->tax_amount,
            depositAmount: (string) $model->deposit_amount,
            totalAmount: (string) $model->total_amount,
            depositStatus: (string) $model->deposit_status,
            arTransactionId: $model->ar_transaction_id !== null ? (int) $model->ar_transaction_id : null,
            journalEntryId: $model->journal_entry_id !== null ? (int) $model->journal_entry_id : null,
            notes: $model->notes,
            metadata: is_array($model->metadata) ? $model->metadata : null,
            rowVersion: (int) $model->row_version,
            createdAt: $model->created_at,
            updatedAt: $model->updated_at,
            id: (int) $model->id,
        );
    }
}
