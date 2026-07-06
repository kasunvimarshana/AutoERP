<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Services;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\VehicleRental\Enums\RentalAgreementKind;
use Modules\VehicleRental\Enums\RentalAgreementStatus;
use Modules\VehicleRental\Enums\RentalAllocationStatus;
use Modules\VehicleRental\Enums\RentalCalculationStatus;
use Modules\VehicleRental\Enums\RentalCustodyEventType;
use Modules\VehicleRental\Enums\RentalCustodyStatus;
use Modules\VehicleRental\Enums\RentalFinancialSide;
use Modules\VehicleRental\Enums\RentalMode;
use Modules\VehicleRental\Enums\RentalUsageEventApplicability;
use Modules\VehicleRental\Enums\RentalUsageStatus;
use Modules\VehicleRental\Models\RentalDriverAssignment;
use Modules\VehicleRental\Models\RentalUsageContext;
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
        'reversed' => [],
    ];

    public function __construct(
        private readonly DecimalMath $math,
        private readonly RentalNumberService $numbers,
        private readonly RentalRateVersionService $rates,
        private readonly RentalStatusHistoryService $history,
        private readonly RentalUsageFactService $facts,
    ) {}

    public function create(
        RentalVehicleAllocation $allocation,
        array $data,
        ?int $userId,
    ): RentalUsageLog {
        return DB::transaction(function () use ($allocation, $data, $userId): RentalUsageLog {
            $allocation = RentalVehicleAllocation::query()
                ->with(['agreement', 'sourceAllocation.agreement'])
                ->lockForUpdate()
                ->findOrFail($allocation->getKey());
            $this->assertAllocationExpectedVersion($allocation, (int) ($data['expected_allocation_version'] ?? 0));
            $sourceAllocation = $this->lockSourceAllocation(
                $allocation,
                isset($data['expected_source_allocation_version'])
                    ? (int) $data['expected_source_allocation_version']
                    : null,
            );

            $startedAt = CarbonImmutable::parse((string) $data['started_at']);
            $endedAt = CarbonImmutable::parse((string) $data['ended_at']);
            $this->assertAllocation($allocation, $startedAt, $endedAt);
            $this->assertSourceAllocation($allocation, $sourceAllocation, $startedAt, $endedAt);
            $driverAssignment = $this->resolveDriverAssignment(
                $allocation,
                $data,
                $startedAt,
                $endedAt,
            );

            [$startOdometer, $endOdometer, $distance, $netOperationalDistance, $garage, $internal]
                = $this->distanceFacts($data);
            $workingMinutes = $this->minutesBetween($startedAt, $endedAt);
            $normalOvertime = (int) ($data['normal_overtime_minutes'] ?? 0);
            $doubleOvertime = (int) ($data['double_overtime_minutes'] ?? 0);
            $tripleOvertime = (int) ($data['triple_overtime_minutes'] ?? 0);
            if ($normalOvertime + $doubleOvertime + $tripleOvertime > $workingMinutes) {
                throw new InvalidArgumentException(
                    'Combined overtime cannot exceed total working minutes.',
                );
            }
            $nightOutCount = $this->math->normalize((string) ($data['night_out_count'] ?? '0'));
            $usageDate = $this->assertUsageDate($allocation, $data, $startedAt);
            $fingerprint = $this->fingerprint(
                $allocation,
                $sourceAllocation,
                $driverAssignment,
                $data,
                $usageDate,
                $startedAt,
                $endedAt,
                $startOdometer,
                $endOdometer,
                $distance,
                $netOperationalDistance,
                $garage,
                $internal,
                $workingMinutes,
                $normalOvertime,
                $doubleOvertime,
                $tripleOvertime,
                $nightOutCount,
            );

            $existing = RentalUsageLog::query()
                ->where('tenant_id', $allocation->tenant_id)
                ->where('fingerprint', $fingerprint)
                ->first();
            if ($existing !== null) {
                return $existing->load($this->relations());
            }

            $this->lockVehicleTimeline($allocation);
            $this->assertNoVehicleOverlap(
                (int) $allocation->vehicle_id,
                $startedAt,
                $endedAt,
            );
            if ($driverAssignment !== null) {
                $this->lockDriverTimeline(
                    (int) $allocation->tenant_id,
                    (int) $driverAssignment->employee_id,
                );
                $this->assertNoDriverOverlap(
                    (int) $driverAssignment->employee_id,
                    $startedAt,
                    $endedAt,
                );
            }
            $this->assertOdometerChain(
                (int) $allocation->vehicle_id,
                $startedAt,
                $endedAt,
                $startOdometer,
                $endOdometer,
                $data['odometer_variance_reason'] ?? null,
            );

            $sequence = ((int) RentalUsageLog::query()
                ->where('vehicle_allocation_id', $allocation->getKey())
                ->whereDate('usage_date', $usageDate)
                ->lockForUpdate()
                ->max('operational_sequence')) + 1;

            $log = RentalUsageLog::query()->create([
                'tenant_id' => $allocation->tenant_id,
                'organization_unit_id' => $allocation->organization_unit_id,
                'usage_number' => $this->numbers->next(
                    (int) $allocation->tenant_id,
                    $allocation->organization_unit_id,
                    'vehicle_rental_usage',
                    'RUL-',
                ),
                'vehicle_allocation_id' => $allocation->getKey(),
                'vehicle_id' => $allocation->vehicle_id,
                'driver_assignment_id' => $driverAssignment?->getKey(),
                'driver_id' => $driverAssignment?->employee_id,
                'usage_date' => $usageDate,
                'started_at' => $startedAt,
                'ended_at' => $endedAt,
                'start_odometer' => $startOdometer,
                'end_odometer' => $endOdometer,
                'distance_km' => $distance,
                'net_operational_distance_km' => $netOperationalDistance,
                'garage_distance_km' => $garage,
                'internal_distance_km' => $internal,
                'working_minutes' => $workingMinutes,
                'normal_overtime_minutes' => $normalOvertime,
                'double_overtime_minutes' => $doubleOvertime,
                'triple_overtime_minutes' => $tripleOvertime,
                'night_out_count' => $nightOutCount,
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

            $this->createEvents(
                $log,
                $allocation,
                $data['events'] ?? [],
                $sourceAllocation !== null,
                $startedAt,
                $endedAt,
                $userId,
            );

            $revenueContext = $this->createContext(
                $log,
                $allocation,
                RentalFinancialSide::Revenue,
                $startedAt,
                $userId,
            );
            $this->facts->createInitial($revenueContext, $log, $userId);

            if ($sourceAllocation !== null) {
                $costContext = $this->createContext(
                    $log,
                    $sourceAllocation,
                    RentalFinancialSide::Cost,
                    $startedAt,
                    $userId,
                );
                $this->facts->createInitial($costContext, $log, $userId);
            }

            $this->history->record(
                $log,
                null,
                RentalUsageStatus::Draft->value,
                $userId,
            );

            return $log->load($this->relations());
        });
    }

    public function transition(
        RentalUsageLog $log,
        RentalUsageStatus $to,
        int $expectedVersion,
        ?int $userId = null,
        ?string $reason = null,
    ): RentalUsageLog {
        return DB::transaction(function () use (
            $log,
            $to,
            $expectedVersion,
            $userId,
            $reason,
        ): RentalUsageLog {
            $log = RentalUsageLog::query()
                ->lockForUpdate()
                ->findOrFail($log->getKey());

            if ((int) $log->row_version !== $expectedVersion) {
                throw new InvalidArgumentException(
                    'Running-chart usage changed since it was loaded. Reload and try again.',
                );
            }

            $from = $log->status;
            if ($from === $to) {
                return $log->load($this->relations());
            }
            if (! in_array($to->value, self::TRANSITIONS[$from->value] ?? [], true)) {
                throw new InvalidArgumentException(
                    "Invalid usage transition from {$from->value} to {$to->value}.",
                );
            }
            if (
                $to === RentalUsageStatus::Approved
                && ! $log->contexts()
                    ->where('financial_side', RentalFinancialSide::Revenue->value)
                    ->exists()
            ) {
                throw new InvalidArgumentException(
                    'Revenue context is required before usage approval.',
                );
            }
            if ($to === RentalUsageStatus::Reversed) {
                $this->assertReversible($log, $reason);
                $this->facts->reverseForUsage(
                    $log,
                    $userId,
                    trim((string) $reason),
                );
            }

            $log->forceFill([
                'status' => $to,
                'submitted_by' => $to === RentalUsageStatus::Submitted
                    ? $userId
                    : $log->submitted_by,
                'submitted_at' => $to === RentalUsageStatus::Submitted
                    ? now()
                    : $log->submitted_at,
                'approved_by' => $to === RentalUsageStatus::Approved
                    ? $userId
                    : $log->approved_by,
                'approved_at' => $to === RentalUsageStatus::Approved
                    ? now()
                    : $log->approved_at,
                'rejected_by' => $to === RentalUsageStatus::Rejected
                    ? $userId
                    : $log->rejected_by,
                'rejected_at' => $to === RentalUsageStatus::Rejected
                    ? now()
                    : $log->rejected_at,
                'reversed_by' => $to === RentalUsageStatus::Reversed
                    ? $userId
                    : $log->reversed_by,
                'reversed_at' => $to === RentalUsageStatus::Reversed
                    ? now()
                    : $log->reversed_at,
                'reversal_reason' => $to === RentalUsageStatus::Reversed
                    ? trim((string) $reason)
                    : $log->reversal_reason,
                'row_version' => $log->row_version + 1,
                'updated_by' => $userId,
            ])->save();

            $this->history->record(
                $log,
                $from->value,
                $to->value,
                $userId,
                $reason,
            );

            return $log->refresh()->load($this->relations());
        });
    }

    public function paginate(
        int $tenantId,
        ?int $organizationUnitId,
        array $filters,
        int $perPage,
    ): LengthAwarePaginator {
        $query = RentalUsageLog::query()
            ->forContext($tenantId, $organizationUnitId)
            ->with($this->relations());

        foreach (['vehicle_allocation_id', 'vehicle_id', 'driver_id', 'status'] as $key) {
            if (isset($filters[$key]) && $filters[$key] !== '') {
                $query->where($key, $filters[$key]);
            }
        }
        if (! empty($filters['agreement_id']) && ! empty($filters['financial_side'])) {
            $query->whereHas(
                'contexts',
                fn (Builder $context) => $context
                    ->where('agreement_id', $filters['agreement_id'])
                    ->where('financial_side', $filters['financial_side']),
            );
        } elseif (! empty($filters['agreement_id'])) {
            $query->whereHas(
                'contexts',
                fn (Builder $context) => $context
                    ->where('agreement_id', $filters['agreement_id']),
            );
        } elseif (! empty($filters['financial_side'])) {
            $query->whereHas(
                'contexts',
                fn (Builder $context) => $context
                    ->where('financial_side', $filters['financial_side']),
            );
        }
        if (! empty($filters['date_from'])) {
            $query->whereDate('usage_date', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $query->whereDate('usage_date', '<=', $filters['date_to']);
        }

        return $query
            ->latest('usage_date')
            ->latest('operational_sequence')
            ->paginate($perPage);
    }

    /** @return list<string> */
    public function relations(): array
    {
        return [
            'allocation.agreement.customer',
            'allocation.agreement.supplier',
            'vehicle.make',
            'vehicle.model',
            'driverAssignment.employee',
            'driver',
            'events',
            'contexts.agreement.customer',
            'contexts.agreement.supplier',
            'contexts.customer',
            'contexts.supplier',
            'contexts.allocation',
            'contexts.rateVersion.components',
            'contexts.usageFact',
        ];
    }

    private function assertAllocation(
        RentalVehicleAllocation $allocation,
        CarbonImmutable $startedAt,
        CarbonImmutable $endedAt,
    ): void {
        if ($allocation->agreement->agreement_kind !== RentalAgreementKind::CustomerRental) {
            throw new InvalidArgumentException(
                'Daily running chart must be recorded against a customer rental allocation.',
            );
        }
        if ($allocation->agreement->status !== RentalAgreementStatus::Active) {
            throw new InvalidArgumentException(
                'Running chart requires an active rental agreement.',
            );
        }
        if ($allocation->status !== RentalAllocationStatus::Active) {
            throw new InvalidArgumentException(
                'Running chart requires an active vehicle allocation.',
            );
        }
        if (
            $startedAt->lessThan(CarbonImmutable::parse($allocation->allocated_from))
            || (
                $allocation->allocated_to !== null
                && $endedAt->greaterThan(CarbonImmutable::parse($allocation->allocated_to))
            )
        ) {
            throw new InvalidArgumentException(
                'Usage time must be inside the allocation period.',
            );
        }

        $handedOver = $allocation->custodyEvents()
            ->whereIn('event_type', [
                RentalCustodyEventType::CompanyToCustomer->value,
                RentalCustodyEventType::ReplacementIn->value,
            ])
            ->where('status', RentalCustodyStatus::Confirmed->value)
            ->where('occurred_at', '<=', $startedAt)
            ->exists();
        if (! $handedOver) {
            throw new InvalidArgumentException(
                'Vehicle must be handed over to the customer before recording usage.',
            );
        }

        $returned = $allocation->custodyEvents()
            ->whereIn('event_type', [
                RentalCustodyEventType::CustomerToCompany->value,
                RentalCustodyEventType::ReplacementOut->value,
            ])
            ->where('status', RentalCustodyStatus::Confirmed->value)
            ->where('occurred_at', '<', $endedAt)
            ->exists();
        if ($returned) {
            throw new InvalidArgumentException(
                'Usage cannot be recorded after the vehicle return.',
            );
        }
    }

    private function lockSourceAllocation(
        RentalVehicleAllocation $allocation,
        ?int $expectedVersion,
    ): ?RentalVehicleAllocation {
        if ($allocation->source_allocation_id === null) {
            return null;
        }

        $sourceAllocation = RentalVehicleAllocation::query()
            ->with('agreement')
            ->where('tenant_id', $allocation->tenant_id)
            ->lockForUpdate()
            ->findOrFail($allocation->source_allocation_id);
        if ($expectedVersion === null || (int) $sourceAllocation->row_version !== $expectedVersion) {
            throw ValidationException::withMessages([
                'expected_source_allocation_version' => ['The owner supply allocation changed after it was loaded. Reload and review the latest version.'],
            ]);
        }

        $allocation->setRelation('sourceAllocation', $sourceAllocation);

        return $sourceAllocation;
    }

    private function assertSourceAllocation(
        RentalVehicleAllocation $allocation,
        ?RentalVehicleAllocation $sourceAllocation,
        CarbonImmutable $startedAt,
        CarbonImmutable $endedAt,
    ): void {
        if ($sourceAllocation === null) {
            return;
        }
        if ($sourceAllocation->agreement->agreement_kind !== RentalAgreementKind::OwnerSupply) {
            throw new InvalidArgumentException(
                'Running chart owner payable context requires an owner supply allocation.',
            );
        }
        if ($sourceAllocation->agreement->status !== RentalAgreementStatus::Active) {
            throw new InvalidArgumentException(
                'Running chart owner payable context requires an active owner supply agreement.',
            );
        }
        if ((int) $sourceAllocation->vehicle_id !== (int) $allocation->vehicle_id) {
            throw new InvalidArgumentException(
                'Running chart owner supply allocation must use the same vehicle as the customer allocation.',
            );
        }
        if ($sourceAllocation->status !== RentalAllocationStatus::Active) {
            throw new InvalidArgumentException(
                'Running chart owner payable context requires an active owner supply allocation.',
            );
        }
        if (
            $startedAt->lessThan(CarbonImmutable::parse($sourceAllocation->allocated_from))
            || (
                $sourceAllocation->allocated_to !== null
                && $endedAt->greaterThan(CarbonImmutable::parse($sourceAllocation->allocated_to))
            )
        ) {
            throw new InvalidArgumentException(
                'Usage time must be inside the owner supply allocation period.',
            );
        }
    }

    private function resolveDriverAssignment(
        RentalVehicleAllocation $allocation,
        array $data,
        CarbonImmutable $startedAt,
        CarbonImmutable $endedAt,
    ): ?RentalDriverAssignment {
        $assignmentId = isset($data['driver_assignment_id'])
            ? (int) $data['driver_assignment_id']
            : null;

        if (
            $allocation->agreement->rental_mode === RentalMode::WithDriver
            && $assignmentId === null
        ) {
            throw new InvalidArgumentException(
                'A valid driver assignment is required for a with-driver rental.',
            );
        }
        if ($assignmentId === null) {
            return null;
        }

        $assignment = $allocation->driverAssignments()
            ->whereKey($assignmentId)
            ->where('assigned_from', '<=', $startedAt)
            ->where(fn (Builder $query) => $query
                ->whereNull('assigned_to')
                ->orWhere('assigned_to', '>=', $endedAt))
            ->lockForUpdate()
            ->first();

        if ($assignment === null) {
            throw new InvalidArgumentException(
                'Driver assignment is not valid for the complete usage period.',
            );
        }

        return $assignment;
    }

    /**
     * @return array{string, string, string, string, string, string}
     */
    private function distanceFacts(array $data): array
    {
        $start = $this->math->normalize((string) $data['start_odometer']);
        $end = $this->math->normalize((string) $data['end_odometer']);
        if ($this->math->compare($end, $start) < 0) {
            throw new InvalidArgumentException(
                'Finish odometer cannot be below start odometer.',
            );
        }

        $distance = $this->math->sub($end, $start);
        $garage = $this->math->normalize(
            (string) ($data['garage_distance_km'] ?? '0'),
        );
        $internal = $this->math->normalize(
            (string) ($data['internal_distance_km'] ?? '0'),
        );
        $net = $this->math->sub(
            $this->math->sub($distance, $garage),
            $internal,
        );
        if ($this->math->isNegative($net)) {
            throw new InvalidArgumentException(
                'Garage and internal distance cannot exceed total distance.',
            );
        }

        return [$start, $end, $distance, $net, $garage, $internal];
    }

    private function assertUsageDate(
        RentalVehicleAllocation $allocation,
        array $data,
        CarbonImmutable $startedAt,
    ): string {
        $usageDate = CarbonImmutable::parse((string) $data['usage_date'])
            ->toDateString();
        $localStartDate = $startedAt
            ->setTimezone($allocation->agreement->billing_timezone)
            ->toDateString();

        if ($usageDate !== $localStartDate) {
            throw new InvalidArgumentException(
                'Usage date must match the local start date in the agreement billing timezone.',
            );
        }

        return $usageDate;
    }

    private function fingerprint(
        RentalVehicleAllocation $allocation,
        ?RentalVehicleAllocation $sourceAllocation,
        ?RentalDriverAssignment $driverAssignment,
        array $data,
        string $usageDate,
        CarbonImmutable $startedAt,
        CarbonImmutable $endedAt,
        string $startOdometer,
        string $endOdometer,
        string $distance,
        string $netOperationalDistance,
        string $garage,
        string $internal,
        int $workingMinutes,
        int $normalOvertime,
        int $doubleOvertime,
        int $tripleOvertime,
        string $nightOutCount,
    ): string {
        $events = array_map(function (array $event): array {
            return [
                'event_type' => (string) ($event['event_type'] ?? ''),
                'applicability' => (string) ($event['applicability'] ?? ''),
                'occurred_at' => empty($event['occurred_at'])
                    ? null
                    : CarbonImmutable::parse((string) $event['occurred_at'])->toIso8601String(),
                'quantity' => $this->math->normalize((string) ($event['quantity'] ?? '0')),
                'unit' => $event['unit'] ?? null,
                'reference_number' => $event['reference_number'] ?? null,
                'remarks' => $event['remarks'] ?? null,
            ];
        }, array_values($data['events'] ?? []));

        return hash('sha256', json_encode([
            'tenant_id' => (int) $allocation->tenant_id,
            'allocation_id' => (int) $allocation->getKey(),
            'source_allocation_id' => $sourceAllocation?->getKey(),
            'driver_assignment_id' => $driverAssignment?->getKey(),
            'usage_date' => $usageDate,
            'started_at' => $startedAt->toIso8601String(),
            'ended_at' => $endedAt->toIso8601String(),
            'start_odometer' => $startOdometer,
            'end_odometer' => $endOdometer,
            'distance_km' => $distance,
            'net_operational_distance_km' => $netOperationalDistance,
            'garage_distance_km' => $garage,
            'internal_distance_km' => $internal,
            'working_minutes' => $workingMinutes,
            'normal_overtime_minutes' => $normalOvertime,
            'double_overtime_minutes' => $doubleOvertime,
            'triple_overtime_minutes' => $tripleOvertime,
            'night_out_count' => $nightOutCount,
            'trip_from' => $data['trip_from'] ?? null,
            'trip_to' => $data['trip_to'] ?? null,
            'trip_purpose' => $data['trip_purpose'] ?? null,
            'odometer_variance_reason' => $data['odometer_variance_reason'] ?? null,
            'remarks' => $data['remarks'] ?? null,
            'events' => $events,
        ], JSON_THROW_ON_ERROR));
    }

    private function lockVehicleTimeline(RentalVehicleAllocation $allocation): void
    {
        RentalVehicleAllocation::query()
            ->where('tenant_id', $allocation->tenant_id)
            ->where('vehicle_id', $allocation->vehicle_id)
            ->orderBy('id')
            ->lockForUpdate()
            ->get(['id']);
    }

    private function assertAllocationExpectedVersion(RentalVehicleAllocation $allocation, int $expectedVersion): void
    {
        if ((int) $allocation->row_version !== $expectedVersion) {
            throw ValidationException::withMessages([
                'expected_allocation_version' => ['The vehicle allocation changed after it was loaded. Reload and review the latest version.'],
            ]);
        }
    }

    private function lockDriverTimeline(int $tenantId, int $employeeId): void
    {
        RentalDriverAssignment::query()
            ->where('tenant_id', $tenantId)
            ->where('employee_id', $employeeId)
            ->orderBy('id')
            ->lockForUpdate()
            ->get(['id']);
    }

    private function assertNoVehicleOverlap(
        int $vehicleId,
        CarbonImmutable $startedAt,
        CarbonImmutable $endedAt,
    ): void {
        if ($this->overlapQuery($startedAt, $endedAt)
            ->where('vehicle_id', $vehicleId)
            ->exists()) {
            throw new InvalidArgumentException(
                'Running-chart time overlaps another usage record for this vehicle.',
            );
        }
    }

    private function assertNoDriverOverlap(
        int $employeeId,
        CarbonImmutable $startedAt,
        CarbonImmutable $endedAt,
    ): void {
        if ($this->overlapQuery($startedAt, $endedAt)
            ->where('driver_id', $employeeId)
            ->exists()) {
            throw new InvalidArgumentException(
                'Running-chart time overlaps another usage record for this driver.',
            );
        }
    }

    private function overlapQuery(
        CarbonImmutable $startedAt,
        CarbonImmutable $endedAt,
    ): Builder {
        return RentalUsageLog::query()
            ->whereNot('status', RentalUsageStatus::Reversed->value)
            ->where('started_at', '<', $endedAt)
            ->where('ended_at', '>', $startedAt)
            ->lockForUpdate();
    }

    private function assertOdometerChain(
        int $vehicleId,
        CarbonImmutable $startedAt,
        CarbonImmutable $endedAt,
        string $startOdometer,
        string $endOdometer,
        ?string $varianceReason,
    ): void {
        $hasReason = trim((string) $varianceReason) !== '';

        $previous = RentalUsageLog::query()
            ->where('vehicle_id', $vehicleId)
            ->whereNot('status', RentalUsageStatus::Reversed->value)
            ->where('ended_at', '<=', $startedAt)
            ->orderByDesc('ended_at')
            ->orderByDesc('id')
            ->lockForUpdate()
            ->first();
        if (
            $previous !== null
            && $this->math->compare(
                $startOdometer,
                (string) $previous->end_odometer,
            ) < 0
            && ! $hasReason
        ) {
            throw new InvalidArgumentException(
                'Start odometer is below the previous recorded finish. Provide a variance reason.',
            );
        }

        $next = RentalUsageLog::query()
            ->where('vehicle_id', $vehicleId)
            ->whereNot('status', RentalUsageStatus::Reversed->value)
            ->where('started_at', '>=', $endedAt)
            ->orderBy('started_at')
            ->orderBy('id')
            ->lockForUpdate()
            ->first();
        if (
            $next !== null
            && $this->math->compare(
                $endOdometer,
                (string) $next->start_odometer,
            ) > 0
            && ! $hasReason
        ) {
            throw new InvalidArgumentException(
                'Finish odometer exceeds the next recorded start. Provide a variance reason.',
            );
        }
    }

    /**
     * @param list<array<string, mixed>> $events
     */
    private function createEvents(
        RentalUsageLog $log,
        RentalVehicleAllocation $allocation,
        array $events,
        bool $hasOwnerContext,
        CarbonImmutable $startedAt,
        CarbonImmutable $endedAt,
        ?int $userId,
    ): void {
        foreach (array_values($events) as $index => $event) {
            $applicability = RentalUsageEventApplicability::from((string) $event['applicability']);
            if (
                ! $hasOwnerContext
                && in_array($applicability, [RentalUsageEventApplicability::Owner, RentalUsageEventApplicability::Both], true)
            ) {
                throw new InvalidArgumentException(
                    'Owner-applicable usage events require a linked owner supply allocation.',
                );
            }
            if (! empty($event['occurred_at'])) {
                $occurredAt = CarbonImmutable::parse(
                    (string) $event['occurred_at'],
                );
                if (
                    $occurredAt->lessThan($startedAt)
                    || $occurredAt->greaterThan($endedAt)
                ) {
                    throw new InvalidArgumentException(
                        'Usage event time must be inside the running-chart time range.',
                    );
                }
            }

            $log->events()->create([
                'tenant_id' => $allocation->tenant_id,
                'organization_unit_id' => $allocation->organization_unit_id,
                'sequence' => $index + 1,
                'event_type' => $event['event_type'],
                'applicability' => $applicability->value,
                'occurred_at' => $event['occurred_at'] ?? null,
                'quantity' => $event['quantity'],
                'unit' => $event['unit'] ?? null,
                'reference_number' => $event['reference_number'] ?? null,
                'remarks' => $event['remarks'] ?? null,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);
        }
    }

    private function createContext(
        RentalUsageLog $log,
        RentalVehicleAllocation $allocation,
        RentalFinancialSide $side,
        CarbonImmutable $at,
        ?int $userId,
    ): RentalUsageContext {
        $agreement = $allocation->agreement;
        $rate = $this->rates->resolve(
            $agreement,
            $at->toDateTimeString(),
        );
        $fingerprint = hash('sha256', implode('|', [
            $log->tenant_id,
            $log->getKey(),
            $side->value,
            $agreement->getKey(),
            $rate->getKey(),
        ]));

        return $log->contexts()->create([
            'tenant_id' => $log->tenant_id,
            'organization_unit_id' => $log->organization_unit_id,
            'financial_side' => $side->value,
            'agreement_id' => $agreement->getKey(),
            'vehicle_allocation_id' => $allocation->getKey(),
            'rate_version_id' => $rate->getKey(),
            'customer_id' => $side === RentalFinancialSide::Revenue
                ? $agreement->customer_id
                : null,
            'supplier_id' => $side === RentalFinancialSide::Cost
                ? $agreement->supplier_id
                : null,
            'currency_id' => $rate->currency_id,
            'context_fingerprint' => $fingerprint,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);
    }

    private function assertReversible(
        RentalUsageLog $log,
        ?string $reason,
    ): void {
        if (trim((string) $reason) === '') {
            throw new InvalidArgumentException('A reversal reason is required.');
        }
        if ($log->contexts()
            ->whereHas(
                'calculationLines.run',
                fn (Builder $query) => $query->where(
                    'calculation_status',
                    RentalCalculationStatus::Approved->value,
                ),
            )
            ->exists()) {
            throw new InvalidArgumentException(
                'Reverse the approved rental calculations before reversing this running-chart entry.',
            );
        }
    }

    private function minutesBetween(
        CarbonImmutable $start,
        CarbonImmutable $end,
    ): int {
        if (! $end->greaterThan($start)) {
            throw new InvalidArgumentException(
                'Usage finish time must be after start time.',
            );
        }

        return (int) $start->diffInMinutes($end);
    }
}
