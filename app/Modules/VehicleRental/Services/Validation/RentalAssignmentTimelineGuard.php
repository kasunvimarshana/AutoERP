<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Services\Validation;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Modules\Hr\Models\HrEmployee;
use Modules\Vehicle\Models\Vehicle;
use Modules\Vehicle\Services\VehicleAvailabilityService;
use Modules\VehicleRental\DTOs\RentalAssignmentData;
use Modules\VehicleRental\Enums\RentalAssignmentSide;
use Modules\VehicleRental\Enums\RentalAssignmentStatus;
use Modules\VehicleRental\Models\RentalAssignment;

final class RentalAssignmentTimelineGuard
{
    private const OPEN_ENDED_AT = '9999-12-31 23:59:59';

    public const OVERLAP_STATUSES = [
        RentalAssignmentStatus::Planned->value,
        RentalAssignmentStatus::Active->value,
    ];

    public function __construct(private readonly VehicleAvailabilityService $vehicleAvailability) {}

    public function dateTime(string $value): CarbonImmutable
    {
        return CarbonImmutable::parse($value)->seconds(0);
    }

    public function assertExpectedVersion(RentalAssignment $assignment, int $expectedVersion): void
    {
        if ((int) $assignment->row_version !== $expectedVersion) {
            throw new InvalidArgumentException('Rental assignment was changed by another request. Reload it before continuing.');
        }
    }

    /** @param list<int> $vehicleIds */
    public function lockVehicles(int $tenantId, ?int $organizationUnitId, array $vehicleIds): void
    {
        $vehicleIds = array_values(array_unique($vehicleIds));
        sort($vehicleIds, SORT_NUMERIC);
        $vehicles = Vehicle::query()
            ->forTenant($tenantId, $organizationUnitId)
            ->whereIn('id', $vehicleIds)
            ->orderBy('id')
            ->lockForUpdate()
            ->get();
        if ($vehicles->count() !== count($vehicleIds)) {
            throw new InvalidArgumentException('One or more selected vehicles were not found in the active scope.');
        }
    }

    public function assertAvailable(
        int $tenantId,
        ?int $organizationUnitId,
        int $vehicleId,
        CarbonImmutable $startsAt,
        ?CarbonImmutable $endsAt,
    ): void {
        $this->vehicleAvailability->assertAvailable(
            $tenantId,
            $organizationUnitId,
            $vehicleId,
            $startsAt->toDateTimeString(),
            $endsAt?->toDateTimeString(),
        );
    }

    /** @param list<int> $vehicleIds */
    public function lockVehicleTimeline(
        int $tenantId,
        ?int $organizationUnitId,
        array $vehicleIds,
        RentalAssignmentSide $side,
    ): Collection {
        $vehicleIds = array_values(array_unique($vehicleIds));
        sort($vehicleIds, SORT_NUMERIC);

        return RentalAssignment::query()
            ->forContext($tenantId, $organizationUnitId)
            ->whereIn('vehicle_id', $vehicleIds)
            ->where('side', $side->value)
            ->orderBy('vehicle_id')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();
    }

    public function assertNoVehicleOverlap(
        iterable $timeline,
        CarbonImmutable $startsAt,
        ?CarbonImmutable $endsAt,
        ?int $ignoreAssignmentId = null,
    ): void {
        foreach ($timeline as $existing) {
            if ($ignoreAssignmentId !== null && (int) $existing->getKey() === $ignoreAssignmentId) {
                continue;
            }
            if (! in_array($existing->status->value, self::OVERLAP_STATUSES, true)) {
                continue;
            }
            if ($this->overlaps(
                $startsAt,
                $endsAt,
                CarbonImmutable::instance($existing->starts_at),
                $existing->ends_at === null ? null : CarbonImmutable::instance($existing->ends_at),
            )) {
                throw new InvalidArgumentException('The selected vehicle already has an overlapping active rental assignment.');
            }
        }
    }

    public function assertDriverAvailable(
        RentalAssignmentData $data,
        CarbonImmutable $startsAt,
        ?CarbonImmutable $endsAt,
        ?int $ignoreAssignmentId = null,
    ): void {
        if ($data->driverEmployeeId === null) {
            return;
        }

        HrEmployee::query()
            ->forTenant($data->tenantId, $data->organizationUnitId)
            ->active()
            ->lockForUpdate()
            ->findOrFail($data->driverEmployeeId);

        $query = RentalAssignment::query()
            ->forContext($data->tenantId, $data->organizationUnitId)
            ->where('driver_employee_id', $data->driverEmployeeId)
            ->whereIn('status', self::OVERLAP_STATUSES)
            ->where('starts_at', '<', ($endsAt ?? CarbonImmutable::parse(self::OPEN_ENDED_AT))->toDateTimeString())
            ->where(function (Builder $query) use ($startsAt): void {
                $query->whereNull('ends_at')->orWhere('ends_at', '>', $startsAt->toDateTimeString());
            })
            ->orderBy('id')
            ->lockForUpdate();
        if ($ignoreAssignmentId !== null) {
            $query->whereKeyNot($ignoreAssignmentId);
        }
        if ($data->side === RentalAssignmentSide::CustomerUse && $data->sourceAssignmentId !== null) {
            $query->whereKeyNot($data->sourceAssignmentId);
        }
        if ($data->side === RentalAssignmentSide::OwnerSupply && $ignoreAssignmentId !== null) {
            $query->where(function (Builder $scope) use ($ignoreAssignmentId): void {
                $scope->where('side', '!=', RentalAssignmentSide::CustomerUse->value)
                    ->orWhereNull('source_assignment_id')
                    ->orWhere('source_assignment_id', '!=', $ignoreAssignmentId);
            });
        }

        $conflict = $query
            ->with('agreement:id,agreement_number')
            ->first();
        if ($conflict instanceof RentalAssignment) {
            throw new InvalidArgumentException($this->driverConflictMessage($conflict));
        }
    }

    private function driverConflictMessage(RentalAssignment $conflict): string
    {
        $reference = $conflict->agreement?->agreement_number ?? 'Assignment #'.$conflict->getKey();
        $side = str_replace('_', ' ', $conflict->side->value);
        $status = $conflict->status->value;
        $startsAt = $conflict->starts_at->toDateTimeString();
        $endsAt = $conflict->ends_at?->toDateTimeString() ?? 'open-ended';

        return "The selected driver already has an overlapping rental assignment: {$reference} ({$side}, {$status}) from {$startsAt} to {$endsAt}.";
    }

    private function overlaps(
        CarbonImmutable $leftStart,
        ?CarbonImmutable $leftEnd,
        CarbonImmutable $rightStart,
        ?CarbonImmutable $rightEnd,
    ): bool {
        $openEnd = CarbonImmutable::parse(self::OPEN_ENDED_AT);

        return $leftStart->lt($rightEnd ?? $openEnd)
            && $rightStart->lt($leftEnd ?? $openEnd);
    }
}
