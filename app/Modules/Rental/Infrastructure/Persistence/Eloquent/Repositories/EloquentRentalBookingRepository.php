<?php

declare(strict_types=1);

namespace Modules\Rental\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Rental\Domain\Entities\RentalBooking;
use Modules\Rental\Domain\RepositoryInterfaces\RentalBookingRepositoryInterface;
use Modules\Rental\Infrastructure\Persistence\Eloquent\Models\RentalBookingModel;

class EloquentRentalBookingRepository implements RentalBookingRepositoryInterface
{
    public function __construct(private readonly RentalBookingModel $model) {}

    public function findById(int $tenantId, int $id): ?RentalBooking
    {
        /** @var RentalBookingModel|null $model */
        $model = $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('id', $id)
            ->first();

        return $model !== null ? $this->mapModelToEntity($model) : null;
    }

    public function findByTenant(int $tenantId, int $orgUnitId = null, array $filters = []): array
    {
        $query = $this->model->newQuery()
            ->where('tenant_id', $tenantId);

        if ($orgUnitId !== null) {
            $query->where('org_unit_id', $orgUnitId);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['customer_id'])) {
            $query->where('customer_id', (int) $filters['customer_id']);
        }

        return $query->orderByDesc('pickup_at')
            ->get()
            ->map(fn (RentalBookingModel $m) => $this->mapModelToEntity($m))
            ->all();
    }

    public function save(RentalBooking $booking): RentalBooking
    {
        $payload = [
            'tenant_id' => $booking->getTenantId(),
            'org_unit_id' => $booking->getOrgUnitId(),
            'row_version' => $booking->getRowVersion(),
            'booking_number' => $booking->getBookingNumber(),
            'customer_id' => $booking->getCustomerId(),
            'rental_mode' => $booking->getRentalMode(),
            'ownership_model' => $booking->getOwnershipModel(),
            'status' => $booking->getStatus(),
            'pickup_at' => $booking->getPickupAt(),
            'return_due_at' => $booking->getReturnDueAt(),
            'actual_return_at' => $booking->getActualReturnAt(),
            'pickup_location' => $booking->getPickupLocation(),
            'return_location' => $booking->getReturnLocation(),
            'currency_id' => $booking->getCurrencyId(),
            'rate_plan' => $booking->getRatePlan(),
            'rate_amount' => $booking->getRateAmount(),
            'estimated_amount' => $booking->getEstimatedAmount(),
            'final_amount' => $booking->getFinalAmount(),
            'security_deposit_amount' => $booking->getSecurityDepositAmount(),
            'security_deposit_status' => $booking->getSecurityDepositStatus(),
            'partner_supplier_id' => $booking->getPartnerSupplierId(),
            'terms_and_conditions' => $booking->getTermsAndConditions(),
            'notes' => $booking->getNotes(),
            'metadata' => $booking->getMetadata(),
        ];

        $id = $booking->getId();

        if ($id !== null) {
            $this->model->newQuery()
                ->where('tenant_id', $booking->getTenantId())
                ->where('id', $id)
                ->update($payload);

            /** @var RentalBookingModel $saved */
            $saved = $this->model->newQuery()
                ->where('tenant_id', $booking->getTenantId())
                ->where('id', $id)
                ->firstOrFail();
        } else {
            /** @var RentalBookingModel $saved */
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

    public function findConflictingBookings(
        int $tenantId,
        int $assetId,
        string $pickupAt,
        string $returnDueAt,
        ?int $excludeBookingId = null,
    ): array {
        $query = $this->model->newQuery()
            ->where('rental_bookings.tenant_id', $tenantId)
            ->whereIn('rental_bookings.status', ['reserved', 'active'])
            ->join('rental_booking_assets', function ($join) use ($assetId): void {
                $join->on('rental_booking_assets.rental_booking_id', '=', 'rental_bookings.id')
                    ->where('rental_booking_assets.asset_id', '=', $assetId)
                    ->whereNull('rental_booking_assets.deleted_at');
            })
            ->where(function ($q) use ($pickupAt, $returnDueAt): void {
                $q->whereBetween('rental_bookings.pickup_at', [$pickupAt, $returnDueAt])
                    ->orWhereBetween('rental_bookings.return_due_at', [$pickupAt, $returnDueAt])
                    ->orWhere(function ($inner) use ($pickupAt, $returnDueAt): void {
                        $inner->where('rental_bookings.pickup_at', '<=', $pickupAt)
                            ->where('rental_bookings.return_due_at', '>=', $returnDueAt);
                    });
            })
            ->select('rental_bookings.*');

        if ($excludeBookingId !== null) {
            $query->where('rental_bookings.id', '!=', $excludeBookingId);
        }

        return $query->get()
            ->map(fn (RentalBookingModel $m) => $this->mapModelToEntity($m))
            ->all();
    }

    public function nextBookingNumber(int $tenantId, ?int $orgUnitId): string
    {
        $count = $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->withTrashed()
            ->count();

        $seq = str_pad((string) ($count + 1), 6, '0', STR_PAD_LEFT);
        $prefix = 'RNT-'.date('Ym');

        return "{$prefix}-{$seq}";
    }

    private function mapModelToEntity(RentalBookingModel $model): RentalBooking
    {
        return new RentalBooking(
            tenantId: (int) $model->tenant_id,
            customerId: (int) $model->customer_id,
            rentalMode: (string) $model->rental_mode,
            ownershipModel: (string) $model->ownership_model,
            pickupAt: (string) $model->pickup_at,
            returnDueAt: (string) $model->return_due_at,
            currencyId: (int) $model->currency_id,
            ratePlan: (string) $model->rate_plan,
            rateAmount: (float) $model->rate_amount,
            status: (string) $model->status,
            orgUnitId: $model->org_unit_id !== null ? (int) $model->org_unit_id : null,
            bookingNumber: (string) $model->booking_number,
            actualReturnAt: $model->actual_return_at !== null ? (string) $model->actual_return_at : null,
            pickupLocation: $model->pickup_location,
            returnLocation: $model->return_location,
            estimatedAmount: (float) $model->estimated_amount,
            finalAmount: (float) $model->final_amount,
            securityDepositAmount: (float) $model->security_deposit_amount,
            securityDepositStatus: (string) $model->security_deposit_status,
            partnerSupplierId: $model->partner_supplier_id !== null ? (int) $model->partner_supplier_id : null,
            termsAndConditions: $model->terms_and_conditions,
            notes: $model->notes,
            metadata: $model->metadata,
            rowVersion: (int) $model->row_version,
            id: (int) $model->id,
            createdAt: $model->created_at ? new \DateTimeImmutable($model->created_at->toISOString()) : null,
            updatedAt: $model->updated_at ? new \DateTimeImmutable($model->updated_at->toISOString()) : null,
        );
    }
}
