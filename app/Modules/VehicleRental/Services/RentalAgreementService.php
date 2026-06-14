<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Services;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Customer\Enums\CustomerStatus;
use Modules\Customer\Models\Customer;
use Modules\Sequence\Services\Sequences\GenerateSequenceNumberService;
use Modules\Supplier\Enums\SupplierStatus;
use Modules\Supplier\Models\Supplier;
use Modules\Tax\Models\TaxGroup;
use Modules\Vehicle\Enums\VehicleStatus;
use Modules\Vehicle\Services\VehicleStatusService;
use Modules\VehicleRental\DTOs\RentalAgreementData;
use Modules\VehicleRental\DTOs\RentalAgreementVehicleData;
use Modules\VehicleRental\DTOs\RentalRateSnapshotData;
use Modules\VehicleRental\Enums\RentalAgreementDirection;
use Modules\VehicleRental\Enums\RentalAgreementStatus;
use Modules\VehicleRental\Enums\RentalAgreementVehicleLinkStatus;
use Modules\VehicleRental\Enums\RentalAgreementVehicleStatus;
use Modules\VehicleRental\Enums\RentalPartyType;
use Modules\VehicleRental\Enums\RentalReservationStatus;
use Modules\VehicleRental\Models\RentalAgreement;
use Modules\VehicleRental\Models\RentalAgreementRateSnapshot;
use Modules\VehicleRental\Models\RentalAgreementVehicle;
use Modules\VehicleRental\Models\RentalAgreementVehicleLink;
use Modules\VehicleRental\Models\RentalReservation;
use Modules\VehicleRental\Models\RentalStatusHistory;
use RuntimeException;

final class RentalAgreementService
{
    /** @var array<string, list<string>> */
    private const TRANSITIONS = [
        'draft' => ['confirmed', 'cancelled'],
        'confirmed' => ['active', 'cancelled'],
        'active' => ['returned', 'cancelled'],
        'returned' => ['completed'],
        'completed' => [],
        'cancelled' => [],
    ];

    public function __construct(
        private readonly DecimalMath $math,
        private readonly GenerateSequenceNumberService $sequences,
        private readonly RentalAgreementVehicleService $agreementVehicles,
        private readonly RentalAvailabilityService $availability,
        private readonly VehicleStatusService $vehicleStatuses,
    ) {}

    public function create(RentalAgreementData $data): RentalAgreement
    {
        return DB::transaction(function () use ($data): RentalAgreement {
            $this->validate($data);
            $reservation = $this->reservation($data);

            $agreement = RentalAgreement::query()->create([
                'tenant_id' => $data->tenantId,
                'organization_unit_id' => $data->organizationUnitId,
                'agreement_number' => $data->agreementNumber ?? $this->nextNumber($data),
                'reservation_id' => $data->reservationId,
                'direction' => $data->direction->value,
                'party_type' => $data->partyType->value,
                'party_id' => $data->partyId,
                'rental_type' => $data->rentalType->value,
                'billing_cycle' => $data->billingCycle->value,
                'agreement_date' => $data->agreementDate,
                'start_at' => $data->startAt,
                'expected_end_at' => $data->expectedEndAt,
                'currency_id' => $data->currencyId,
                'status' => RentalAgreementStatus::Draft->value,
                'terms_snapshot' => $data->termsSnapshot,
                'remarks' => $data->remarks,
                'created_by' => $data->createdBy,
            ]);
            $this->createRateSnapshot($agreement, $data->rateSnapshot);
            $this->recordStatus($agreement, null, RentalAgreementStatus::Draft, $data->createdBy);

            if ($reservation !== null) {
                if ($reservation->vehicle_id !== null) {
                    $vehicle = $reservation->vehicle()->firstOrFail();
                    $this->agreementVehicles->allocate($agreement, new RentalAgreementVehicleData(
                        vehicleId: (int) $reservation->vehicle_id,
                        allocatedFrom: $data->startAt,
                        allocatedTo: $data->expectedEndAt,
                        startOdometer: (string) $vehicle->odometer_reading,
                        ownerPartyType: $data->direction === RentalAgreementDirection::Inbound ? $data->partyType : null,
                        ownerPartyId: $data->direction === RentalAgreementDirection::Inbound ? $data->partyId : null,
                    ));
                }
                $old = $reservation->status;
                $reservation->forceFill([
                    'status' => RentalReservationStatus::Converted->value,
                    'updated_by' => $data->createdBy,
                ])->save();
                RentalStatusHistory::query()->create([
                    'tenant_id' => $reservation->tenant_id,
                    'organization_unit_id' => $reservation->organization_unit_id,
                    'reservation_id' => $reservation->getKey(),
                    'entity_type' => 'reservation',
                    'subject_id' => $reservation->getKey(),
                    'old_status' => $old->value,
                    'new_status' => RentalReservationStatus::Converted->value,
                    'changed_by' => $data->createdBy,
                    'changed_at' => now(),
                ]);
            }

            return $agreement->load($this->relations());
        });
    }

    public function changeStatus(
        RentalAgreement $agreement,
        RentalAgreementStatus $status,
        ?int $changedBy = null,
        ?string $reason = null,
    ): RentalAgreement {
        return DB::transaction(function () use ($agreement, $status, $changedBy, $reason): RentalAgreement {
            $agreement = RentalAgreement::query()->lockForUpdate()->findOrFail($agreement->getKey());
            $old = $agreement->status;
            if ($old === $status) {
                return $agreement;
            }
            if (! in_array($status->value, self::TRANSITIONS[$old->value] ?? [], true)) {
                throw new InvalidArgumentException(
                    "Invalid rental agreement status transition from {$old->value} to {$status->value}.",
                );
            }
            $allocations = $agreement->vehicles()
                ->with(['vehicle', 'pickupInspection', 'returnInspection'])
                ->lockForUpdate()
                ->get();
            $agreement->setRelation('vehicles', $allocations);
            if ($status === RentalAgreementStatus::Confirmed) {
                foreach ($agreement->vehicles as $allocation) {
                    $this->availability->assertAvailable(
                        (int) $agreement->tenant_id,
                        $agreement->organization_unit_id,
                        (int) $allocation->vehicle_id,
                        $allocation->allocated_from->toDateTimeString(),
                        ($allocation->allocated_to ?? $agreement->expected_end_at)->toDateTimeString(),
                        excludeAgreementId: (int) $agreement->getKey(),
                        excludeReservationId: $agreement->reservation_id,
                        direction: $agreement->direction,
                    );
                    if ($allocation->vehicle?->status === VehicleStatus::Active) {
                        $this->vehicleStatuses->changeTo(
                            $allocation->vehicle,
                            VehicleStatus::Reserved,
                            $changedBy,
                            'Reserved for rental agreement '.$agreement->agreement_number,
                        );
                    }
                }
            }
            if ($status === RentalAgreementStatus::Active) {
                $activeAllocations = $agreement->vehicles->whereNotIn('status', [
                    RentalAgreementVehicleStatus::Replaced,
                    RentalAgreementVehicleStatus::Returned,
                ]);
                if ($activeAllocations->isEmpty()) {
                    throw new InvalidArgumentException('At least one vehicle must be allocated before activating an agreement.');
                }
                if ($activeAllocations->contains(fn ($allocation): bool => $allocation->pickupInspection === null)) {
                    throw new InvalidArgumentException('Pickup inspection is required for every active rental vehicle.');
                }
                foreach ($activeAllocations as $allocation) {
                    $this->assertOppositeAllocationIsLinked($agreement, $allocation);
                    if ($allocation->vehicle?->status === VehicleStatus::Reserved) {
                        $this->vehicleStatuses->changeTo(
                            $allocation->vehicle,
                            VehicleStatus::Rented,
                            $changedBy,
                            'Activated for rental agreement '.$agreement->agreement_number,
                        );
                    }
                    $allocation->forceFill(['status' => RentalAgreementVehicleStatus::Active->value])->save();
                }
            }
            if ($status === RentalAgreementStatus::Returned) {
                $outstanding = $agreement->vehicles
                    ->where('status', RentalAgreementVehicleStatus::Active)
                    ->contains(fn ($allocation): bool => $allocation->returnInspection === null);
                if ($outstanding) {
                    throw new InvalidArgumentException('Return inspection is required for every active rental vehicle.');
                }
                $agreement->actual_end_at ??= now();
            }
            if ($status === RentalAgreementStatus::Cancelled) {
                if ($agreement->operationalUsageLogs()
                    ->where('status', 'approved')
                    ->exists()
                    || $agreement->charges()->where('status', 'approved')->exists()
                    || $agreement->invoiceLinks()->exists()
                    || $agreement->paymentLinks()->exists()) {
                    throw new InvalidArgumentException(
                        'A financially or operationally approved agreement cannot be cancelled; use reversal or correction.',
                    );
                }
                foreach ($agreement->vehicles as $allocation) {
                    if (in_array($allocation->vehicle?->status, [VehicleStatus::Reserved, VehicleStatus::Rented], true)) {
                        $hasOtherBlockingAllocation = RentalAgreementVehicle::query()
                            ->where('vehicle_id', $allocation->vehicle_id)
                            ->where('agreement_id', '!=', $agreement->getKey())
                            ->whereNotIn('status', [
                                RentalAgreementVehicleStatus::Replaced->value,
                                RentalAgreementVehicleStatus::Returned->value,
                            ])
                            ->whereHas('agreement', fn (Builder $query) => $query->whereIn('status', [
                                RentalAgreementStatus::Confirmed->value,
                                RentalAgreementStatus::Active->value,
                            ]))
                            ->exists();
                        if (! $hasOtherBlockingAllocation) {
                            $this->vehicleStatuses->changeTo(
                                $allocation->vehicle,
                                VehicleStatus::Active,
                                $changedBy,
                                'Rental agreement '.$agreement->agreement_number.' cancelled',
                            );
                        }
                    }
                }
            }

            $agreement->status = $status;
            $agreement->updated_by = $changedBy;
            $agreement->save();
            $this->recordStatus($agreement, $old, $status, $changedBy, $reason);

            return $agreement->refresh()->load($this->relations());
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
        $query = RentalAgreement::query()
            ->forContext($tenantId, $organizationUnitId)
            ->with(['customer', 'supplier', 'vehicles.vehicle.make', 'vehicles.vehicle.model', 'rateSnapshot']);
        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function (Builder $scope) use ($search): void {
                $scope->where('agreement_number', 'like', "%{$search}%")
                    ->orWhereHas('vehicles.vehicle', fn (Builder $vehicle) => $vehicle
                        ->where('vehicle_number', 'like', "%{$search}%")
                        ->orWhere('registration_number', 'like', "%{$search}%"));
            });
        }
        foreach (['status', 'direction', 'party_type', 'party_id', 'rental_type', 'billing_cycle'] as $filter) {
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
        if (($filters['overdue'] ?? false) === true || ($filters['overdue'] ?? null) === '1') {
            $query->whereIn('status', [
                RentalAgreementStatus::Confirmed->value,
                RentalAgreementStatus::Active->value,
            ])->where('expected_end_at', '<', now());
        }

        return $query->latest('agreement_date')->latest('id')->paginate($perPage);
    }

    /**
     * @return list<string>
     */
    public function relations(): array
    {
        return [
            'reservation',
            'customer',
            'supplier',
            'rateSnapshot',
            'vehicles.vehicle.make',
            'vehicles.vehicle.model',
            'vehicles.pickupInspection',
            'vehicles.returnInspection',
            'operationalUsageLogs.driver',
            'operationalUsageLogs.events',
            'operationalUsageLogs.contexts.agreement.customer',
            'operationalUsageLogs.contexts.agreement.supplier',
            'operationalUsageLogs.contexts.rateSnapshot',
            'expenses',
            'charges',
            'inboundVehicleLinks.inboundAgreement.supplier',
            'inboundVehicleLinks.outboundAgreement.customer',
            'inboundVehicleLinks.vehicle.make',
            'inboundVehicleLinks.vehicle.model',
            'outboundVehicleLinks.inboundAgreement.supplier',
            'outboundVehicleLinks.outboundAgreement.customer',
            'outboundVehicleLinks.vehicle.make',
            'outboundVehicleLinks.vehicle.model',
            'invoiceLinks.invoice.balance',
            'paymentLinks.payment',
            'paymentLinks.invoice',
        ];
    }

    private function validate(RentalAgreementData $data): void
    {
        $start = CarbonImmutable::parse($data->startAt);
        $end = CarbonImmutable::parse($data->expectedEndAt);
        if ($end->lessThanOrEqualTo($start)) {
            throw new InvalidArgumentException('Agreement end date and time must be after the start date and time.');
        }
        $this->party($data);
        $this->validateRateSnapshot($data, $data->rateSnapshot);
    }

    private function party(RentalAgreementData $data): void
    {
        if ($data->direction === RentalAgreementDirection::Outbound
            && $data->partyType !== RentalPartyType::Customer) {
            throw new InvalidArgumentException('Outbound rental agreements require a customer party.');
        }
        if ($data->direction === RentalAgreementDirection::Inbound
            && ! in_array($data->partyType, [RentalPartyType::Supplier, RentalPartyType::Owner], true)) {
            throw new InvalidArgumentException('Inbound hire-in agreements require a supplier or owner party.');
        }

        if ($data->partyType === RentalPartyType::Customer) {
            $party = Customer::query()->where('tenant_id', $data->tenantId)
                ->where(fn (Builder $query) => $query->whereNull('organization_unit_id')
                    ->when($data->organizationUnitId !== null, fn (Builder $inner) => $inner->orWhere('organization_unit_id', $data->organizationUnitId)))
                ->findOrFail($data->partyId);
            if ($party->status !== CustomerStatus::Active) {
                throw new InvalidArgumentException('Only active customers can be used for rental agreements.');
            }

            return;
        }

        $party = Supplier::query()->where('tenant_id', $data->tenantId)
            ->where(fn (Builder $query) => $query->whereNull('organization_unit_id')
                ->when($data->organizationUnitId !== null, fn (Builder $inner) => $inner->orWhere('organization_unit_id', $data->organizationUnitId)))
            ->findOrFail($data->partyId);
        if ($party->status !== SupplierStatus::Active) {
            throw new InvalidArgumentException('Only active suppliers or owners can be used for hire-in agreements.');
        }
    }

    private function reservation(RentalAgreementData $data): ?RentalReservation
    {
        if ($data->reservationId === null) {
            return null;
        }
        $reservation = RentalReservation::query()
            ->forContext($data->tenantId, $data->organizationUnitId)
            ->lockForUpdate()
            ->findOrFail($data->reservationId);
        if ($reservation->status !== RentalReservationStatus::Confirmed) {
            throw new InvalidArgumentException('Only confirmed reservations can be converted to agreements.');
        }
        if ($reservation->agreement()->exists()) {
            throw new InvalidArgumentException('Reservation has already been converted to an agreement.');
        }
        if ($reservation->direction !== $data->direction
            || $reservation->party_type !== $data->partyType
            || (int) $reservation->party_id !== $data->partyId) {
            throw new InvalidArgumentException('Agreement party and direction must match the reservation.');
        }

        return $reservation;
    }

    private function validateRateSnapshot(RentalAgreementData $data, RentalRateSnapshotData $rate): void
    {
        foreach ([
            $rate->baseRate, $rate->allowedHours, $rate->allowedKm, $rate->extraHourRate,
            $rate->extraKmRate, $rate->overtimeRate, $rate->doubleOvertimeRate,
            $rate->nightShiftRate, $rate->weekendRate, $rate->holidayRate, $rate->driverRate,
            $rate->outstationRate, $rate->dayOutRate, $rate->nightOutRate, $rate->fuelRate,
            $rate->waitingHourRate,
        ] as $value) {
            if ($this->math->isNegative($value)) {
                throw new InvalidArgumentException('Rental rate snapshot values cannot be negative.');
            }
        }
        if ($rate->taxProfileId !== null) {
            TaxGroup::query()
                ->where('tenant_id', $data->tenantId)
                ->where('organization_unit_id', $data->organizationUnitId)
                ->where('active', true)
                ->findOrFail($rate->taxProfileId);
        }
    }

    private function createRateSnapshot(RentalAgreement $agreement, RentalRateSnapshotData $rate): void
    {
        RentalAgreementRateSnapshot::query()->create([
            'tenant_id' => $agreement->tenant_id,
            'organization_unit_id' => $agreement->organization_unit_id,
            'agreement_id' => $agreement->getKey(),
            'base_rate' => $this->math->normalize($rate->baseRate),
            'rate_unit' => $rate->rateUnit->value,
            'allowed_hours' => $this->math->normalize($rate->allowedHours),
            'allowed_km' => $this->math->normalize($rate->allowedKm),
            'extra_hour_rate' => $this->math->normalize($rate->extraHourRate),
            'extra_km_rate' => $this->math->normalize($rate->extraKmRate),
            'overtime_rate' => $this->math->normalize($rate->overtimeRate),
            'double_overtime_rate' => $this->math->normalize($rate->doubleOvertimeRate),
            'night_shift_rate' => $this->math->normalize($rate->nightShiftRate),
            'weekend_rate' => $this->math->normalize($rate->weekendRate),
            'holiday_rate' => $this->math->normalize($rate->holidayRate),
            'driver_rate' => $this->math->normalize($rate->driverRate),
            'outstation_rate' => $this->math->normalize($rate->outstationRate),
            'day_out_rate' => $this->math->normalize($rate->dayOutRate),
            'night_out_rate' => $this->math->normalize($rate->nightOutRate),
            'fuel_rate' => $this->math->normalize($rate->fuelRate),
            'waiting_hour_rate' => $this->math->normalize($rate->waitingHourRate),
            'tax_profile_id' => $rate->taxProfileId,
            'currency_id' => $rate->currencyId ?? $agreement->currency_id,
        ]);
    }

    private function nextNumber(RentalAgreementData $data): string
    {
        $result = $this->sequences->execute([
            'tenant_id' => $data->tenantId,
            'organization_unit_id' => $data->organizationUnitId,
            'document_type' => 'rental_agreement',
            'period_type' => 'yearly',
            'at_date' => $data->agreementDate,
            'prefix' => 'RAGR-{PERIOD}-',
            'padding' => 6,
        ]);
        if ($result->isFailure()) {
            throw new RuntimeException($result->errorOrFail()->message);
        }

        return (string) $result->valueOrFail()['generated_number'];
    }

    private function recordStatus(
        RentalAgreement $agreement,
        ?RentalAgreementStatus $old,
        RentalAgreementStatus $new,
        ?int $changedBy,
        ?string $reason = null,
    ): void {
        RentalStatusHistory::query()->create([
            'tenant_id' => $agreement->tenant_id,
            'organization_unit_id' => $agreement->organization_unit_id,
            'agreement_id' => $agreement->getKey(),
            'entity_type' => 'agreement',
            'subject_id' => $agreement->getKey(),
            'old_status' => $old?->value,
            'new_status' => $new->value,
            'reason' => $reason,
            'changed_by' => $changedBy,
            'changed_at' => now(),
        ]);
    }

    private function assertOppositeAllocationIsLinked(
        RentalAgreement $agreement,
        RentalAgreementVehicle $allocation,
    ): void {
        $oppositeDirection = $agreement->direction === RentalAgreementDirection::Outbound
            ? RentalAgreementDirection::Inbound
            : RentalAgreementDirection::Outbound;
        $allocationEnd = $allocation->allocated_to ?? $agreement->expected_end_at;
        $oppositeAllocations = RentalAgreementVehicle::query()
            ->forContext((int) $agreement->tenant_id, $agreement->organization_unit_id)
            ->where('vehicle_id', $allocation->vehicle_id)
            ->whereKeyNot($allocation->getKey())
            ->where('allocated_from', '<', $allocationEnd)
            ->where(fn (Builder $query) => $query
                ->whereNull('allocated_to')
                ->orWhere('allocated_to', '>', $allocation->allocated_from))
            ->whereHas('agreement', fn (Builder $query) => $query
                ->where('direction', $oppositeDirection->value)
                ->whereIn('status', [
                    RentalAgreementStatus::Confirmed->value,
                    RentalAgreementStatus::Active->value,
                ]))
            ->with('agreement')
            ->lockForUpdate()
            ->get();

        foreach ($oppositeAllocations as $opposite) {
            $from = $allocation->allocated_from->greaterThan($opposite->allocated_from)
                ? $allocation->allocated_from
                : $opposite->allocated_from;
            $oppositeEnd = $opposite->allocated_to ?? $opposite->agreement?->expected_end_at;
            $to = $allocationEnd->lessThan($oppositeEnd) ? $allocationEnd : $oppositeEnd;
            $inboundId = $agreement->direction === RentalAgreementDirection::Inbound
                ? $allocation->getKey()
                : $opposite->getKey();
            $outboundId = $agreement->direction === RentalAgreementDirection::Outbound
                ? $allocation->getKey()
                : $opposite->getKey();
            $linked = RentalAgreementVehicleLink::query()
                ->forContext((int) $agreement->tenant_id, $agreement->organization_unit_id)
                ->where('inbound_agreement_vehicle_id', $inboundId)
                ->where('outbound_agreement_vehicle_id', $outboundId)
                ->where('status', RentalAgreementVehicleLinkStatus::Approved->value)
                ->where('effective_from', '<=', $from)
                ->where('effective_to', '>=', $to)
                ->lockForUpdate()
                ->exists();
            if (! $linked) {
                throw new InvalidArgumentException(
                    'An overlapping opposite-direction allocation must have an approved allocation link before activation.',
                );
            }
        }
    }
}
