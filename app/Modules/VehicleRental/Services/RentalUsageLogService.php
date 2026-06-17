<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Services;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Hr\Enums\EmployeeStatus;
use Modules\Hr\Models\HrEmployee;
use Modules\Vehicle\Models\Vehicle;
use Modules\VehicleRental\DTOs\RentalUsageLogData;
use Modules\VehicleRental\Enums\RentalAgreementStatus;
use Modules\VehicleRental\Enums\RentalUsageEventType;
use Modules\VehicleRental\Enums\RentalUsageLogStatus;
use Modules\VehicleRental\Models\RentalAgreement;
use Modules\VehicleRental\Models\RentalAgreementVehicle;
use Modules\VehicleRental\Models\RentalStatusHistory;
use Modules\VehicleRental\Models\RentalUsageLog;

final class RentalUsageLogService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly RentalUsageContextService $contexts,
    ) {}

    public function create(RentalAgreement $agreement, RentalUsageLogData $data): RentalUsageLog
    {
        return $this->createWithMode(null, $agreement, $data);
    }

    public function createForMode(
        string $mode,
        RentalAgreement $agreement,
        RentalUsageLogData $data,
        ?RentalAgreement $counterpartAgreement = null,
        ?int $counterpartAgreementVehicleId = null,
    ): RentalUsageLog {
        return $this->createWithMode(
            $mode,
            $agreement,
            $data,
            $counterpartAgreement,
            $counterpartAgreementVehicleId,
        );
    }

    private function createWithMode(
        ?string $mode,
        RentalAgreement $agreement,
        RentalUsageLogData $data,
        ?RentalAgreement $counterpartAgreement = null,
        ?int $counterpartAgreementVehicleId = null,
    ): RentalUsageLog
    {
        if (! in_array($agreement->status, [
            RentalAgreementStatus::Active,
            RentalAgreementStatus::Returned,
        ], true)) {
            throw new InvalidArgumentException('Usage logs require an active or returned agreement.');
        }

        return DB::transaction(function () use (
            $agreement,
            $data,
            $mode,
            $counterpartAgreement,
            $counterpartAgreementVehicleId,
        ): RentalUsageLog {
            $agreement = RentalAgreement::query()
                ->forContext((int) $agreement->tenant_id, $agreement->organization_unit_id)
                ->with('rateSnapshot')
                ->lockForUpdate()
                ->findOrFail($agreement->getKey());
            if (! in_array($agreement->status, [
                RentalAgreementStatus::Active,
                RentalAgreementStatus::Returned,
            ], true)) {
                throw new InvalidArgumentException('Usage logs require an active or returned agreement.');
            }
            $allocation = $agreement->vehicles()->with('pickupInspection')->lockForUpdate()
                ->findOrFail($data->agreementVehicleId);
            Vehicle::query()
                ->where('tenant_id', $agreement->tenant_id)
                ->whereKey($allocation->vehicle_id)
                ->lockForUpdate()
                ->firstOrFail();
            $this->validate($agreement, $allocation, $data);
            $counterpart = $this->lockedCounterpart(
                $agreement,
                $counterpartAgreement,
                $counterpartAgreementVehicleId,
            );
            $resolved = $this->resolveContexts(
                $mode,
                $agreement,
                $allocation,
                $data,
                $counterpart['agreement'],
                $counterpart['allocation'],
            );

            $distance = $this->math->sub($data->endOdometer, $data->startOdometer);
            $effectiveAt = CarbonImmutable::parse(
                $data->usageDate.' '.($data->startTime ?? '00:00:00'),
            );
            $operationalSequence = ((int) RentalUsageLog::query()
                ->where('vehicle_id', $allocation->vehicle_id)
                ->where('effective_at', $effectiveAt)
                ->max('operational_sequence')) + 1;
            $fingerprint = hash('sha256', implode('|', [
                (string) $agreement->tenant_id,
                (string) $allocation->vehicle_id,
                $data->usageDate,
                $data->startTime ?? '00:00',
                $data->endTime ?? '00:00',
                $this->math->normalize($data->startOdometer),
                $this->math->normalize($data->endOdometer),
            ]));
            $existing = RentalUsageLog::query()
                ->where('tenant_id', $agreement->tenant_id)
                ->where('usage_fingerprint', $fingerprint)
                ->lockForUpdate()
                ->first();
            if ($existing instanceof RentalUsageLog) {
                $this->assertIdempotentCreate($existing, $agreement, $allocation, $resolved);

                return $existing->load([
                    'vehicle.make',
                    'vehicle.model',
                    'driver',
                    'events',
                    'contexts.agreement.customer',
                    'contexts.agreement.supplier',
                    'contexts.rateSnapshot',
                ]);
            }
            $this->assertNoOverlappingUsage(
                null,
                (int) $agreement->tenant_id,
                $agreement->organization_unit_id,
                (int) $allocation->vehicle_id,
                $data,
            );
            $log = RentalUsageLog::query()->create([
                'tenant_id' => $agreement->tenant_id,
                'organization_unit_id' => $agreement->organization_unit_id,
                'agreement_id' => $agreement->getKey(),
                'agreement_vehicle_id' => $allocation->getKey(),
                'vehicle_id' => $allocation->vehicle_id,
                'driver_id' => $data->driverId,
                'usage_date' => $data->usageDate,
                'effective_at' => $effectiveAt,
                'operational_sequence' => $operationalSequence,
                'start_time' => $data->startTime,
                'end_time' => $data->endTime,
                'working_minutes' => $this->workingMinutes($data),
                'start_odometer' => $this->math->normalize($data->startOdometer),
                'end_odometer' => $this->math->normalize($data->endOdometer),
                'distance_km' => $distance,
                'cumulative_km' => null,
                'comparative_km' => $data->comparativeKm === null
                    ? null
                    : $this->math->normalize($data->comparativeKm),
                'usage_fingerprint' => $fingerprint,
                'odometer_variance_reason' => $data->odometerVarianceReason,
                'trip_from' => $data->tripFrom,
                'trip_to' => $data->tripTo,
                'trip_purpose' => $data->tripPurpose,
                'status' => RentalUsageLogStatus::Draft->value,
                'remarks' => $data->remarks,
                'created_by' => $data->createdBy,
                'updated_by' => $data->createdBy,
            ]);
            $this->contexts->attachResolved($log, $resolved);
            $this->recordStatus($log, null, RentalUsageLogStatus::Draft, $data->createdBy);

            return $log->load([
                'vehicle.make',
                'vehicle.model',
                'driver',
                'events',
                'contexts.agreement.customer',
                'contexts.agreement.supplier',
                'contexts.rateSnapshot',
            ]);
        });
    }

    public function update(RentalUsageLog $log, RentalUsageLogData $data): RentalUsageLog
    {
        return $this->updateWithMode($log, $data);
    }

    public function updateForMode(
        string $mode,
        RentalUsageLog $log,
        RentalUsageLogData $data,
        RentalAgreement $agreement,
        ?RentalAgreement $counterpartAgreement = null,
        ?int $counterpartAgreementVehicleId = null,
    ): RentalUsageLog {
        return $this->updateWithMode(
            $log,
            $data,
            $mode,
            $agreement,
            $counterpartAgreement,
            $counterpartAgreementVehicleId,
        );
    }

    private function updateWithMode(
        RentalUsageLog $log,
        RentalUsageLogData $data,
        ?string $mode = null,
        ?RentalAgreement $selectedAgreement = null,
        ?RentalAgreement $counterpartAgreement = null,
        ?int $counterpartAgreementVehicleId = null,
    ): RentalUsageLog
    {
        return DB::transaction(function () use (
            $log,
            $data,
            $mode,
            $selectedAgreement,
            $counterpartAgreement,
            $counterpartAgreementVehicleId,
        ): RentalUsageLog {
            $log = RentalUsageLog::query()
                ->with(['events', 'contexts'])
                ->lockForUpdate()
                ->findOrFail($log->getKey());
            $this->assertEditable($log);
            $agreementId = $selectedAgreement?->getKey() ?? $log->agreement_id;
            $agreement = RentalAgreement::query()
                ->forContext((int) $log->tenant_id, $log->organization_unit_id)
                ->with('rateSnapshot')
                ->lockForUpdate()
                ->findOrFail($agreementId);
            $allocation = $agreement->vehicles()->with('pickupInspection')->lockForUpdate()
                ->findOrFail($data->agreementVehicleId);
            Vehicle::query()
                ->where('tenant_id', $agreement->tenant_id)
                ->whereKey($allocation->vehicle_id)
                ->lockForUpdate()
                ->firstOrFail();
            $this->validate($agreement, $allocation, $data);
            $counterpart = $this->lockedCounterpart(
                $agreement,
                $counterpartAgreement,
                $counterpartAgreementVehicleId,
            );
            $resolved = $this->resolveContexts(
                $mode,
                $agreement,
                $allocation,
                $data,
                $counterpart['agreement'],
                $counterpart['allocation'],
            );

            $distance = $this->math->sub($data->endOdometer, $data->startOdometer);
            $effectiveAt = CarbonImmutable::parse(
                $data->usageDate.' '.($data->startTime ?? '00:00:00'),
            );
            $fingerprint = hash('sha256', implode('|', [
                (string) $agreement->tenant_id,
                (string) $allocation->vehicle_id,
                $data->usageDate,
                $data->startTime ?? '00:00',
                $data->endTime ?? '00:00',
                $this->math->normalize($data->startOdometer),
                $this->math->normalize($data->endOdometer),
            ]));
            $duplicate = RentalUsageLog::query()
                ->where('tenant_id', $agreement->tenant_id)
                ->where('usage_fingerprint', $fingerprint)
                ->whereKeyNot($log->getKey())
                ->lockForUpdate()
                ->first();
            if ($duplicate instanceof RentalUsageLog) {
                throw new InvalidArgumentException('This physical vehicle usage has already been recorded.');
            }
            $this->assertNoOverlappingUsage(
                $log,
                (int) $agreement->tenant_id,
                $agreement->organization_unit_id,
                (int) $allocation->vehicle_id,
                $data,
            );
            $log->forceFill([
                'agreement_id' => $agreement->getKey(),
                'agreement_vehicle_id' => $allocation->getKey(),
                'vehicle_id' => $allocation->vehicle_id,
                'driver_id' => $data->driverId,
                'usage_date' => $data->usageDate,
                'effective_at' => $effectiveAt,
                'start_time' => $data->startTime,
                'end_time' => $data->endTime,
                'working_minutes' => $this->workingMinutes($data),
                'start_odometer' => $this->math->normalize($data->startOdometer),
                'end_odometer' => $this->math->normalize($data->endOdometer),
                'distance_km' => $distance,
                'cumulative_km' => null,
                'comparative_km' => $data->comparativeKm === null
                    ? null
                    : $this->math->normalize($data->comparativeKm),
                'usage_fingerprint' => $fingerprint,
                'odometer_variance_reason' => $data->odometerVarianceReason,
                'trip_from' => $data->tripFrom,
                'trip_to' => $data->tripTo,
                'trip_purpose' => $data->tripPurpose,
                'status' => RentalUsageLogStatus::Draft->value,
                'submitted_by' => null,
                'submitted_at' => null,
                'rejected_by' => null,
                'rejected_at' => null,
                'remarks' => $data->remarks,
                'updated_by' => $data->createdBy,
            ])->save();
            $log->contexts()->lockForUpdate()->delete();
            $this->contexts->attachResolved($log, $resolved);

            return $log->refresh()->load([
                'vehicle.make',
                'vehicle.model',
                'driver',
                'events',
                'contexts.agreement.customer',
                'contexts.agreement.supplier',
                'contexts.rateSnapshot',
            ]);
        });
    }

    public function delete(RentalUsageLog $log): void
    {
        DB::transaction(function () use ($log): void {
            $log = RentalUsageLog::query()
                ->with(['events', 'expenses'])
                ->lockForUpdate()
                ->findOrFail($log->getKey());
            $this->assertEditable($log);
            foreach ($log->expenses as $expense) {
                if (! in_array($expense->status->value, ['draft', 'rejected'], true)) {
                    throw new InvalidArgumentException('Usage with submitted, approved, charged, or settled expenses cannot be deleted.');
                }
            }
            $log->events()->lockForUpdate()->delete();
            $log->expenses()->lockForUpdate()->delete();
            $log->contexts()->lockForUpdate()->delete();
            $log->delete();
        });
    }

    public function changeStatus(
        RentalUsageLog $log,
        RentalUsageLogStatus $status,
        ?int $changedBy = null,
        ?string $reason = null,
        bool $allowMileageVariance = false,
    ): RentalUsageLog {
        $allowed = [
            'draft' => ['submitted'],
            'submitted' => ['approved', 'rejected'],
            'approved' => [],
            'rejected' => ['draft'],
        ];

        return DB::transaction(function () use (
            $log,
            $status,
            $changedBy,
            $reason,
            $allowMileageVariance,
            $allowed,
        ): RentalUsageLog {
            $log = RentalUsageLog::query()
                ->with(['events', 'agreementVehicle.pickupInspection', 'contexts'])
                ->lockForUpdate()
                ->findOrFail($log->getKey());
            $old = $log->status;
            if ($old === $status) {
                return $log;
            }
            if (! in_array($status->value, $allowed[$old->value] ?? [], true)) {
                throw new InvalidArgumentException(
                    "Invalid usage log status transition from {$old->value} to {$status->value}.",
                );
            }

            $updates = ['status' => $status->value, 'updated_by' => $changedBy];
            if ($status === RentalUsageLogStatus::Submitted) {
                $this->assertNoOverlappingStoredUsage($log);
                $this->validateClassifiedTime($log);
                $updates['submitted_by'] = $changedBy;
                $updates['submitted_at'] = now();
            }
            if ($status === RentalUsageLogStatus::Approved) {
                $this->assertNoOverlappingStoredUsage($log);
                $cumulative = $this->approveMileage($log, $allowMileageVariance, $reason);
                $updates['cumulative_km'] = $cumulative;
                $updates['approved_by'] = $changedBy;
                $updates['approved_at'] = now();
                $updates['rejected_by'] = null;
                $updates['rejected_at'] = null;
            }
            if ($status === RentalUsageLogStatus::Rejected) {
                $updates['cumulative_km'] = null;
                $updates['rejected_by'] = $changedBy;
                $updates['rejected_at'] = now();
            }
            if ($status === RentalUsageLogStatus::Draft) {
                $updates['submitted_by'] = null;
                $updates['submitted_at'] = null;
                $updates['rejected_by'] = null;
                $updates['rejected_at'] = null;
            }

            $log->forceFill($updates)->save();
            $this->recordStatus($log, $old, $status, $changedBy, $reason);

            return $log->refresh()->load(['events', 'contexts.agreement', 'contexts.rateSnapshot']);
        });
    }

    private function approveMileage(
        RentalUsageLog $log,
        bool $allowMileageVariance,
        ?string $reason,
    ): string {
        $vehicle = Vehicle::query()
            ->where('tenant_id', $log->tenant_id)
            ->whereKey($log->vehicle_id)
            ->lockForUpdate()
            ->firstOrFail();
        $approved = RentalUsageLog::query()
            ->where('vehicle_id', $log->vehicle_id)
            ->where('status', RentalUsageLogStatus::Approved->value)
            ->whereKeyNot($log->getKey())
            ->orderBy('effective_at')
            ->orderBy('operational_sequence')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();
        $previous = $approved
            ->filter(fn (RentalUsageLog $row): bool => $this->compareMileageOrder($row, $log) < 0)
            ->last();
        $next = $approved
            ->first(fn (RentalUsageLog $row): bool => $this->compareMileageOrder($row, $log) > 0);
        $expectedStart = (string) ($previous?->end_odometer
            ?? $log->agreementVehicle?->pickupInspection?->odometer
            ?? $log->agreementVehicle?->start_odometer
            ?? '0.000000');
        $startMismatch = $this->math->compare((string) $log->start_odometer, $expectedStart) !== 0;
        $nextMismatch = $next !== null
            && $this->math->compare((string) $log->end_odometer, (string) $next->start_odometer) !== 0;
        if ($startMismatch || $nextMismatch) {
            if (! $allowMileageVariance || trim((string) $reason) === '') {
                if ($nextMismatch) {
                    throw new InvalidArgumentException(
                        'Running chart finish odometer must equal the next approved start odometer '
                        ."({$next->start_odometer}).",
                    );
                }
                throw new InvalidArgumentException(
                    "Running chart start odometer must equal the previous approved finish odometer ({$expectedStart}).",
                );
            }
            $log->odometer_variance_reason = $reason;
        }
        if ($this->math->compare((string) $log->end_odometer, (string) $vehicle->odometer_reading) > 0) {
            $vehicle->odometer_reading = $this->math->normalize((string) $log->end_odometer);
            $vehicle->save();
        }

        $cumulative = $this->math->add(
            (string) ($previous?->cumulative_km ?? '0.000000'),
            (string) $log->distance_km,
        );
        $running = $cumulative;
        foreach ($approved->filter(
            fn (RentalUsageLog $row): bool => $this->compareMileageOrder($row, $log) > 0,
        ) as $later) {
            $running = $this->math->add($running, (string) $later->distance_km);
            if ($this->math->compare((string) $later->cumulative_km, $running) !== 0) {
                $later->forceFill(['cumulative_km' => $running])->save();
            }
        }

        return $cumulative;
    }

    /**
     * @return array{agreement: RentalAgreement|null, allocation: RentalAgreementVehicle|null}
     */
    private function lockedCounterpart(
        RentalAgreement $selectedAgreement,
        ?RentalAgreement $counterpartAgreement,
        ?int $counterpartAgreementVehicleId,
    ): array {
        if ($counterpartAgreement === null && $counterpartAgreementVehicleId === null) {
            return ['agreement' => null, 'allocation' => null];
        }
        if ($counterpartAgreement === null || $counterpartAgreementVehicleId === null) {
            throw new InvalidArgumentException('Linked running charts require both counterpart agreement and allocation.');
        }

        $agreement = RentalAgreement::query()
            ->forContext((int) $selectedAgreement->tenant_id, $selectedAgreement->organization_unit_id)
            ->with('rateSnapshot')
            ->lockForUpdate()
            ->findOrFail($counterpartAgreement->getKey());
        $allocation = $agreement->vehicles()
            ->with('pickupInspection')
            ->lockForUpdate()
            ->findOrFail($counterpartAgreementVehicleId);
        Vehicle::query()
            ->where('tenant_id', $agreement->tenant_id)
            ->whereKey($allocation->vehicle_id)
            ->lockForUpdate()
            ->firstOrFail();

        return ['agreement' => $agreement, 'allocation' => $allocation];
    }

    /**
     * @return array{
     *   selected_agreement: RentalAgreement,
     *   selected_allocation: RentalAgreementVehicle,
     *   link: mixed,
     *   contexts: list<array{agreement: RentalAgreement, allocation: RentalAgreementVehicle}>
     * }
     */
    private function resolveContexts(
        ?string $mode,
        RentalAgreement $agreement,
        RentalAgreementVehicle $allocation,
        RentalUsageLogData $data,
        ?RentalAgreement $counterpartAgreement,
        ?RentalAgreementVehicle $counterpartAllocation,
    ): array {
        if ($mode === null) {
            return $this->contexts->resolve($agreement, $allocation, $data->usageDate, $data->startTime);
        }

        return $this->contexts->resolveForMode(
            $mode,
            $agreement,
            $allocation,
            $data->usageDate,
            $data->startTime,
            $counterpartAgreement,
            $counterpartAllocation,
        );
    }

    /**
     * @param  array{contexts: list<array{agreement: RentalAgreement, allocation: RentalAgreementVehicle}>}  $resolved
     */
    private function assertIdempotentCreate(
        RentalUsageLog $existing,
        RentalAgreement $agreement,
        RentalAgreementVehicle $allocation,
        array $resolved,
    ): void {
        $existingContextIds = $existing->contexts()
            ->orderBy('agreement_id')
            ->pluck('agreement_id')
            ->map(fn ($id): int => (int) $id)
            ->values()
            ->all();
        if ((int) $existing->agreement_id !== (int) $agreement->getKey()
            || (int) $existing->agreement_vehicle_id !== (int) $allocation->getKey()
            || $existingContextIds !== $this->resolvedAgreementIds($resolved)) {
            throw new InvalidArgumentException('This physical vehicle usage has already been recorded.');
        }
    }

    /**
     * @param  array{contexts: list<array{agreement: RentalAgreement, allocation: RentalAgreementVehicle}>}  $resolved
     * @return list<int>
     */
    private function resolvedAgreementIds(array $resolved): array
    {
        $ids = array_map(
            fn (array $row): int => (int) $row['agreement']->getKey(),
            $resolved['contexts'],
        );
        sort($ids);

        return array_values($ids);
    }

    private function assertNoOverlappingStoredUsage(RentalUsageLog $log): void
    {
        $data = new RentalUsageLogData(
            agreementVehicleId: (int) $log->agreement_vehicle_id,
            usageDate: $log->usage_date->toDateString(),
            startOdometer: (string) $log->start_odometer,
            endOdometer: (string) $log->end_odometer,
            driverId: $log->driver_id === null ? null : (int) $log->driver_id,
            startTime: $log->start_time,
            endTime: $log->end_time,
        );
        $this->assertNoOverlappingUsage(
            $log,
            (int) $log->tenant_id,
            $log->organization_unit_id,
            (int) $log->vehicle_id,
            $data,
        );
    }

    private function assertNoOverlappingUsage(
        ?RentalUsageLog $current,
        int $tenantId,
        ?int $organizationUnitId,
        int $vehicleId,
        RentalUsageLogData $data,
    ): void {
        $interval = $this->usageInterval($data);
        if ($interval === null) {
            return;
        }
        [$start, $end] = $interval;
        if (! $end->greaterThan($start)) {
            return;
        }

        $query = RentalUsageLog::query()
            ->where('tenant_id', $tenantId)
            ->where('organization_unit_id', $organizationUnitId)
            ->where('vehicle_id', $vehicleId)
            ->whereIn('status', [
                RentalUsageLogStatus::Draft->value,
                RentalUsageLogStatus::Submitted->value,
                RentalUsageLogStatus::Approved->value,
            ])
            ->whereBetween('effective_at', [$start->subDay(), $end->addDay()])
            ->lockForUpdate();
        if ($current instanceof RentalUsageLog) {
            $query->whereKeyNot($current->getKey());
        }

        foreach ($query->get() as $existing) {
            $existingInterval = $this->storedUsageInterval($existing);
            if ($existingInterval === null) {
                continue;
            }
            if ($this->intervalsOverlap($start, $end, $existingInterval[0], $existingInterval[1])) {
                throw new InvalidArgumentException('Usage time overlaps an existing running chart for this vehicle.');
            }
        }
    }

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}|null
     */
    private function usageInterval(RentalUsageLogData $data): ?array
    {
        if ($data->startTime === null || $data->endTime === null) {
            return null;
        }
        $start = CarbonImmutable::parse($data->usageDate.' '.$data->startTime);
        $end = CarbonImmutable::parse($data->usageDate.' '.$data->endTime);
        if ($end->lessThan($start)) {
            $end = $end->addDay();
        }

        return [$start, $end];
    }

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}|null
     */
    private function storedUsageInterval(RentalUsageLog $log): ?array
    {
        if ($log->start_time === null || $log->end_time === null) {
            return null;
        }
        $data = new RentalUsageLogData(
            agreementVehicleId: (int) $log->agreement_vehicle_id,
            usageDate: $log->usage_date->toDateString(),
            startOdometer: (string) $log->start_odometer,
            endOdometer: (string) $log->end_odometer,
            startTime: $log->start_time,
            endTime: $log->end_time,
        );

        return $this->usageInterval($data);
    }

    private function intervalsOverlap(
        CarbonImmutable $leftStart,
        CarbonImmutable $leftEnd,
        CarbonImmutable $rightStart,
        CarbonImmutable $rightEnd,
    ): bool {
        return $leftStart->lessThan($rightEnd) && $leftEnd->greaterThan($rightStart);
    }

    private function assertEditable(RentalUsageLog $log): void
    {
        if (! in_array($log->status, [RentalUsageLogStatus::Draft, RentalUsageLogStatus::Rejected], true)) {
            throw new InvalidArgumentException('Only draft or rejected running chart rows can be edited.');
        }
        if (DB::table('rental_charge_calculations')
            ->where('tenant_id', $log->tenant_id)
            ->where('organization_unit_id', $log->organization_unit_id)
            ->where('usage_log_id', $log->getKey())
            ->whereIn('status', ['draft', 'approved'])
            ->exists()) {
            throw new InvalidArgumentException('Charged running chart rows require a controlled correction or reversal.');
        }
    }

    private function validate(
        RentalAgreement $agreement,
        RentalAgreementVehicle $allocation,
        RentalUsageLogData $data,
    ): void {
        if ($this->math->compare($data->endOdometer, $data->startOdometer) < 0) {
            throw new InvalidArgumentException('Usage end odometer must be greater than or equal to start odometer.');
        }
        if ($this->math->compare($data->startOdometer, (string) $allocation->start_odometer) < 0) {
            throw new InvalidArgumentException('Usage start odometer cannot be below the vehicle pickup odometer.');
        }
        if ($allocation->pickupInspection === null) {
            throw new InvalidArgumentException('Pickup inspection is required before recording vehicle usage.');
        }
        $interval = $this->usageInterval($data);
        $allocationEnd = $allocation->allocated_to ?? $agreement->expected_end_at;
        if ($interval !== null) {
            [$usageStart, $usageEnd] = $interval;
            if ($usageStart->lessThan($allocation->allocated_from)
                || $usageEnd->greaterThan($allocationEnd)) {
                throw new InvalidArgumentException('Usage time must fall within the vehicle allocation period.');
            }
        }
        $date = CarbonImmutable::parse($data->usageDate);
        if ($interval === null && ($date->startOfDay()->lessThan($allocation->allocated_from->startOfDay())
            || ! $date->startOfDay()->lessThan($allocationEnd))) {
            throw new InvalidArgumentException('Usage date must fall within the vehicle allocation period.');
        }
        if (($data->startTime === null) !== ($data->endTime === null)) {
            throw new InvalidArgumentException('Usage start time and end time must be provided together.');
        }
        if ($data->driverId !== null) {
            $driver = HrEmployee::query()
                ->where('tenant_id', $agreement->tenant_id)
                ->where(function (Builder $query) use ($agreement): void {
                    $query->whereNull('organization_unit_id');
                    if ($agreement->organization_unit_id !== null) {
                        $query->orWhere('organization_unit_id', $agreement->organization_unit_id);
                    }
                })
                ->findOrFail($data->driverId);
            if ($driver->status !== EmployeeStatus::Active) {
                throw new InvalidArgumentException('Only active employees can be assigned as rental drivers.');
            }
        }
    }

    private function workingMinutes(RentalUsageLogData $data): int
    {
        if ($data->startTime === null || $data->endTime === null) {
            return 0;
        }
        $start = CarbonImmutable::parse($data->usageDate.' '.$data->startTime);
        $end = CarbonImmutable::parse($data->usageDate.' '.$data->endTime);
        if ($end->lessThan($start)) {
            $end = $end->addDay();
        }

        return intdiv($end->getTimestamp() - $start->getTimestamp(), 60);
    }

    private function validateClassifiedTime(RentalUsageLog $log): void
    {
        $classified = $log->events
            ->whereIn('event_type', [
                RentalUsageEventType::Overtime,
                RentalUsageEventType::DoubleOvertime,
            ])
            ->pluck('quantity')
            ->map(fn ($quantity): string => (string) $quantity)
            ->all();
        $classifiedHours = $this->math->sum($classified);
        $workingHours = $this->math->div((string) $log->working_minutes, '60.000000');
        if ($this->math->compare($classifiedHours, $workingHours) > 0) {
            throw new InvalidArgumentException(
                'Overtime and double-overtime quantities cannot exceed total working hours.',
            );
        }
    }

    private function recordStatus(
        RentalUsageLog $log,
        ?RentalUsageLogStatus $old,
        RentalUsageLogStatus $new,
        ?int $changedBy,
        ?string $reason = null,
    ): void {
        RentalStatusHistory::query()->create([
            'tenant_id' => $log->tenant_id,
            'organization_unit_id' => $log->organization_unit_id,
            'agreement_id' => $log->agreement_id,
            'usage_log_id' => $log->getKey(),
            'entity_type' => 'usage_log',
            'subject_id' => $log->getKey(),
            'old_status' => $old?->value,
            'new_status' => $new->value,
            'reason' => $reason,
            'changed_by' => $changedBy,
            'changed_at' => now(),
        ]);
    }

    private function compareMileageOrder(RentalUsageLog $left, RentalUsageLog $right): int
    {
        $effective = $left->effective_at->getTimestamp() <=> $right->effective_at->getTimestamp();
        if ($effective !== 0) {
            return $effective;
        }
        $sequence = ((int) $left->operational_sequence) <=> ((int) $right->operational_sequence);

        return $sequence !== 0 ? $sequence : ((int) $left->getKey() <=> (int) $right->getKey());
    }
}
