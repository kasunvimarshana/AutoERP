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
        if (! in_array($agreement->status, [
            RentalAgreementStatus::Active,
            RentalAgreementStatus::Returned,
        ], true)) {
            throw new InvalidArgumentException('Usage logs require an active or returned agreement.');
        }

        return DB::transaction(function () use ($agreement, $data): RentalUsageLog {
            $agreement = RentalAgreement::query()
                ->forContext((int) $agreement->tenant_id, $agreement->organization_unit_id)
                ->with('rateSnapshot')
                ->lockForUpdate()
                ->findOrFail($agreement->getKey());
            $allocation = $agreement->vehicles()->with('pickupInspection')->lockForUpdate()
                ->findOrFail($data->agreementVehicleId);
            Vehicle::query()
                ->where('tenant_id', $agreement->tenant_id)
                ->whereKey($allocation->vehicle_id)
                ->lockForUpdate()
                ->firstOrFail();
            $this->validate($agreement, $allocation, $data);

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
            $this->contexts->attach(
                $log,
                $agreement,
                $allocation,
                $data->usageDate,
                $data->startTime,
            );
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
                $this->validateClassifiedTime($log);
                $updates['submitted_by'] = $changedBy;
                $updates['submitted_at'] = now();
            }
            if ($status === RentalUsageLogStatus::Approved) {
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
        $date = CarbonImmutable::parse($data->usageDate);
        if ($date->startOfDay()->lessThan($allocation->allocated_from->startOfDay())
            || ($allocation->allocated_to !== null
                && $date->startOfDay()->greaterThan($allocation->allocated_to->startOfDay()))) {
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
