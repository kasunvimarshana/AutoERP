<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Services;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\VehicleRental\Enums\RentalCalculationLineStatus;
use Modules\VehicleRental\Enums\RentalCalculationStatus;
use Modules\VehicleRental\Enums\RentalUsageFactStatus;
use Modules\VehicleRental\Enums\RentalUsageStatus;
use Modules\VehicleRental\Models\RentalUsageContext;
use Modules\VehicleRental\Models\RentalUsageFact;
use Modules\VehicleRental\Models\RentalUsageLog;

final class RentalUsageFactService
{
    /** @var array<string, list<string>> */
    private const TRANSITIONS = [
        'draft' => ['submitted', 'reversed'],
        'submitted' => ['approved', 'rejected', 'reversed'],
        'approved' => ['reversed'],
        'rejected' => ['draft', 'reversed'],
        'reversed' => [],
    ];

    public function __construct(private readonly DecimalMath $math) {}

    public function createInitial(
        RentalUsageContext $context,
        RentalUsageLog $usage,
        ?int $userId,
    ): RentalUsageFact {
        return $context->usageFact()->create([
            'tenant_id' => $usage->tenant_id,
            'organization_unit_id' => $usage->organization_unit_id,
            'usage_log_id' => $usage->getKey(),
            'financial_side' => $context->financial_side->value,
            'started_at' => $usage->started_at,
            'ended_at' => $usage->ended_at,
            'start_odometer' => $usage->start_odometer,
            'end_odometer' => $usage->end_odometer,
            'commercial_distance_km' => $usage->net_operational_distance_km,
            'working_minutes' => $usage->working_minutes,
            'normal_overtime_minutes' => $usage->normal_overtime_minutes,
            'double_overtime_minutes' => $usage->double_overtime_minutes,
            'triple_overtime_minutes' => $usage->triple_overtime_minutes,
            'night_out_count' => $usage->night_out_count,
            'status' => RentalUsageFactStatus::Draft->value,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);
    }

    public function update(
        RentalUsageFact $fact,
        array $data,
        int $expectedVersion,
        ?int $userId,
    ): RentalUsageFact {
        return DB::transaction(function () use ($fact, $data, $expectedVersion, $userId): RentalUsageFact {
            $fact = RentalUsageFact::query()
                ->with(['usageLog', 'context'])
                ->lockForUpdate()
                ->findOrFail($fact->getKey());
            $this->assertVersion($fact, $expectedVersion);
            if (! in_array($fact->status, [RentalUsageFactStatus::Draft, RentalUsageFactStatus::Rejected], true)) {
                throw new InvalidArgumentException('Only draft or rejected commercial usage facts can be edited.');
            }

            $usage = RentalUsageLog::query()->lockForUpdate()->findOrFail($fact->usage_log_id);
            $startedAt = CarbonImmutable::parse((string) $data['started_at']);
            $endedAt = CarbonImmutable::parse((string) $data['ended_at']);
            if ($startedAt->lessThan(CarbonImmutable::parse($usage->started_at))
                || $endedAt->greaterThan(CarbonImmutable::parse($usage->ended_at))) {
                throw new InvalidArgumentException('Commercial time must stay inside the physical usage period.');
            }

            $startOdometer = $this->math->normalize((string) $data['start_odometer']);
            $endOdometer = $this->math->normalize((string) $data['end_odometer']);
            if ($this->math->compare($endOdometer, $startOdometer) < 0
                || $this->math->compare($startOdometer, (string) $usage->start_odometer) < 0
                || $this->math->compare($endOdometer, (string) $usage->end_odometer) > 0) {
                throw new InvalidArgumentException('Commercial odometer values must stay inside the physical odometer range.');
            }

            $commercialDistance = $this->math->normalize((string) $data['commercial_distance_km']);
            $commercialOdometerDistance = $this->math->sub($endOdometer, $startOdometer);
            if ($this->math->compare($commercialDistance, $commercialOdometerDistance) > 0) {
                throw new InvalidArgumentException('Commercial distance cannot exceed the selected commercial odometer range.');
            }
            $workingMinutes = $startedAt->diffInMinutes($endedAt);
            $normalOvertime = (int) ($data['normal_overtime_minutes'] ?? 0);
            $doubleOvertime = (int) ($data['double_overtime_minutes'] ?? 0);
            $tripleOvertime = (int) ($data['triple_overtime_minutes'] ?? 0);
            if ($normalOvertime + $doubleOvertime + $tripleOvertime > $workingMinutes) {
                throw new InvalidArgumentException('Combined commercial overtime cannot exceed commercial working minutes.');
            }
            $nightOutCount = $this->math->normalize((string) ($data['night_out_count'] ?? '0'));

            $varianceExists = $this->math->compare($startOdometer, (string) $usage->start_odometer) !== 0
                || $this->math->compare($endOdometer, (string) $usage->end_odometer) !== 0
                || $this->math->compare($commercialDistance, (string) $usage->net_operational_distance_km) !== 0
                || ! $startedAt->equalTo(CarbonImmutable::parse($usage->started_at))
                || ! $endedAt->equalTo(CarbonImmutable::parse($usage->ended_at))
                || $normalOvertime !== (int) $usage->normal_overtime_minutes
                || $doubleOvertime !== (int) $usage->double_overtime_minutes
                || $tripleOvertime !== (int) $usage->triple_overtime_minutes
                || $this->math->compare($nightOutCount, (string) $usage->night_out_count) !== 0;
            if ($varianceExists && empty(trim((string) ($data['variance_reason'] ?? '')))) {
                throw new InvalidArgumentException('A variance reason is required when commercial facts differ from physical usage.');
            }

            $fact->forceFill([
                'started_at' => $startedAt,
                'ended_at' => $endedAt,
                'start_odometer' => $startOdometer,
                'end_odometer' => $endOdometer,
                'commercial_distance_km' => $commercialDistance,
                'working_minutes' => $workingMinutes,
                'normal_overtime_minutes' => $normalOvertime,
                'double_overtime_minutes' => $doubleOvertime,
                'triple_overtime_minutes' => $tripleOvertime,
                'night_out_count' => $nightOutCount,
                'reference_number' => $data['reference_number'] ?? null,
                'variance_reason' => $data['variance_reason'] ?? null,
                'remarks' => $data['remarks'] ?? null,
                'status' => RentalUsageFactStatus::Draft,
                'row_version' => $fact->row_version + 1,
                'updated_by' => $userId,
            ])->save();

            return $fact->refresh()->load(['context.agreement.customer', 'context.agreement.supplier']);
        });
    }

    public function transition(
        RentalUsageFact $fact,
        RentalUsageFactStatus $to,
        int $expectedVersion,
        ?int $userId,
        ?string $reason = null,
    ): RentalUsageFact {
        return DB::transaction(function () use ($fact, $to, $expectedVersion, $userId, $reason): RentalUsageFact {
            $fact = RentalUsageFact::query()
                ->with(['usageLog', 'context'])
                ->lockForUpdate()
                ->findOrFail($fact->getKey());
            $this->assertVersion($fact, $expectedVersion);
            $from = $fact->status;
            if ($from === $to) {
                return $fact;
            }
            if (! in_array($to->value, self::TRANSITIONS[$from->value] ?? [], true)) {
                throw new InvalidArgumentException("Invalid commercial usage transition from {$from->value} to {$to->value}.");
            }
            if ($to === RentalUsageFactStatus::Approved
                && $fact->usageLog->status !== RentalUsageStatus::Approved) {
                throw new InvalidArgumentException('Physical usage must be approved before commercial facts can be approved.');
            }
            if ($to === RentalUsageFactStatus::Reversed) {
                if (empty(trim((string) $reason))) {
                    throw new InvalidArgumentException('A reversal reason is required.');
                }
                $consumed = $fact->context->calculationLines()
                    ->where('status', RentalCalculationLineStatus::Approved->value)
                    ->whereHas('run', fn (Builder $query) => $query
                        ->where('calculation_status', RentalCalculationStatus::Approved->value))
                    ->exists();
                if ($consumed) {
                    throw new InvalidArgumentException('Reverse the approved calculation before reversing commercial usage facts.');
                }
            }

            $fact->forceFill([
                'status' => $to,
                'submitted_by' => $to === RentalUsageFactStatus::Submitted ? $userId : $fact->submitted_by,
                'submitted_at' => $to === RentalUsageFactStatus::Submitted ? now() : $fact->submitted_at,
                'approved_by' => $to === RentalUsageFactStatus::Approved ? $userId : $fact->approved_by,
                'approved_at' => $to === RentalUsageFactStatus::Approved ? now() : $fact->approved_at,
                'rejected_by' => $to === RentalUsageFactStatus::Rejected ? $userId : $fact->rejected_by,
                'rejected_at' => $to === RentalUsageFactStatus::Rejected ? now() : $fact->rejected_at,
                'reversed_by' => $to === RentalUsageFactStatus::Reversed ? $userId : $fact->reversed_by,
                'reversed_at' => $to === RentalUsageFactStatus::Reversed ? now() : $fact->reversed_at,
                'reversal_reason' => $to === RentalUsageFactStatus::Reversed ? $reason : $fact->reversal_reason,
                'row_version' => $fact->row_version + 1,
                'updated_by' => $userId,
            ])->save();

            return $fact->refresh()->load(['context.agreement.customer', 'context.agreement.supplier']);
        });
    }

    public function reverseForUsage(RentalUsageLog $usage, ?int $userId, string $reason): void
    {
        $facts = RentalUsageFact::query()
            ->where('usage_log_id', $usage->getKey())
            ->whereNot('status', RentalUsageFactStatus::Reversed->value)
            ->lockForUpdate()
            ->get();
        foreach ($facts as $fact) {
            $fact->forceFill([
                'status' => RentalUsageFactStatus::Reversed,
                'reversed_by' => $userId,
                'reversed_at' => now(),
                'reversal_reason' => $reason,
                'row_version' => $fact->row_version + 1,
                'updated_by' => $userId,
            ])->save();
        }
    }

    private function assertVersion(RentalUsageFact $fact, int $expectedVersion): void
    {
        if ((int) $fact->row_version !== $expectedVersion) {
            throw new InvalidArgumentException('Commercial usage facts changed since they were loaded. Reload and try again.');
        }
    }
}
