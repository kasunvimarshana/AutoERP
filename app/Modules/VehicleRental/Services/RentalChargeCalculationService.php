<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\VehicleRental\Enums\RentalAgreementDirection;
use Modules\VehicleRental\Enums\RentalAgreementStatus;
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
use Modules\VehicleRental\Models\RentalCharge;
use Modules\VehicleRental\Models\RentalChargeCalculation;
use Modules\VehicleRental\Models\RentalExpense;
use Modules\VehicleRental\Models\RentalUsageContext;
use Modules\VehicleRental\Models\RentalUsageEvent;

final class RentalChargeCalculationService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly RentalChargeService $charges,
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
                'usageContexts',
                'expenses',
                'returnInspections',
                'charges',
            ]);
            $version = max(
                1,
                ((int) $agreement->chargeCalculations()->max('calculation_version')) + 1,
            );
            $superseded = $this->prepareRegeneration($agreement, $replace);

            $calculations = collect();
            foreach ($this->calculationRows($agreement, $version) as $row) {
                if ($this->math->isZero($row['chargeable_quantity']) || $this->math->isZero($row['amount'])) {
                    continue;
                }
                $sourceKey = $this->sourceKey($row);
                $row['supersedes_calculation_id'] = $superseded[$sourceKey] ?? null;
                $calculations->push(RentalChargeCalculation::query()->create($row));
            }
            $created = $this->charges->createFromCalculations($agreement, $calculations);
            $agreement->expenses()
                ->where('status', RentalExpenseStatus::Approved->value)
                ->whereIn('financial_treatment', $this->chargeableTreatments($agreement))
                ->update([
                    'status' => RentalExpenseStatus::Charged->value,
                    'charge_generation_status' => 'generated',
                    'updated_at' => now(),
                ]);

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
                'usageContexts',
                'expenses',
                'returnInspections',
            ])
            ->findOrFail($agreement->getKey());
        if ($agreement->rateSnapshot === null) {
            throw new InvalidArgumentException('Agreement rate snapshot is missing.');
        }

        $calculations = collect();
        $version = max(
            1,
            ((int) $agreement->chargeCalculations()->max('calculation_version')) + 1,
        );
        foreach ($this->calculationRows($agreement, $version) as $index => $row) {
            if ($this->math->isZero($row['chargeable_quantity']) || $this->math->isZero($row['amount'])) {
                continue;
            }
            $calculation = new RentalChargeCalculation;
            $calculation->forceFill($row);
            $calculation->setAttribute('id', -($index + 1));
            $calculations->push($calculation);
        }

        return $this->charges->previewFromCalculations($agreement, $calculations);
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
    private function calculationRows(RentalAgreement $agreement, int $version): array
    {
        $rows = [];
        $baseQuantity = $this->baseQuantity($agreement);
        $this->append(
            $rows,
            $agreement,
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
        $this->appendDerivedUsage($rows, $agreement, $version);
        $this->appendUsageEvents($rows, $agreement, $version);
        $this->appendExpenses($rows, $agreement, $version);
        $this->appendDamages($rows, $agreement, $version);

        return $rows;
    }

    /**
     * @return array<string, int>
     */
    private function prepareRegeneration(RentalAgreement $agreement, bool $replace): array
    {
        if (! $agreement->chargeCalculations()->exists()) {
            return [];
        }
        if (! $replace) {
            throw new InvalidArgumentException('Rental charges have already been generated for this agreement.');
        }
        $protected = $agreement->charges()
            ->where(function ($query): void {
                $query->where('status', RentalChargeStatus::Approved->value)
                    ->orWhere('invoice_status', '!=', RentalChargeInvoiceStatus::NotInvoiced->value);
            })->exists();
        if ($protected) {
            throw new InvalidArgumentException('Approved or invoiced rental charges cannot be regenerated.');
        }
        $previous = $agreement->chargeCalculations()
            ->where('status', 'draft')
            ->lockForUpdate()
            ->get();
        $agreement->charges()
            ->where('status', RentalChargeStatus::Draft->value)
            ->lockForUpdate()
            ->update(['status' => RentalChargeStatus::Cancelled->value]);
        $agreement->chargeCalculations()
            ->where('status', 'draft')
            ->update(['status' => 'superseded']);

        return $previous->mapWithKeys(
            fn (RentalChargeCalculation $calculation): array => [
                $this->sourceKey($calculation->getAttributes()) => (int) $calculation->getKey(),
            ],
        )->all();
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function appendDerivedUsage(array &$rows, RentalAgreement $agreement, int $version): void
    {
        $approved = $agreement->operationalUsageLogs->where('status', RentalUsageLogStatus::Approved);
        $distance = $this->math->sum(
            $approved->pluck('distance_km')->map(fn ($value): string => (string) $value)->all(),
        );
        $extraKm = $this->positiveSub($distance, (string) $agreement->rateSnapshot->allowed_km);
        $this->append(
            $rows,
            $agreement,
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
    private function appendUsageEvents(array &$rows, RentalAgreement $agreement, int $version): void
    {
        foreach ($agreement->operationalUsageLogs->where('status', RentalUsageLogStatus::Approved) as $log) {
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
    private function appendExpenses(array &$rows, RentalAgreement $agreement, int $version): void
    {
        foreach ($agreement->expenses->filter(
            fn (RentalExpense $expense): bool => in_array($expense->status, [
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
            $this->append(
                $rows,
                $agreement,
                $context instanceof RentalUsageContext ? $context : null,
                'rental_expense',
                (int) $expense->getKey(),
                $this->expenseCalculationType($expense),
                '1.000000',
                '0.000000',
                '1.000000',
                (string) $expense->amount,
                'expense',
                'approved_expense_financial_treatment',
                $expense->description ?? str($expense->expense_type->value)->title()->append(' expense')->toString(),
                $version,
            );
        }
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function appendDamages(array &$rows, RentalAgreement $agreement, int $version): void
    {
        if ($agreement->direction !== RentalAgreementDirection::Outbound) {
            return;
        }
        foreach ($agreement->returnInspections as $inspection) {
            if (! $inspection->is_damage_billable
                || $this->math->compare((string) $inspection->damage_amount, '0.000000') <= 0) {
                continue;
            }
            $this->append(
                $rows,
                $agreement,
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

    private function baseQuantity(RentalAgreement $agreement): string
    {
        $end = $agreement->actual_end_at ?? $agreement->expected_end_at;
        $seconds = max(1, $end->getTimestamp() - $agreement->start_at->getTimestamp());

        return match ($agreement->rateSnapshot->rate_unit) {
            RentalRateUnit::Trip => '1.000000',
            RentalRateUnit::Km => $this->math->sum($agreement->operationalUsageLogs
                ->where('status', RentalUsageLogStatus::Approved)
                ->pluck('distance_km')->map(fn ($value): string => (string) $value)->all()),
            RentalRateUnit::Hour => $this->ceilingUnits($seconds, 3600),
            RentalRateUnit::Day => $this->ceilingUnits($seconds, 86400),
            RentalRateUnit::Week => $this->ceilingUnits($seconds, 604800),
            RentalRateUnit::Month => $this->ceilingUnits($seconds, 2592000),
        };
    }

    private function ceilingUnits(int $seconds, int $unitSeconds): string
    {
        return $this->math->normalize((string) max(1, intdiv($seconds + $unitSeconds - 1, $unitSeconds)));
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

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function append(
        array &$rows,
        RentalAgreement $agreement,
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
    ): void {
        $measuredQuantity = $this->math->normalize($measuredQuantity);
        $allowedQuantity = $this->math->normalize($allowedQuantity);
        $chargeableQuantity = $this->math->normalize($chargeableQuantity);
        $rate = $this->math->normalize($rate);
        $side = RentalFinancialSide::fromDirection($agreement->direction);
        $fingerprint = hash('sha256', implode('|', [
            (string) $agreement->tenant_id,
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
            'agreement_id' => $agreement->getKey(),
            'agreement_vehicle_id' => $context?->agreement_vehicle_id,
            'usage_log_id' => $context?->usage_log_id,
            'usage_context_id' => $context?->getKey(),
            'rate_snapshot_id' => $agreement->rateSnapshot?->getKey(),
            'agreement_direction' => $agreement->direction->value,
            'financial_side' => $side->value,
            'party_type' => $agreement->party_type->value,
            'party_id' => $agreement->party_id,
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
            DB::table('rental_usage_logs')->whereIn('id', $usageLogIds)->lockForUpdate()->get();
            DB::table('rental_usage_events')->whereIn('usage_log_id', $usageLogIds)->lockForUpdate()->get();
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
