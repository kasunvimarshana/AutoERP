<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Services\Validation;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;
use Modules\Vehicle\Enums\VehicleOwnerType;
use Modules\Vehicle\Models\VehicleOwnership;
use Modules\VehicleRental\DTOs\RentalAssignmentData;
use Modules\VehicleRental\Enums\RentalAgreementKind;
use Modules\VehicleRental\Enums\RentalAgreementStatus;
use Modules\VehicleRental\Enums\RentalAssignmentSide;
use Modules\VehicleRental\Enums\RentalAssignmentStatus;
use Modules\VehicleRental\Models\RentalAgreement;
use Modules\VehicleRental\Models\RentalAssignment;

final class RentalAssignmentSourceGuard
{
    public function assertAgreementSupportsAssignment(
        RentalAgreement $agreement,
        RentalAssignmentData $data,
        CarbonImmutable $startsAt,
        ?CarbonImmutable $endsAt,
    ): void {
        if ($agreement->status !== RentalAgreementStatus::Active) {
            throw new InvalidArgumentException('Only active rental agreements can receive vehicle assignments.');
        }
        $expectedKind = $data->side === RentalAssignmentSide::CustomerUse
            ? RentalAgreementKind::Customer
            : RentalAgreementKind::Owner;
        if ($agreement->kind !== $expectedKind) {
            throw new InvalidArgumentException('Vehicle assignment side does not match the selected agreement kind.');
        }

        $enteredStartsAt = CarbonImmutable::parse($data->startsAt);
        $enteredEndsAt = $data->endsAt === null ? null : CarbonImmutable::parse($data->endsAt);
        if ($enteredStartsAt->toDateString() < $agreement->starts_on->toDateString()) {
            throw new InvalidArgumentException('Vehicle assignment cannot start before the agreement period.');
        }
        if ($agreement->ends_on !== null
            && ($enteredEndsAt === null || $enteredEndsAt->toDateString() > $agreement->ends_on->toDateString())) {
            throw new InvalidArgumentException('Vehicle assignment must end within the agreement period.');
        }
        if ($data->selfDrive && $data->driverEmployeeId !== null) {
            throw new InvalidArgumentException('Self-drive assignments cannot also have an assigned driver.');
        }
    }

    public function sourceAssignmentForPlanning(
        RentalAssignmentData $data,
        CarbonImmutable $startsAt,
        ?CarbonImmutable $endsAt,
    ): ?RentalAssignment {
        $source = $this->sourceAssignment($data);
        if (! $source instanceof RentalAssignment) {
            return null;
        }
        if (! in_array($source->status, [RentalAssignmentStatus::Planned, RentalAssignmentStatus::Active], true)) {
            throw new InvalidArgumentException('Customer assignment source must be planned or active.');
        }
        if (! $this->periodContainsCompletePeriod($source, $startsAt, $endsAt)) {
            throw new InvalidArgumentException('Owner-supply assignment must cover the complete customer-use assignment period.');
        }

        return $source;
    }

    public function sourceAssignmentForOperation(
        RentalAssignmentData $data,
        CarbonImmutable $startsAt,
        ?CarbonImmutable $endsAt,
    ): ?RentalAssignment {
        $source = $this->sourceAssignment($data);
        if (! $source instanceof RentalAssignment) {
            return null;
        }
        if ($source->status !== RentalAssignmentStatus::Active) {
            throw new InvalidArgumentException(
                'Owner-supply source must be active before customer vehicle handover or replacement.',
            );
        }
        if (! $this->periodContainsCompletePeriod($source, $startsAt, $endsAt)) {
            throw new InvalidArgumentException(
                'Active owner-supply assignment must cover the complete customer-use operational period.',
            );
        }

        return $source;
    }

    public function assertOwnershipSource(
        RentalAgreement $agreement,
        RentalAssignmentData $data,
        ?RentalAssignment $source,
        CarbonImmutable $startsAt,
        ?CarbonImmutable $endsAt,
    ): void {
        if ($data->side === RentalAssignmentSide::CustomerUse && $source instanceof RentalAssignment) {
            return;
        }
        $ownerType = $data->side === RentalAssignmentSide::OwnerSupply
            ? VehicleOwnerType::Supplier
            : VehicleOwnerType::Company;
        $ownerId = $ownerType === VehicleOwnerType::Supplier ? (int) $agreement->supplier_id : null;
        $covers = VehicleOwnership::query()
            ->where('tenant_id', $data->tenantId)
            ->where('vehicle_id', $data->vehicleId)
            ->where('owner_type', $ownerType->value)
            ->when($ownerId !== null, fn (Builder $query) => $query->where('owner_id', $ownerId))
            ->where('started_at', '<=', $startsAt->toDateTimeString())
            ->where(function (Builder $query) use ($endsAt): void {
                $query->whereNull('ended_at');
                if ($endsAt !== null) {
                    $query->orWhere('ended_at', '>=', $endsAt->toDateTimeString());
                }
            })
            ->orderBy('id')
            ->lockForUpdate()
            ->exists();
        if (! $covers) {
            $message = $ownerType === VehicleOwnerType::Supplier
                ? 'The selected vehicle is not registered to the owner-agreement supplier for the complete assignment period. Add or correct the Supplier Vehicle relationship before continuing.'
                : 'A customer-use assignment without an owner source requires company ownership for the full period.';
            throw new InvalidArgumentException($message);
        }
    }

    public function assertNoActiveDependents(RentalAssignment $assignment): void
    {
        $hasDependents = RentalAssignment::query()
            ->forContext((int) $assignment->tenant_id, $assignment->organization_unit_id)
            ->where('source_assignment_id', $assignment->getKey())
            ->whereIn('status', RentalAssignmentTimelineGuard::OVERLAP_STATUSES)
            ->orderBy('id')
            ->lockForUpdate()
            ->exists();
        if ($hasDependents) {
            throw new InvalidArgumentException(
                'Replace, return, or cancel dependent customer vehicle assignments before changing this owner-supply assignment.',
            );
        }
    }

    private function sourceAssignment(RentalAssignmentData $data): ?RentalAssignment
    {
        if ($data->sourceAssignmentId === null) {
            return null;
        }
        $source = RentalAssignment::query()
            ->forContext($data->tenantId, $data->organizationUnitId)
            ->lockForUpdate()
            ->findOrFail($data->sourceAssignmentId);
        if ($source->side !== RentalAssignmentSide::OwnerSupply
            || (int) $source->vehicle_id !== $data->vehicleId) {
            throw new InvalidArgumentException(
                'Customer assignment source must be an owner-supply assignment for the same vehicle.',
            );
        }

        return $source;
    }

    private function periodContainsCompletePeriod(
        RentalAssignment $assignment,
        CarbonImmutable $startsAt,
        ?CarbonImmutable $endsAt,
    ): bool {
        if (CarbonImmutable::instance($assignment->starts_at)->gt($startsAt)) {
            return false;
        }
        if ($assignment->ends_at === null) {
            return true;
        }

        return $endsAt !== null && CarbonImmutable::instance($assignment->ends_at)->gte($endsAt);
    }
}
