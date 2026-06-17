<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\VehicleRental\Enums\RentalAgreementDirection;
use Modules\VehicleRental\Enums\RentalAgreementStatus;
use Modules\VehicleRental\Enums\RentalBillingBasis;
use Modules\VehicleRental\Enums\RentalChargeCalculationType;
use Modules\VehicleRental\Enums\RentalChargeInvoiceStatus;
use Modules\VehicleRental\Enums\RentalChargeStatus;
use Modules\VehicleRental\Enums\RentalExpenseFinancialTreatment;
use Modules\VehicleRental\Enums\RentalExpenseStatus;
use Modules\VehicleRental\Enums\RentalExpenseType;
use Modules\VehicleRental\Enums\RentalFinancialSide;
use Modules\VehicleRental\Enums\RentalRateUnit;
use Modules\VehicleRental\Enums\RentalUsageEventType;
use Modules\VehicleRental\Enums\RentalUsageLogStatus;
use Modules\VehicleRental\Models\RentalAgreement;
use Modules\VehicleRental\Models\RentalBillingPeriod;
use Modules\VehicleRental\Models\RentalCharge;
use Modules\VehicleRental\Models\RentalChargeCalculation;
use Modules\VehicleRental\Models\RentalChargeRun;
use Modules\VehicleRental\Models\RentalExpense;
use Modules\VehicleRental\Models\RentalUsageContext;
use Modules\VehicleRental\Models\RentalUsageEvent;

final class RentalChargeCalculationService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly RentalChargeService $charges,
        private readonly RentalBillingPeriodService $billingPeriods,
    ) {}

    /**
     * @return Collection<int, RentalCharge>
     */
    public function calculate(RentalAgreement $agreement, bool $replace = false): Collection
    {
        $this->assertCalculable($agreement);

        return DB::transaction(function () use ($agreement, $replace): Collection {
            $agreement = RentalAgreement::query()
                ->lockForUpdate()
                ->with([
                    'rateSnapshot',
                    'operationalUsageLogs.events',
                    'operationalUsageLogs.contexts',
                    'expenses',
                    'returnInspections',
                    'charges',
                ])
                ->findOrFail($agreement->getKey());
            if ($agreement->rateSnapshot === null) {
                throw new InvalidArgumentException('Agreement rate snapshot is missing.');
            }
            $this->assertCalculable($agreement);
            $this->lockCalculationInputs($agreement);
            $agreement->load([
                'rateSnapshot',
                'operationalUsageLogs.events',
                'operationalUsageLogs.contexts',
                'usageContexts.usageLog',
                'expenses',
                'returnInspections',
                'charges',
            ]);
            $created = collect();
            $periods = $this->billingPeriods->eligiblePeriods($agreement);
            if ($periods->isEmpty()) {
                throw new InvalidArgumentException('No closed billing periods are eligible for rental charge calculation.');
            }
            foreach ($periods as $period) {
                $billingPeriod = $this->billingPeriods->persist($agreement, $period);
                $run = $this->prepareChargeRun($agreement, $billingPeriod, $period, $replace);
                $superseded = $this->prepareRegeneration($agreement, $billingPeriod, $run, $replace);
                $calculations = collect();
                foreach ($this->calculationRows($agreement, $period, $billingPeriod, $run) as $row) {
                    if ($this->math->isZero($row['chargeable_quantity']) || $this->math->isZero($row['amount'])) {
                        continue;
                    }
                    $sourceKey = $this->sourceKey($row);
                    $row['supersedes_calculation_id'] = $superseded[$sourceKey] ?? null;
                    $calculations->push(RentalChargeCalculation::query()->create($row));
                }
                if ($calculations->isEmpty()) {
                    $run->forceFill([
                        'calculation_status' => 'empty',
                        'calculated_at' => now(),
                    ])->save();

                    continue;
                }
                $charges = $this->charges->createFromCalculations($agreement, $calculations);
                $this->markExpensesCharged($agreement, $calculations);
                $this->updateRunTotals($run, $charges);
                $created = $created->merge($charges);
            }

            if ($created->isEmpty()) {
                throw new InvalidArgumentException('No rental charge calculations were produced.');
            }

            return $created;
        });
    }

    /**
     * @return Collection<int, RentalCharge>
     */
    public function preview(RentalAgreement $agreement): Collection
    {
        $this->assertCalculable($agreement);
        $agreement = RentalAgreement::query()
            ->with([
                'rateSnapshot',
                'operationalUsageLogs.events',
                'operationalUsageLogs.contexts',
                'usageContexts.usageLog',
                'expenses',
                'returnInspections',
            ])
            ->findOrFail($agreement->getKey());
        if ($agreement->rateSnapshot === null) {
            throw new InvalidArgumentException('Agreement rate snapshot is missing.');
        }

        $calculations = collect();
        $index = 0;
        foreach ($this->billingPeriods->eligiblePeriods($agreement) as $period) {
            $billingPeriod = new RentalBillingPeriod;
            $billingPeriod->forceFill([
                'id' => -((int) $period['sequence']),
                'period_start' => $period['start'],
                'period_end' => $period['end'],
                'billing_cycle_key' => $period['key'],
                'period_sequence' => $period['sequence'],
            ]);
            $run = new RentalChargeRun;
            $run->forceFill([
                'id' => -((int) $period['sequence']),
                'billing_period_id' => $billingPeriod->getKey(),
                'run_version' => 1,
            ]);
            foreach ($this->calculationRows($agreement, $period, $billingPeriod, $run) as $row) {
                if ($this->math->isZero($row['chargeable_quantity']) || $this->math->isZero($row['amount'])) {
                    continue;
                }
                $calculation = new RentalChargeCalculation;
                $calculation->forceFill($row);
                $calculation->setAttribute('id', -(++$index));
                $calculations->push($calculation);
            }
        }

        return $this->charges->previewFromCalculations($agreement, $calculations);
    }

    /**
     * @param  array{contexts: list<array{agreement: RentalAgreement}>}  $resolved
     * @param  list<array<string, mixed>>  $trips
     * @return array<string, mixed>
     */
    public function previewRunningChart(array $resolved, array $trips): array
    {
        $distance = '0.000000';
        $workingMinutes = 0;
        $classifiedHours = '0.000000';
        $eventQuantities = [];

        foreach ($trips as $trip) {
            $startOdometer = (string) ($trip['start_odometer'] ?? '0');
            $endOdometer = (string) ($trip['end_odometer'] ?? '0');
            if ($this->math->compare($endOdometer, $startOdometer) < 0) {
                throw new InvalidArgumentException('Preview finish odometer must be greater than or equal to start odometer.');
            }
            $distance = $this->math->add($distance, $this->math->sub($endOdometer, $startOdometer));
            $workingMinutes += $this->runningChartWorkingMinutes(
                (string) ($trip['usage_date'] ?? now()->toDateString()),
                isset($trip['start_time']) ? (string) $trip['start_time'] : null,
                isset($trip['end_time']) ? (string) $trip['end_time'] : null,
            );
            foreach (($trip['events'] ?? []) as $event) {
                $type = RentalUsageEventType::from((string) $event['event_type']);
                $quantity = $this->math->normalize((string) $event['quantity']);
                $eventQuantities[$type->value] = $this->math->add(
                    $eventQuantities[$type->value] ?? '0.000000',
                    $quantity,
                );
                if (in_array($type, [
                    RentalUsageEventType::Overtime,
                    RentalUsageEventType::DoubleOvertime,
                ], true)) {
                    $classifiedHours = $this->math->add($classifiedHours, $quantity);
                }
            }
        }

        $workingHours = $this->math->div((string) $workingMinutes, '60.000000');
        $contextRows = [];
        $revenue = '0.000000';
        $cost = '0.000000';

        foreach ($resolved['contexts'] as $context) {
            $agreement = $context['agreement'];
            $agreement->loadMissing('rateSnapshot');
            if ($agreement->rateSnapshot === null) {
                throw new InvalidArgumentException('Agreement rate snapshot is missing.');
            }

            $lines = [];
            $baseQuantity = $this->runningChartBaseQuantity(
                $agreement,
                $distance,
                $workingHours,
                count($trips),
            );
            $this->appendPreviewLine(
                $lines,
                'base_rental',
                $baseQuantity,
                (string) $agreement->rateSnapshot->base_rate,
                $agreement->rateSnapshot->rate_unit->value,
            );

            $extraKm = $this->positiveSub($distance, (string) $agreement->rateSnapshot->allowed_km);
            $this->appendPreviewLine(
                $lines,
                'extra_km',
                $extraKm,
                (string) $agreement->rateSnapshot->extra_km_rate,
                'km',
            );

            $hoursAfterAllowance = $this->positiveSub(
                $workingHours,
                (string) $agreement->rateSnapshot->allowed_hours,
            );
            $extraHours = $this->positiveSub($hoursAfterAllowance, $classifiedHours);
            $this->appendPreviewLine(
                $lines,
                'extra_hour',
                $extraHours,
                (string) $agreement->rateSnapshot->extra_hour_rate,
                'hour',
            );

            foreach ($eventQuantities as $eventType => $quantity) {
                $type = RentalUsageEventType::from($eventType);
                if (in_array($type, [
                    RentalUsageEventType::ExtraHour,
                    RentalUsageEventType::ExtraKm,
                ], true)) {
                    continue;
                }
                $this->appendPreviewLine(
                    $lines,
                    $this->eventCalculationTypeValue($type),
                    $quantity,
                    $this->eventRate($agreement, $type),
                    $this->eventUnit($type),
                );
            }

            $amount = $this->math->sum(array_map(
                fn (array $line): string => (string) $line['amount'],
                $lines,
            ));
            $side = RentalFinancialSide::fromDirection($agreement->direction)->value;
            if ($side === RentalFinancialSide::Revenue->value) {
                $revenue = $this->math->add($revenue, $amount);
            } else {
                $cost = $this->math->add($cost, $amount);
            }
            $contextRows[] = [
                'agreement_id' => (int) $agreement->getKey(),
                'financial_side' => $side,
                'estimated_total' => $amount,
                'lines' => $lines,
            ];
        }

        return [
            'daily_km' => $distance,
            'working_minutes' => $workingMinutes,
            'working_hours' => $workingHours,
            'overtime_hours' => $classifiedHours,
            'customer_revenue' => $revenue,
            'owner_cost' => $cost,
            'estimated_margin' => $this->math->sub($revenue, $cost),
            'contexts' => $contextRows,
            'persistent' => false,
        ];
    }

    private function assertCalculable(RentalAgreement $agreement): void
    {
        if (! in_array($agreement->status, [
            RentalAgreementStatus::Active,
            RentalAgreementStatus::Returned,
            RentalAgreementStatus::Completed,
        ], true)) {
            throw new InvalidArgumentException('Rental charges can only be calculated for active or returned agreements.');
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function calculationRows(
        RentalAgreement $agreement,
        array $period,
        RentalBillingPeriod $billingPeriod,
        RentalChargeRun $run,
    ): array {
        $rows = [];
        $version = (int) $run->run_version;
        $periodLogs = $this->periodUsageLogs($agreement, $period);
        $baseQuantity = $this->baseQuantity($agreement, $period, $periodLogs);
        $this->append(
            $rows,
            $agreement,
            $period,
            $billingPeriod,
            $run,
            null,
            'rental_agreement',
            (int) $agreement->getKey(),
            RentalChargeCalculationType::BaseRental,
            $baseQuantity,
            '0.000000',
            $baseQuantity,
            (string) $agreement->rateSnapshot->base_rate,
            $agreement->rateSnapshot->rate_unit->value,
            'base_rate',
            'Base rental charge',
            $version,
        );
        $this->appendDerivedUsage($rows, $agreement, $period, $billingPeriod, $run, $periodLogs, $version);
        $this->appendUsageEvents($rows, $agreement, $period, $billingPeriod, $run, $periodLogs, $version);
        $this->appendExpenses($rows, $agreement, $period, $billingPeriod, $run, $version);
        $this->appendDamages($rows, $agreement, $period, $billingPeriod, $run, $version);

        return $rows;
    }

    /**
     * @return array<string, int>
     */
    private function prepareRegeneration(
        RentalAgreement $agreement,
        RentalBillingPeriod $billingPeriod,
        RentalChargeRun $run,
        bool $replace,
    ): array {
        $previousRuns = $agreement->chargeRuns()
            ->where('billing_period_id', $billingPeriod->getKey())
            ->where('financial_side', RentalFinancialSide::fromDirection($agreement->direction)->value)
            ->where('rate_snapshot_id', $agreement->rateSnapshot?->getKey())
            ->whereKeyNot($run->getKey())
            ->lockForUpdate()
            ->get();
        if ($previousRuns->isEmpty()) {
            return [];
        }
        if (! $replace) {
            throw new InvalidArgumentException('Rental charges have already been generated for this billing period.');
        }
        $protected = RentalCharge::query()
            ->whereIn('charge_run_id', $previousRuns->pluck('id'))
            ->where(function ($query): void {
                $query->where('status', RentalChargeStatus::Approved->value)
                    ->orWhere('invoice_status', '!=', RentalChargeInvoiceStatus::NotInvoiced->value);
            })
            ->exists();
        if ($protected) {
            throw new InvalidArgumentException('Approved or invoiced rental charges cannot be regenerated.');
        }
        $previous = RentalChargeCalculation::query()
            ->whereIn('charge_run_id', $previousRuns->pluck('id'))
            ->where('status', 'draft')
            ->lockForUpdate()
            ->get();
        RentalCharge::query()
            ->whereIn('charge_run_id', $previousRuns->pluck('id'))
            ->where('status', RentalChargeStatus::Draft->value)
            ->lockForUpdate()
            ->update(['status' => RentalChargeStatus::Cancelled->value]);
        RentalChargeCalculation::query()
            ->whereIn('charge_run_id', $previousRuns->pluck('id'))
            ->where('status', 'draft')
            ->update(['status' => 'superseded']);
        RentalChargeRun::query()
            ->whereIn('id', $previousRuns->pluck('id'))
            ->update([
                'calculation_status' => 'superseded',
                'approval_status' => 'cancelled',
                'updated_at' => now(),
            ]);

        return $previous->mapWithKeys(
            fn (RentalChargeCalculation $calculation): array => [
                $this->sourceKey($calculation->getAttributes()) => (int) $calculation->getKey(),
            ],
        )->all();
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function appendDerivedUsage(
        array &$rows,
        RentalAgreement $agreement,
        array $period,
        RentalBillingPeriod $billingPeriod,
        RentalChargeRun $run,
        Collection $approved,
        int $version,
    ): void {
        $distance = $this->math->sum(
            $approved->pluck('distance_km')->map(fn ($value): string => (string) $value)->all(),
        );
        $extraKm = $this->positiveSub($distance, (string) $agreement->rateSnapshot->allowed_km);
        $this->append(
            $rows,
            $agreement,
            $period,
            $billingPeriod,
            $run,
            null,
            'rental_agreement',
            (int) $agreement->getKey(),
            RentalChargeCalculationType::ExtraKm,
            $distance,
            (string) $agreement->rateSnapshot->allowed_km,
            $extraKm,
            (string) $agreement->rateSnapshot->extra_km_rate,
            'km',
            'distance_above_allowance',
            'Extra mileage above the agreement allowance',
            $version,
        );

        $workingHours = $this->math->div(
            (string) $approved->sum('working_minutes'),
            '60.000000',
        );
        $classifiedHours = '0.000000';
        foreach ($approved as $log) {
            foreach ($log->events->whereIn('event_type', [
                RentalUsageEventType::Overtime,
                RentalUsageEventType::DoubleOvertime,
            ]) as $event) {
                $classifiedHours = $this->math->add($classifiedHours, (string) $event->quantity);
            }
        }
        $hoursAfterAllowance = $this->positiveSub(
            $workingHours,
            (string) $agreement->rateSnapshot->allowed_hours,
        );
        $extraHours = $this->positiveSub($hoursAfterAllowance, $classifiedHours);
        $this->append(
            $rows,
            $agreement,
            $period,
            $billingPeriod,
            $run,
            null,
            'rental_agreement',
            (int) $agreement->getKey(),
            RentalChargeCalculationType::ExtraHour,
            $workingHours,
            (string) $agreement->rateSnapshot->allowed_hours,
            $extraHours,
            (string) $agreement->rateSnapshot->extra_hour_rate,
            'hour',
            'extra_hours_excluding_overtime',
            'Extra hours above allowance after overtime classification',
            $version,
        );
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function appendUsageEvents(
        array &$rows,
        RentalAgreement $agreement,
        array $period,
        RentalBillingPeriod $billingPeriod,
        RentalChargeRun $run,
        Collection $logs,
        int $version,
    ): void {
        foreach ($logs as $log) {
            $context = $log->contexts->firstWhere('agreement_id', $agreement->getKey());
            if (! $context instanceof RentalUsageContext) {
                throw new InvalidArgumentException('Approved usage is missing its agreement calculation context.');
            }
            $hasHoliday = $log->events->contains('event_type', RentalUsageEventType::Holiday);
            foreach ($log->events as $event) {
                if (in_array($event->event_type, [
                    RentalUsageEventType::ExtraHour,
                    RentalUsageEventType::ExtraKm,
                ], true)) {
                    continue;
                }
                if ($hasHoliday && $event->event_type === RentalUsageEventType::Weekend) {
                    continue;
                }
                $type = $this->eventCalculationType($event);
                $this->append(
                    $rows,
                    $agreement,
                    $period,
                    $billingPeriod,
                    $run,
                    $context,
                    'rental_usage_event',
                    (int) $event->getKey(),
                    $type,
                    (string) $event->quantity,
                    '0.000000',
                    (string) $event->quantity,
                    $this->eventRate($agreement, $event->event_type),
                    $this->eventUnit($event->event_type),
                    $event->event_type === RentalUsageEventType::Weekend
                        ? 'weekend_unless_holiday'
                        : 'additive_operational_event',
                    $event->remarks ?? str($event->event_type->value)->replace('_', ' ')->title()->toString(),
                    $version,
                );
            }
        }
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function appendExpenses(
        array &$rows,
        RentalAgreement $agreement,
        array $period,
        RentalBillingPeriod $billingPeriod,
        RentalChargeRun $run,
        int $version,
    ): void {
        $start = CarbonImmutable::parse($period['start']);
        $end = CarbonImmutable::parse($period['end']);
        foreach ($agreement->expenses->filter(
            fn (RentalExpense $expense): bool => $this->expenseBelongsToPeriod($expense, $agreement, $start, $end)
                && in_array($expense->status, [
                    RentalExpenseStatus::Approved,
                    RentalExpenseStatus::Charged,
                ], true)
                && in_array(
                    $expense->financial_treatment->value,
                    $this->chargeableTreatments($agreement),
                    true,
                ),
        ) as $expense) {
            $context = $expense->usage_log_id === null
                ? null
                : $agreement->usageContexts->firstWhere('usage_log_id', $expense->usage_log_id);
            $rate = (string) $expense->recovery_base_amount;
            if ($this->math->isZero($rate)) {
                $rate = (string) $expense->amount;
            }
            $this->append(
                $rows,
                $agreement,
                $period,
                $billingPeriod,
                $run,
                $context instanceof RentalUsageContext ? $context : null,
                'rental_expense',
                (int) $expense->getKey(),
                $this->expenseCalculationType($expense),
                '1.000000',
                '0.000000',
                '1.000000',
                $rate,
                'expense',
                'approved_expense_financial_treatment',
                $expense->description ?? str($expense->expense_type->value)->title()->append(' expense')->toString(),
                $version,
                $expense->recovery_tax_group_id,
            );
        }
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function appendDamages(
        array &$rows,
        RentalAgreement $agreement,
        array $period,
        RentalBillingPeriod $billingPeriod,
        RentalChargeRun $run,
        int $version,
    ): void {
        if ($agreement->direction !== RentalAgreementDirection::Outbound) {
            return;
        }
        $start = CarbonImmutable::parse($period['start']);
        $end = CarbonImmutable::parse($period['end']);
        $isFinal = (bool) $period['is_final'];
        foreach ($agreement->returnInspections as $inspection) {
            $outside = $inspection->inspected_at->lessThan($start)
                || ($inspection->inspected_at->greaterThanOrEqualTo($end)
                    && ! ($isFinal && $inspection->inspected_at->equalTo($end)));
            if (! $inspection->is_damage_billable
                || $this->math->compare((string) $inspection->damage_amount, '0.000000') <= 0
                || $outside) {
                continue;
            }
            $this->append(
                $rows,
                $agreement,
                $period,
                $billingPeriod,
                $run,
                null,
                'rental_return_inspection',
                (int) $inspection->getKey(),
                RentalChargeCalculationType::Damage,
                '1.000000',
                '0.000000',
                '1.000000',
                (string) $inspection->damage_amount,
                'damage',
                'approved_return_damage',
                $inspection->damage_notes ?? 'Rental vehicle damage charge',
                $version,
            );
        }
    }

    private function baseQuantity(RentalAgreement $agreement, array $period, Collection $periodLogs): string
    {
        $start = CarbonImmutable::parse($period['start']);
        $end = CarbonImmutable::parse($period['end']);
        $seconds = max(1, $end->getTimestamp() - $start->getTimestamp());

        return match ($agreement->rateSnapshot->rate_unit) {
            RentalRateUnit::Trip => '1.000000',
            RentalRateUnit::Km => $this->math->sum($periodLogs
                ->pluck('distance_km')->map(fn ($value): string => (string) $value)->all()),
            RentalRateUnit::Hour => $this->ceilingUnits($seconds, 3600),
            RentalRateUnit::Day => $this->ceilingUnits($seconds, 86400),
            RentalRateUnit::Week => $this->ceilingUnits($seconds, 604800),
            RentalRateUnit::Month => $this->monthQuantity($agreement, $start, $end),
        };
    }

    private function ceilingUnits(int $seconds, int $unitSeconds): string
    {
        return $this->math->normalize((string) max(1, intdiv($seconds + $unitSeconds - 1, $unitSeconds)));
    }

    private function monthQuantity(RentalAgreement $agreement, CarbonImmutable $start, CarbonImmutable $end): string
    {
        $total = '0.000000';
        $cursor = $start;
        while ($cursor->lessThan($end)) {
            [$denominatorStart, $denominatorEnd] = $this->monthlyDenominator($agreement, $cursor);
            $segmentEnd = $denominatorEnd->lessThan($end) ? $denominatorEnd : $end;
            $total = $this->math->add(
                $total,
                $this->durationRatio($cursor, $segmentEnd, $denominatorStart, $denominatorEnd),
            );
            $cursor = $segmentEnd;
        }

        return $this->math->isZero($total) ? '1.000000' : $total;
    }

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    private function monthlyDenominator(RentalAgreement $agreement, CarbonImmutable $cursor): array
    {
        return match ($agreement->billing_basis) {
            RentalBillingBasis::CalendarMonth => [
                $cursor->startOfMonth()->startOfDay(),
                $cursor->startOfMonth()->startOfDay()->addMonthNoOverflow(),
            ],
            RentalBillingBasis::FixedThirtyDay => [$cursor, $cursor->addDays(30)],
            default => [$cursor, $this->anniversaryBoundary($agreement, $cursor)],
        };
    }

    private function anniversaryBoundary(RentalAgreement $agreement, CarbonImmutable $cursor): CarbonImmutable
    {
        $anchor = CarbonImmutable::parse($agreement->start_at);
        $anchorDay = (int) $anchor->day;
        $anchorIsEndOfMonth = $anchorDay === (int) $anchor->daysInMonth;
        $next = $cursor->addMonthNoOverflow();
        $day = $anchorIsEndOfMonth ? (int) $next->daysInMonth : min($anchorDay, (int) $next->daysInMonth);

        return $next->setDay($day);
    }

    private function durationRatio(
        CarbonImmutable $numeratorStart,
        CarbonImmutable $numeratorEnd,
        CarbonImmutable $denominatorStart,
        CarbonImmutable $denominatorEnd,
    ): string {
        $numerator = max(0, $numeratorEnd->getTimestamp() - $numeratorStart->getTimestamp());
        $denominator = max(1, $denominatorEnd->getTimestamp() - $denominatorStart->getTimestamp());

        return $this->math->div((string) $numerator, (string) $denominator);
    }

    private function runningChartWorkingMinutes(
        string $usageDate,
        ?string $startTime,
        ?string $endTime,
    ): int {
        if ($startTime === null || $endTime === null) {
            return 0;
        }
        $start = CarbonImmutable::parse($usageDate.' '.$startTime);
        $end = CarbonImmutable::parse($usageDate.' '.$endTime);
        if ($end->lessThan($start)) {
            $end = $end->addDay();
        }

        return intdiv($end->getTimestamp() - $start->getTimestamp(), 60);
    }

    private function runningChartBaseQuantity(
        RentalAgreement $agreement,
        string $distance,
        string $workingHours,
        int $tripCount,
    ): string {
        if ($tripCount === 0) {
            return '0.000000';
        }

        return match ($agreement->rateSnapshot->rate_unit) {
            RentalRateUnit::Trip => $this->math->normalize((string) $tripCount),
            RentalRateUnit::Km => $distance,
            RentalRateUnit::Hour => $workingHours,
            RentalRateUnit::Day,
            RentalRateUnit::Week,
            RentalRateUnit::Month => '1.000000',
        };
    }

    /**
     * @param  list<array<string, string>>  $lines
     */
    private function appendPreviewLine(
        array &$lines,
        string $type,
        string $quantity,
        string $rate,
        ?string $unit,
    ): void {
        $quantity = $this->math->normalize($quantity);
        $rate = $this->math->normalize($rate);
        if ($this->math->isZero($quantity) || $this->math->isZero($rate)) {
            return;
        }

        $lines[] = [
            'type' => $type,
            'quantity' => $quantity,
            'rate' => $rate,
            'unit' => $unit,
            'amount' => $this->math->mul($quantity, $rate),
        ];
    }

    private function eventCalculationTypeValue(RentalUsageEventType $type): string
    {
        return match ($type) {
            RentalUsageEventType::ExtraHour => RentalChargeCalculationType::ExtraHour->value,
            RentalUsageEventType::ExtraKm => RentalChargeCalculationType::ExtraKm->value,
            RentalUsageEventType::Overtime => RentalChargeCalculationType::Overtime->value,
            RentalUsageEventType::DoubleOvertime => RentalChargeCalculationType::DoubleOvertime->value,
            RentalUsageEventType::NightShift => RentalChargeCalculationType::NightShift->value,
            RentalUsageEventType::Weekend => RentalChargeCalculationType::Weekend->value,
            RentalUsageEventType::Holiday => RentalChargeCalculationType::Holiday->value,
            RentalUsageEventType::DayOut => RentalChargeCalculationType::DayOut->value,
            RentalUsageEventType::NightOut => RentalChargeCalculationType::NightOut->value,
            RentalUsageEventType::Driver => RentalChargeCalculationType::Driver->value,
            RentalUsageEventType::Outstation => RentalChargeCalculationType::Outstation->value,
            RentalUsageEventType::Waiting => RentalChargeCalculationType::Waiting->value,
            RentalUsageEventType::Pass, RentalUsageEventType::Other => RentalChargeCalculationType::Other->value,
        };
    }

    private function eventCalculationType(RentalUsageEvent $event): RentalChargeCalculationType
    {
        return match ($event->event_type) {
            RentalUsageEventType::ExtraHour => RentalChargeCalculationType::ExtraHour,
            RentalUsageEventType::ExtraKm => RentalChargeCalculationType::ExtraKm,
            RentalUsageEventType::Overtime => RentalChargeCalculationType::Overtime,
            RentalUsageEventType::DoubleOvertime => RentalChargeCalculationType::DoubleOvertime,
            RentalUsageEventType::NightShift => RentalChargeCalculationType::NightShift,
            RentalUsageEventType::Weekend => RentalChargeCalculationType::Weekend,
            RentalUsageEventType::Holiday => RentalChargeCalculationType::Holiday,
            RentalUsageEventType::DayOut => RentalChargeCalculationType::DayOut,
            RentalUsageEventType::NightOut => RentalChargeCalculationType::NightOut,
            RentalUsageEventType::Driver => RentalChargeCalculationType::Driver,
            RentalUsageEventType::Outstation => RentalChargeCalculationType::Outstation,
            RentalUsageEventType::Waiting => RentalChargeCalculationType::Waiting,
            RentalUsageEventType::Pass, RentalUsageEventType::Other => RentalChargeCalculationType::Other,
        };
    }

    private function eventRate(RentalAgreement $agreement, RentalUsageEventType $type): string
    {
        $snapshot = $agreement->rateSnapshot;

        return (string) match ($type) {
            RentalUsageEventType::ExtraHour => $snapshot->extra_hour_rate,
            RentalUsageEventType::ExtraKm => $snapshot->extra_km_rate,
            RentalUsageEventType::Overtime => $snapshot->overtime_rate,
            RentalUsageEventType::DoubleOvertime => $snapshot->double_overtime_rate,
            RentalUsageEventType::NightShift => $snapshot->night_shift_rate,
            RentalUsageEventType::Weekend => $snapshot->weekend_rate,
            RentalUsageEventType::Holiday => $snapshot->holiday_rate,
            RentalUsageEventType::DayOut => $snapshot->day_out_rate,
            RentalUsageEventType::NightOut => $snapshot->night_out_rate,
            RentalUsageEventType::Driver => $snapshot->driver_rate,
            RentalUsageEventType::Outstation => $snapshot->outstation_rate,
            RentalUsageEventType::Waiting => $snapshot->waiting_hour_rate,
            RentalUsageEventType::Pass, RentalUsageEventType::Other => '0.000000',
        };
    }

    private function eventUnit(RentalUsageEventType $type): string
    {
        return match ($type) {
            RentalUsageEventType::Overtime,
            RentalUsageEventType::DoubleOvertime,
            RentalUsageEventType::Waiting => 'hour',
            default => 'event',
        };
    }

    private function expenseCalculationType(RentalExpense $expense): RentalChargeCalculationType
    {
        return match ($expense->expense_type) {
            RentalExpenseType::Fuel => RentalChargeCalculationType::Fuel,
            RentalExpenseType::Toll => RentalChargeCalculationType::Toll,
            RentalExpenseType::Parking => RentalChargeCalculationType::Parking,
            default => RentalChargeCalculationType::Other,
        };
    }

    /**
     * @return list<string>
     */
    private function chargeableTreatments(RentalAgreement $agreement): array
    {
        return $agreement->direction === RentalAgreementDirection::Outbound
            ? [RentalExpenseFinancialTreatment::CustomerBillable->value]
            : [RentalExpenseFinancialTreatment::OwnerPayable->value];
    }

    private function positiveSub(string $left, string $right): string
    {
        $result = $this->math->sub($left, $right);

        return $this->math->isNegative($result) ? '0.000000' : $result;
    }

    private function prepareChargeRun(
        RentalAgreement $agreement,
        RentalBillingPeriod $billingPeriod,
        array $period,
        bool $replace,
    ): RentalChargeRun {
        $side = RentalFinancialSide::fromDirection($agreement->direction);
        $existing = $agreement->chargeRuns()
            ->where('billing_period_id', $billingPeriod->getKey())
            ->where('financial_side', $side->value)
            ->where('rate_snapshot_id', $agreement->rateSnapshot?->getKey())
            ->lockForUpdate()
            ->get();
        $active = $existing->whereNotIn('calculation_status', ['superseded', 'cancelled']);
        if ($active->isNotEmpty() && ! $replace) {
            throw new InvalidArgumentException('Rental charges have already been generated for this billing period.');
        }
        $version = ((int) $existing->max('run_version')) + 1;
        $fingerprint = hash('sha256', implode('|', [
            (string) $agreement->tenant_id,
            (string) $agreement->getKey(),
            $side->value,
            (string) $agreement->rateSnapshot?->getKey(),
            (string) $billingPeriod->getKey(),
            (string) $version,
        ]));

        return RentalChargeRun::query()->create([
            'tenant_id' => $agreement->tenant_id,
            'organization_unit_id' => $agreement->organization_unit_id,
            'billing_period_id' => $billingPeriod->getKey(),
            'agreement_id' => $agreement->getKey(),
            'rate_snapshot_id' => $agreement->rateSnapshot?->getKey(),
            'agreement_direction' => $agreement->direction->value,
            'financial_side' => $side->value,
            'party_type' => $agreement->party_type->value,
            'party_id' => $agreement->party_id,
            'billing_period_start' => $period['start'],
            'billing_period_end' => $period['end'],
            'billing_cycle_key' => $period['key'],
            'period_sequence' => $period['sequence'],
            'run_version' => $version,
            'calculation_status' => 'draft',
            'approval_status' => 'pending',
            'invoice_status' => RentalChargeInvoiceStatus::NotInvoiced->value,
            'fingerprint' => $fingerprint,
            'calculated_at' => now(),
        ]);
    }

    private function updateRunTotals(RentalChargeRun $run, Collection $charges): void
    {
        $run->forceFill([
            'calculation_status' => 'calculated',
            'amount_total' => $this->math->sum($charges->pluck('amount')->map(fn ($value): string => (string) $value)->all()),
            'tax_total' => $this->math->sum($charges->pluck('tax_amount')->map(fn ($value): string => (string) $value)->all()),
            'withholding_total' => $this->math->sum($charges->pluck('withholding_amount')->map(fn ($value): string => (string) $value)->all()),
            'grand_total' => $this->math->sum($charges->pluck('total_amount')->map(fn ($value): string => (string) $value)->all()),
            'calculated_at' => now(),
        ])->save();
    }

    private function markExpensesCharged(RentalAgreement $agreement, Collection $calculations): void
    {
        $expenseIds = $calculations
            ->where('source_type', 'rental_expense')
            ->pluck('source_id')
            ->map(fn ($id): int => (int) $id)
            ->values();
        if ($expenseIds->isEmpty()) {
            return;
        }
        $agreement->expenses()
            ->whereIn('id', $expenseIds)
            ->where('status', RentalExpenseStatus::Approved->value)
            ->whereIn('financial_treatment', $this->chargeableTreatments($agreement))
            ->update([
                'status' => RentalExpenseStatus::Charged->value,
                'charge_generation_status' => 'generated',
                'updated_at' => now(),
            ]);
    }

    private function periodUsageLogs(RentalAgreement $agreement, array $period): Collection
    {
        $start = CarbonImmutable::parse($period['start']);
        $end = CarbonImmutable::parse($period['end']);

        return $agreement->operationalUsageLogs
            ->where('status', RentalUsageLogStatus::Approved)
            ->filter(fn ($log): bool => $log->effective_at !== null
                && $log->effective_at->greaterThanOrEqualTo($start)
                && $log->effective_at->lessThan($end))
            ->values();
    }

    private function expenseBelongsToPeriod(
        RentalExpense $expense,
        RentalAgreement $agreement,
        CarbonImmutable $start,
        CarbonImmutable $end,
    ): bool {
        if ($expense->usage_log_id !== null) {
            return $agreement->usageContexts
                ->where('usage_log_id', $expense->usage_log_id)
                ->contains(function (RentalUsageContext $context) use ($start, $end): bool {
                    $log = $context->usageLog;

                    return $log !== null
                        && $log->effective_at !== null
                        && $log->effective_at->greaterThanOrEqualTo($start)
                        && $log->effective_at->lessThan($end);
                });
        }
        $expenseDate = CarbonImmutable::parse($expense->expense_date)->startOfDay();

        return $expenseDate->greaterThanOrEqualTo($start->startOfDay())
            && $expenseDate->lessThan($end->startOfDay());
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function append(
        array &$rows,
        RentalAgreement $agreement,
        array $period,
        RentalBillingPeriod $billingPeriod,
        RentalChargeRun $run,
        ?RentalUsageContext $context,
        string $sourceType,
        int $sourceId,
        RentalChargeCalculationType $type,
        string $measuredQuantity,
        string $allowedQuantity,
        string $chargeableQuantity,
        string $rate,
        ?string $unit,
        string $appliedRule,
        string $description,
        int $version,
        ?int $taxGroupId = null,
    ): void {
        $measuredQuantity = $this->math->normalize($measuredQuantity);
        $allowedQuantity = $this->math->normalize($allowedQuantity);
        $chargeableQuantity = $this->math->normalize($chargeableQuantity);
        $rate = $this->math->normalize($rate);
        $side = RentalFinancialSide::fromDirection($agreement->direction);
        $fingerprint = hash('sha256', implode('|', [
            (string) $agreement->tenant_id,
            (string) $run->getKey(),
            (string) $billingPeriod->getKey(),
            CarbonImmutable::parse($period['start'])->toDateTimeString(),
            CarbonImmutable::parse($period['end'])->toDateTimeString(),
            (string) ($context?->getKey() ?? 'agreement'),
            (string) $agreement->getKey(),
            (string) $agreement->rateSnapshot?->getKey(),
            $side->value,
            $sourceType,
            (string) $sourceId,
            $type->value,
            (string) $version,
        ]));
        $rows[] = [
            'tenant_id' => $agreement->tenant_id,
            'organization_unit_id' => $agreement->organization_unit_id,
            'billing_period_id' => $billingPeriod->getKey(),
            'charge_run_id' => $run->getKey(),
            'agreement_id' => $agreement->getKey(),
            'agreement_vehicle_id' => $context?->agreement_vehicle_id,
            'usage_log_id' => $context?->usage_log_id,
            'usage_context_id' => $context?->getKey(),
            'rate_snapshot_id' => $agreement->rateSnapshot?->getKey(),
            'agreement_direction' => $agreement->direction->value,
            'financial_side' => $side->value,
            'party_type' => $agreement->party_type->value,
            'party_id' => $agreement->party_id,
            'billing_period_start' => $period['start'],
            'billing_period_end' => $period['end'],
            'billing_cycle_key' => $period['key'],
            'period_sequence' => $period['sequence'],
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'calculation_type' => $type->value,
            'measured_quantity' => $measuredQuantity,
            'allowed_quantity' => $allowedQuantity,
            'chargeable_quantity' => $chargeableQuantity,
            'unit' => $unit,
            'rate' => $rate,
            'multiplier' => '1.000000',
            'amount' => $this->math->mul($chargeableQuantity, $rate),
            'tax_group_id' => $taxGroupId ?? $agreement->rateSnapshot?->tax_profile_id,
            'tax_amount' => '0.000000',
            'withholding_amount' => '0.000000',
            'total_amount' => $this->math->mul($chargeableQuantity, $rate),
            'applied_rule' => $appliedRule,
            'calculation_version' => $version,
            'status' => 'draft',
            'fingerprint' => $fingerprint,
            'description' => $description,
        ];
    }

    private function lockCalculationInputs(RentalAgreement $agreement): void
    {
        $agreement->rateSnapshot()->lockForUpdate()->firstOrFail();
        $usageLogIds = $agreement->usageContexts()->lockForUpdate()->pluck('usage_log_id');
        if ($usageLogIds->isNotEmpty()) {
            DB::table('rental_usage_logs')
                ->where('tenant_id', $agreement->tenant_id)
                ->where('organization_unit_id', $agreement->organization_unit_id)
                ->whereIn('id', $usageLogIds)
                ->lockForUpdate()
                ->get();
            DB::table('rental_usage_events')
                ->where('tenant_id', $agreement->tenant_id)
                ->where('organization_unit_id', $agreement->organization_unit_id)
                ->whereIn('usage_log_id', $usageLogIds)
                ->lockForUpdate()
                ->get();
        }
        $agreement->expenses()->lockForUpdate()->get();
        $agreement->returnInspections()->lockForUpdate()->get();
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function sourceKey(array $row): string
    {
        return implode('|', [
            (string) $row['source_type'],
            (string) $row['source_id'],
            $row['calculation_type'] instanceof RentalChargeCalculationType
                ? $row['calculation_type']->value
                : (string) $row['calculation_type'],
        ]);
    }
}
