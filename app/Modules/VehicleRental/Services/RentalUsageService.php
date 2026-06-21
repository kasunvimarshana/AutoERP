<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Services;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\VehicleRental\Enums\RentalAgreementKind;
use Modules\VehicleRental\Enums\RentalAllocationStatus;
use Modules\VehicleRental\Enums\RentalCustodyEventType;
use Modules\VehicleRental\Enums\RentalCustodyStatus;
use Modules\VehicleRental\Enums\RentalFinancialSide;
use Modules\VehicleRental\Enums\RentalMode;
use Modules\VehicleRental\Enums\RentalUsageStatus;
use Modules\VehicleRental\Models\RentalDriverAssignment;
use Modules\VehicleRental\Models\RentalUsageLog;
use Modules\VehicleRental\Models\RentalVehicleAllocation;

final class RentalUsageService
{
    /** @var array<string, list<string>> */
    private const TRANSITIONS = [
        'draft' => ['submitted', 'reversed'],
        'submitted' => ['approved', 'rejected', 'reversed'],
        'approved' => ['reversed'],
        'rejected' => ['draft', 'reversed'],
        'consumed' => ['reversed'],
        'reversed' => [],
    ];

    public function __construct(
        private readonly DecimalMath $math,
        private readonly RentalNumberService $numbers,
        private readonly RentalRateVersionService $rates,
        private readonly RentalStatusHistoryService $history,
    ) {}

    public function create(RentalVehicleAllocation $allocation, array $data, ?int $userId): RentalUsageLog
    {
        return DB::transaction(function () use ($allocation, $data, $userId): RentalUsageLog {
            $allocation = RentalVehicleAllocation::query()
                ->with(['agreement', 'sourceAllocation.agreement'])
                ->lockForUpdate()
                ->findOrFail($allocation->getKey());
            $startedAt = CarbonImmutable::parse((string) $data['started_at']);
            $endedAt = CarbonImmutable::parse((string) $data['ended_at']);
            $this->assertAllocation($allocation, $data, $startedAt, $endedAt);
            $driverAssignment = $this->resolveDriverAssignment($allocation, $data, $startedAt, $endedAt);

            $startOdometer = $this->math->normalize((string) $data['start_odometer']);
            $endOdometer = $this->math->normalize((string) $data['end_odometer']);
            if ($this->math->compare($endOdometer, $startOdometer) < 0) {
                throw new InvalidArgumentException('Finish odometer cannot be below start odometer.');
            }
            $distance = $this->math->sub($endOdometer, $startOdometer);
            $garage = $this->math->normalize((string) ($data['garage_distance_km'] ?? '0'));
            $internal = $this->math->normalize((string) ($data['internal_distance_km'] ?? '0'));
            $chargeable = $this->math->sub($this->math->sub($distance, $garage), $internal);
            if ($this->math->isNegative($chargeable)) {
                throw new InvalidArgumentException('Garage and internal distance cannot exceed total distance.');
            }

            $usageDate = CarbonImmutable::parse((string) $data['usage_date'])->startOfDay();
            $localStartDate = $startedAt->setTimezone($allocation->agreement->billing_timezone)->toDateString();
            if ($usageDate->toDateString() !== $localStartDate) {
                throw new InvalidArgumentException('Usage date must match the local start date in the agreement billing timezone.');
            }

            $fingerprint = hash('sha256', implode('|', [
                $allocation->tenant_id,
                $allocation->getKey(),
                $startedAt->toIso8601String(),
                $endedAt->toIso8601String(),
                $startOdometer,
                $endOdometer,
            ]));
            $existing = RentalUsageLog::query()
                ->where('tenant_id', $allocation->tenant_id)
                ->where('fingerprint', $fingerprint)
                ->first();
            if ($existing !== null) {
                return $existing->load($this->relations());
            }

            $sequence = ((int) RentalUsageLog::query()
                ->where('vehicle_allocation_id', $allocation->getKey())
                ->whereDate('usage_date', $usageDate)
                ->lockForUpdate()
                ->max('operational_sequence')) + 1;

            $overlap = RentalUsageLog::query()
                ->where('vehicle_id', $allocation->vehicle_id)
                ->whereNot('status', RentalUsageStatus::Reversed->value)
                ->where('started_at', '<', $endedAt)
                ->where('ended_at', '>', $startedAt)
                ->lockForUpdate()
                ->exists();
            if ($overlap) {
                throw new InvalidArgumentException('Running-chart time overlaps another usage record for this vehicle.');
            }

            $previous = RentalUsageLog::query()
                ->where('vehicle_id', $allocation->vehicle_id)
                ->whereNot('status', RentalUsageStatus::Reversed->value)
                ->where('ended_at', '<=', $startedAt)
                ->orderByDesc('ended_at')
                ->orderByDesc('id')
                ->lockForUpdate()
                ->first();
            if ($previous !== null && $this->math->compare($startOdometer, (string) $previous->end_odometer) < 0
                && empty($data['odometer_variance_reason'])) {
                throw new InvalidArgumentException('Start odometer is below the previous recorded finish. Provide a variance reason.');
            }

            $next = RentalUsageLog::query()
                ->where('vehicle_id', $allocation->vehicle_id)
                ->whereNot('status', RentalUsageStatus::Reversed->value)
                ->where('started_at', '>=', $endedAt)
                ->orderBy('started_at')
                ->orderBy('id')
                ->lockForUpdate()
                ->first();
            if ($next !== null && $this->math->compare($endOdometer, (string) $next->start_odometer) > 0
                && empty($data['odometer_variance_reason'])) {
                throw new InvalidArgumentException('Finish odometer exceeds the next recorded start. Provide a variance reason.');
            }

            $workingMinutes = $this->minutesBetween($startedAt->toIso8601String(), $endedAt->toIso8601String());
            $overtimeMinutes = (int) ($data['normal_overtime_minutes'] ?? 0)
                + (int) ($data['double_overtime_minutes'] ?? 0)
                + (int) ($data['triple_overtime_minutes'] ?? 0);
            if ($overtimeMinutes > $workingMinutes) {
                throw new InvalidArgumentException('Combined overtime cannot exceed total working minutes.');
            }
            $log = RentalUsageLog::query()->create([
                'tenant_id' => $allocation->tenant_id,
                'organization_unit_id' => $allocation->organization_unit_id,
                'usage_number' => $data['usage_number'] ?? $this->numbers->next(
                    (int) $allocation->tenant_id,
                    $allocation->organization_unit_id,
                    'vehicle_rental_usage',
                    'RUL-',
                ),
                'vehicle_allocation_id' => $allocation->getKey(),
                'vehicle_id' => $allocation->vehicle_id,
                'driver_assignment_id' => $driverAssignment?->getKey(),
                'driver_id' => $driverAssignment?->employee_id,
                'usage_date' => $usageDate->toDateString(),
                'started_at' => $startedAt,
                'ended_at' => $endedAt,
                'start_odometer' => $startOdometer,
                'end_odometer' => $endOdometer,
                'distance_km' => $distance,
                'chargeable_distance_km' => $chargeable,
                'garage_distance_km' => $garage,
                'internal_distance_km' => $internal,
                'working_minutes' => $workingMinutes,
                'normal_overtime_minutes' => $data['normal_overtime_minutes'] ?? 0,
                'double_overtime_minutes' => $data['double_overtime_minutes'] ?? 0,
                'triple_overtime_minutes' => $data['triple_overtime_minutes'] ?? 0,
                'night_out_count' => $data['night_out_count'] ?? '0.000000',
                'trip_from' => $data['trip_from'] ?? null,
                'trip_to' => $data['trip_to'] ?? null,
                'trip_purpose' => $data['trip_purpose'] ?? null,
                'odometer_variance_reason' => $data['odometer_variance_reason'] ?? null,
                'operational_sequence' => $sequence,
                'status' => RentalUsageStatus::Draft->value,
                'fingerprint' => $fingerprint,
                'remarks' => $data['remarks'] ?? null,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            foreach (array_values($data['events'] ?? []) as $index => $event) {
                if (! empty($event['occurred_at'])) {
                    $eventTime = CarbonImmutable::parse((string) $event['occurred_at']);
                    if ($eventTime->lessThan($startedAt) || $eventTime->greaterThan($endedAt)) {
                        throw new InvalidArgumentException('Usage event time must be inside the running-chart time range.');
                    }
                }
                $log->events()->create([
                    'tenant_id' => $allocation->tenant_id,
                    'organization_unit_id' => $allocation->organization_unit_id,
                    'sequence' => $event['sequence'] ?? ($index + 1),
                    'event_type' => $event['event_type'],
                    'occurred_at' => $event['occurred_at'] ?? null,
                    'quantity' => $event['quantity'],
                    'unit' => $event['unit'] ?? null,
                    'reference_number' => $event['reference_number'] ?? null,
                    'remarks' => $event['remarks'] ?? null,
                    'created_by' => $userId,
                    'updated_by' => $userId,
                ]);
            }

            $this->createContext($log, $allocation, RentalFinancialSide::Revenue, $startedAt->toDateTimeString(), $userId);
            if ($allocation->sourceAllocation !== null) {
                $this->createContext($log, $allocation->sourceAllocation, RentalFinancialSide::Cost, $startedAt->toDateTimeString(), $userId);
            }
            $this->history->record($log, null, RentalUsageStatus::Draft->value, $userId);

            return $log->load($this->relations());
        });
    }

    public function transition(RentalUsageLog $log, RentalUsageStatus $to, ?int $userId = null, ?string $reason = null): RentalUsageLog
    {
        return DB::transaction(function () use ($log, $to, $userId, $reason): RentalUsageLog {
            $log = RentalUsageLog::query()->lockForUpdate()->findOrFail($log->getKey());
            $from = $log->status;
            if ($from === $to) {
                return $log->load($this->relations());
            }
            if (! in_array($to->value, self::TRANSITIONS[$from->value] ?? [], true)) {
                throw new InvalidArgumentException("Invalid usage transition from {$from->value} to {$to->value}.");
            }
            if ($to === RentalUsageStatus::Approved && ! $log->contexts()->where('financial_side', RentalFinancialSide::Revenue->value)->exists()) {
                throw new InvalidArgumentException('Revenue context is required before usage approval.');
            }
            if ($to === RentalUsageStatus::Reversed && $log->contexts()
                ->whereHas('calculationLines.run', fn (Builder $query) => $query->where('calculation_status', 'approved'))
                ->exists()) {
                throw new InvalidArgumentException('Reverse the approved rental calculations before reversing this running-chart entry.');
            }

            $log->status = $to;
            $log->submitted_by = $to === RentalUsageStatus::Submitted ? $userId : $log->submitted_by;
            $log->submitted_at = $to === RentalUsageStatus::Submitted ? now() : $log->submitted_at;
            $log->approved_by = $to === RentalUsageStatus::Approved ? $userId : $log->approved_by;
            $log->approved_at = $to === RentalUsageStatus::Approved ? now() : $log->approved_at;
            $log->rejected_by = $to === RentalUsageStatus::Rejected ? $userId : $log->rejected_by;
            $log->rejected_at = $to === RentalUsageStatus::Rejected ? now() : $log->rejected_at;
            $log->reversed_by = $to === RentalUsageStatus::Reversed ? $userId : $log->reversed_by;
            $log->reversed_at = $to === RentalUsageStatus::Reversed ? now() : $log->reversed_at;
            $log->reversal_reason = $to === RentalUsageStatus::Reversed ? $reason : $log->reversal_reason;
            $log->updated_by = $userId;
            $log->save();
            $this->history->record($log, $from->value, $to->value, $userId, $reason);

            return $log->refresh()->load($this->relations());
        });
    }

    public function paginate(int $tenantId, ?int $organizationUnitId, array $filters, int $perPage): LengthAwarePaginator
    {
        $query = RentalUsageLog::query()->forContext($tenantId, $organizationUnitId)->with($this->relations());
        foreach (['vehicle_allocation_id', 'vehicle_id', 'driver_id', 'status'] as $key) {
            if (isset($filters[$key]) && $filters[$key] !== '') {
                $query->where($key, $filters[$key]);
            }
        }
        if (! empty($filters['agreement_id'])) {
            $query->whereHas('contexts', fn (Builder $context) => $context->where('agreement_id', $filters['agreement_id']));
        }
        if (! empty($filters['financial_side'])) {
            $query->whereHas('contexts', fn (Builder $context) => $context->where('financial_side', $filters['financial_side']));
        }
        if (! empty($filters['date_from'])) {
            $query->whereDate('usage_date', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $query->whereDate('usage_date', '<=', $filters['date_to']);
        }

        return $query->latest('usage_date')->latest('operational_sequence')->paginate($perPage);
    }

    public function relations(): array
    {
        return [
            'allocation.agreement.customer', 'allocation.agreement.supplier', 'vehicle.make', 'vehicle.model',
            'driverAssignment', 'driver', 'events', 'contexts.agreement.customer', 'contexts.agreement.supplier',
            'contexts.rateVersion.components',
        ];
    }

    private function assertAllocation(
        RentalVehicleAllocation $allocation,
        array $data,
        CarbonImmutable $startedAt,
        CarbonImmutable $endedAt,
    ): void
    {
        if ($allocation->agreement->agreement_kind !== RentalAgreementKind::CustomerRental) {
            throw new InvalidArgumentException('Daily running chart must be recorded against a customer rental allocation.');
        }
        if ($allocation->status !== RentalAllocationStatus::Active) {
            throw new InvalidArgumentException('Running chart requires an active vehicle allocation.');
        }
        if ($startedAt->lessThan(CarbonImmutable::parse($allocation->allocated_from))
            || ($allocation->allocated_to !== null && $endedAt->greaterThan(CarbonImmutable::parse($allocation->allocated_to)))) {
            throw new InvalidArgumentException('Usage time must be inside the allocation period.');
        }
        $handedOver = $allocation->custodyEvents()
            ->whereIn('event_type', [RentalCustodyEventType::CompanyToCustomer->value, RentalCustodyEventType::ReplacementIn->value])
            ->where('status', RentalCustodyStatus::Confirmed->value)
            ->where('occurred_at', '<=', $startedAt)
            ->exists();
        if (! $handedOver) {
            throw new InvalidArgumentException('Vehicle must be handed over to the customer before recording usage.');
        }
        $returned = $allocation->custodyEvents()
            ->whereIn('event_type', [RentalCustodyEventType::CustomerToCompany->value, RentalCustodyEventType::ReplacementOut->value])
            ->where('status', RentalCustodyStatus::Confirmed->value)
            ->where('occurred_at', '<', $endedAt)
            ->exists();
        if ($returned) {
            throw new InvalidArgumentException('Usage cannot be recorded after the vehicle return.');
        }
    }

    private function resolveDriverAssignment(
        RentalVehicleAllocation $allocation,
        array $data,
        CarbonImmutable $startedAt,
        CarbonImmutable $endedAt,
    ): ?RentalDriverAssignment {
        $assignmentId = isset($data['driver_assignment_id']) ? (int) $data['driver_assignment_id'] : null;
        if ($allocation->agreement->rental_mode === RentalMode::WithDriver && $assignmentId === null) {
            throw new InvalidArgumentException('A valid driver assignment is required for a with-driver rental.');
        }
        if ($assignmentId === null) {
            if (! empty($data['driver_id'])) {
                throw new InvalidArgumentException('Select a driver assignment instead of submitting an unassigned driver.');
            }

            return null;
        }

        $assignment = $allocation->driverAssignments()
            ->whereKey($assignmentId)
            ->where('assigned_from', '<=', $startedAt)
            ->where(fn (Builder $query) => $query->whereNull('assigned_to')->orWhere('assigned_to', '>=', $endedAt))
            ->first();
        if ($assignment === null) {
            throw new InvalidArgumentException('Driver assignment is not valid for the complete usage period.');
        }
        if (! empty($data['driver_id']) && (int) $data['driver_id'] !== (int) $assignment->employee_id) {
            throw new InvalidArgumentException('Driver does not match the selected driver assignment.');
        }

        return $assignment;
    }

    private function createContext(
        RentalUsageLog $log,
        RentalVehicleAllocation $allocation,
        RentalFinancialSide $side,
        string $at,
        ?int $userId,
    ): void {
        $agreement = $allocation->agreement;
        $rate = $this->rates->resolve($agreement, $at);
        $fingerprint = hash('sha256', implode('|', [$log->tenant_id, $log->getKey(), $side->value, $agreement->getKey(), $rate->getKey()]));
        $log->contexts()->create([
            'tenant_id' => $log->tenant_id,
            'organization_unit_id' => $log->organization_unit_id,
            'financial_side' => $side->value,
            'agreement_id' => $agreement->getKey(),
            'vehicle_allocation_id' => $allocation->getKey(),
            'rate_version_id' => $rate->getKey(),
            'customer_id' => $side === RentalFinancialSide::Revenue ? $agreement->customer_id : null,
            'supplier_id' => $side === RentalFinancialSide::Cost ? $agreement->supplier_id : null,
            'currency_id' => $rate->currency_id,
            'context_fingerprint' => $fingerprint,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);
    }

    private function minutesBetween(?string $start, ?string $end): int
    {
        if ($start === null || $end === null) {
            return 0;
        }
        $from = CarbonImmutable::parse($start);
        $to = CarbonImmutable::parse($end);
        if (! $to->greaterThan($from)) {
            throw new InvalidArgumentException('Usage finish time must be after start time.');
        }

        return $from->diffInMinutes($to);
    }
}
