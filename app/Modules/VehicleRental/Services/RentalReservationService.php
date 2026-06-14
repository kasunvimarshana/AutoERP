<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Services;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Customer\Enums\CustomerStatus;
use Modules\Customer\Models\Customer;
use Modules\Sequence\Services\Sequences\GenerateSequenceNumberService;
use Modules\Supplier\Enums\SupplierStatus;
use Modules\Supplier\Models\Supplier;
use Modules\VehicleRental\DTOs\RentalReservationData;
use Modules\VehicleRental\Enums\RentalAgreementDirection;
use Modules\VehicleRental\Enums\RentalPartyType;
use Modules\VehicleRental\Enums\RentalReservationStatus;
use Modules\VehicleRental\Models\RentalReservation;
use Modules\VehicleRental\Models\RentalStatusHistory;
use RuntimeException;

final class RentalReservationService
{
    /** @var array<string, list<string>> */
    private const TRANSITIONS = [
        'draft' => ['pending', 'confirmed', 'cancelled'],
        'pending' => ['confirmed', 'cancelled', 'expired'],
        'confirmed' => ['converted', 'cancelled', 'expired'],
        'converted' => [],
        'cancelled' => [],
        'expired' => [],
    ];

    public function __construct(
        private readonly GenerateSequenceNumberService $sequences,
        private readonly RentalAvailabilityService $availability,
    ) {}

    public function create(RentalReservationData $data): RentalReservation
    {
        return DB::transaction(function () use ($data): RentalReservation {
            $this->validate($data);
            if ($data->vehicleId !== null) {
                $this->availability->assertAvailable(
                    $data->tenantId,
                    $data->organizationUnitId,
                    $data->vehicleId,
                    $data->startAt,
                    $data->expectedEndAt,
                    direction: $data->direction,
                );
            }

            $reservation = RentalReservation::query()->create([
                'tenant_id' => $data->tenantId,
                'organization_unit_id' => $data->organizationUnitId,
                'reservation_number' => $data->reservationNumber ?? $this->nextNumber($data),
                'direction' => $data->direction->value,
                'party_type' => $data->partyType->value,
                'party_id' => $data->partyId,
                'rental_type' => $data->rentalType->value,
                'vehicle_id' => $data->vehicleId,
                'start_at' => $data->startAt,
                'expected_end_at' => $data->expectedEndAt,
                'currency_id' => $data->currencyId,
                'status' => RentalReservationStatus::Draft->value,
                'remarks' => $data->remarks,
                'created_by' => $data->createdBy,
            ]);
            $this->recordStatus($reservation, null, RentalReservationStatus::Draft, $data->createdBy);

            return $reservation->load(['customer', 'supplier', 'vehicle.make', 'vehicle.model']);
        });
    }

    public function update(RentalReservation $reservation, RentalReservationData $data): RentalReservation
    {
        if (! in_array($reservation->status, [RentalReservationStatus::Draft, RentalReservationStatus::Pending], true)) {
            throw new InvalidArgumentException('Only draft or pending reservations can be edited.');
        }
        if ((int) $reservation->tenant_id !== $data->tenantId
            || $reservation->organization_unit_id !== $data->organizationUnitId) {
            throw new InvalidArgumentException('Reservation scope cannot be changed.');
        }

        return DB::transaction(function () use ($reservation, $data): RentalReservation {
            $this->validate($data);
            if ($data->vehicleId !== null) {
                $this->availability->assertAvailable(
                    $data->tenantId,
                    $data->organizationUnitId,
                    $data->vehicleId,
                    $data->startAt,
                    $data->expectedEndAt,
                    excludeReservationId: (int) $reservation->getKey(),
                    direction: $data->direction,
                );
            }

            $reservation->fill([
                'direction' => $data->direction->value,
                'party_type' => $data->partyType->value,
                'party_id' => $data->partyId,
                'rental_type' => $data->rentalType->value,
                'vehicle_id' => $data->vehicleId,
                'start_at' => $data->startAt,
                'expected_end_at' => $data->expectedEndAt,
                'currency_id' => $data->currencyId,
                'remarks' => $data->remarks,
                'updated_by' => $data->createdBy,
            ])->save();

            return $reservation->refresh()->load(['customer', 'supplier', 'vehicle.make', 'vehicle.model']);
        });
    }

    public function changeStatus(
        RentalReservation $reservation,
        RentalReservationStatus $status,
        ?int $changedBy = null,
        ?string $reason = null,
    ): RentalReservation {
        $old = $reservation->status;
        if ($old === $status) {
            return $reservation;
        }
        if (! in_array($status->value, self::TRANSITIONS[$old->value] ?? [], true)) {
            throw new InvalidArgumentException(
                "Invalid rental reservation status transition from {$old->value} to {$status->value}.",
            );
        }

        return DB::transaction(function () use ($reservation, $status, $old, $changedBy, $reason): RentalReservation {
            $reservation = RentalReservation::query()->lockForUpdate()->findOrFail($reservation->getKey());
            if ($status === RentalReservationStatus::Confirmed && $reservation->vehicle_id !== null) {
                $this->availability->assertAvailable(
                    (int) $reservation->tenant_id,
                    $reservation->organization_unit_id,
                    (int) $reservation->vehicle_id,
                    $reservation->start_at->toDateTimeString(),
                    $reservation->expected_end_at->toDateTimeString(),
                    excludeReservationId: (int) $reservation->getKey(),
                    direction: $reservation->direction,
                );
            }
            $reservation->forceFill(['status' => $status->value, 'updated_by' => $changedBy])->save();
            $this->recordStatus($reservation, $old, $status, $changedBy, $reason);

            return $reservation->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(
        int $tenantId,
        ?int $organizationUnitId,
        array $filters,
        int $perPage,
    ): LengthAwarePaginator {
        $query = RentalReservation::query()
            ->forContext($tenantId, $organizationUnitId)
            ->with(['customer', 'supplier', 'vehicle.make', 'vehicle.model']);
        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function (Builder $scope) use ($search): void {
                $scope->where('reservation_number', 'like', "%{$search}%")
                    ->orWhereHas('vehicle', fn (Builder $vehicle) => $vehicle
                        ->where('vehicle_number', 'like', "%{$search}%")
                        ->orWhere('registration_number', 'like', "%{$search}%"));
            });
        }
        foreach (['status', 'direction', 'party_type', 'party_id', 'vehicle_id'] as $filter) {
            if (isset($filters[$filter]) && $filters[$filter] !== '') {
                $query->where($filter, $filters[$filter]);
            }
        }
        if (! empty($filters['date_from'])) {
            $query->whereDate('start_at', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $query->whereDate('start_at', '<=', $filters['date_to']);
        }

        return $query->latest('start_at')->latest('id')->paginate($perPage);
    }

    private function validate(RentalReservationData $data): void
    {
        $start = CarbonImmutable::parse($data->startAt);
        $end = CarbonImmutable::parse($data->expectedEndAt);
        if ($end->lessThanOrEqualTo($start)) {
            throw new InvalidArgumentException('Reservation end date and time must be after the start date and time.');
        }
        $this->party($data->tenantId, $data->organizationUnitId, $data->direction, $data->partyType, $data->partyId);
    }

    private function party(
        int $tenantId,
        ?int $organizationUnitId,
        RentalAgreementDirection $direction,
        RentalPartyType $partyType,
        int $partyId,
    ): void {
        if ($direction === RentalAgreementDirection::Outbound && $partyType !== RentalPartyType::Customer) {
            throw new InvalidArgumentException('Outbound rentals require a customer party.');
        }
        if ($direction === RentalAgreementDirection::Inbound && ! in_array($partyType, [
            RentalPartyType::Supplier,
            RentalPartyType::Owner,
        ], true)) {
            throw new InvalidArgumentException('Inbound hire-in agreements require a supplier or owner party.');
        }

        if ($partyType === RentalPartyType::Customer) {
            $party = Customer::query()->where('tenant_id', $tenantId)
                ->where(fn (Builder $query) => $query->whereNull('organization_unit_id')
                    ->when($organizationUnitId !== null, fn (Builder $inner) => $inner->orWhere('organization_unit_id', $organizationUnitId)))
                ->findOrFail($partyId);
            if ($party->status !== CustomerStatus::Active) {
                throw new InvalidArgumentException('Only active customers can be used for rental reservations.');
            }

            return;
        }

        $party = Supplier::query()->where('tenant_id', $tenantId)
            ->where(fn (Builder $query) => $query->whereNull('organization_unit_id')
                ->when($organizationUnitId !== null, fn (Builder $inner) => $inner->orWhere('organization_unit_id', $organizationUnitId)))
            ->findOrFail($partyId);
        if ($party->status !== SupplierStatus::Active) {
            throw new InvalidArgumentException('Only active suppliers or owners can be used for hire-in reservations.');
        }
    }

    private function nextNumber(RentalReservationData $data): string
    {
        $result = $this->sequences->execute([
            'tenant_id' => $data->tenantId,
            'organization_unit_id' => $data->organizationUnitId,
            'document_type' => 'rental_reservation',
            'period_type' => 'yearly',
            'at_date' => CarbonImmutable::parse($data->startAt)->toDateString(),
            'prefix' => 'RRES-{PERIOD}-',
            'padding' => 6,
        ]);
        if ($result->isFailure()) {
            throw new RuntimeException($result->errorOrFail()->message);
        }

        return (string) $result->valueOrFail()['generated_number'];
    }

    private function recordStatus(
        RentalReservation $reservation,
        ?RentalReservationStatus $old,
        RentalReservationStatus $new,
        ?int $changedBy,
        ?string $reason = null,
    ): void {
        RentalStatusHistory::query()->create([
            'tenant_id' => $reservation->tenant_id,
            'organization_unit_id' => $reservation->organization_unit_id,
            'reservation_id' => $reservation->getKey(),
            'entity_type' => 'reservation',
            'old_status' => $old?->value,
            'new_status' => $new->value,
            'reason' => $reason,
            'changed_by' => $changedBy,
            'changed_at' => now(),
        ]);
    }
}
