<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\VehicleRental\Enums\RentalAgreementStatus;
use Modules\VehicleRental\Enums\RentalChargeCalculationType;
use Modules\VehicleRental\Enums\RentalChargeInvoiceStatus;
use Modules\VehicleRental\Enums\RentalChargeStatus;
use Modules\VehicleRental\Enums\RentalExpenseStatus;
use Modules\VehicleRental\Enums\RentalExpenseType;
use Modules\VehicleRental\Enums\RentalRateUnit;
use Modules\VehicleRental\Enums\RentalUsageEventType;
use Modules\VehicleRental\Enums\RentalUsageLogStatus;
use Modules\VehicleRental\Models\RentalAgreement;
use Modules\VehicleRental\Models\RentalChargeCalculation;
use Modules\VehicleRental\Models\RentalExpense;
use Modules\VehicleRental\Models\RentalReturnInspection;
use Modules\VehicleRental\Models\RentalUsageEvent;

final class RentalChargeCalculationService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly RentalChargeService $charges,
    ) {}

    /**
     * @return Collection<int, \Modules\VehicleRental\Models\RentalCharge>
     */
    public function calculate(RentalAgreement $agreement, bool $replace = false): Collection
    {
        if (! in_array($agreement->status, [
            RentalAgreementStatus::Active,
            RentalAgreementStatus::Returned,
            RentalAgreementStatus::Completed,
        ], true)) {
            throw new InvalidArgumentException('Rental charges can only be calculated for active or returned agreements.');
        }

        return DB::transaction(function () use ($agreement, $replace): Collection {
            $agreement = RentalAgreement::query()
                ->lockForUpdate()
                ->with([
                    'rateSnapshot',
                    'usageLogs.events',
                    'expenses',
                    'returnInspections',
                    'charges',
                ])
                ->findOrFail($agreement->getKey());
            if ($agreement->rateSnapshot === null) {
                throw new InvalidArgumentException('Agreement rate snapshot is missing.');
            }
            if ($agreement->chargeCalculations()->exists()) {
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
                $agreement->charges()->delete();
                $agreement->chargeCalculations()->delete();
                $agreement->expenses()
                    ->where('status', RentalExpenseStatus::Charged->value)
                    ->update(['status' => RentalExpenseStatus::Approved->value]);
            }

            $rows = [];
            $this->append(
                $rows,
                $agreement,
                'rental_agreement',
                (int) $agreement->getKey(),
                RentalChargeCalculationType::BaseRental,
                $this->baseQuantity($agreement),
                (string) $agreement->rateSnapshot->base_rate,
                'Base rental charge',
            );
            $this->appendDerivedUsage($rows, $agreement);
            $this->appendUsageEvents($rows, $agreement);
            $this->appendExpenses($rows, $agreement);
            $this->appendDamages($rows, $agreement);

            $calculations = collect();
            foreach ($rows as $row) {
                if ($this->math->isZero($row['quantity']) || $this->math->isZero($row['amount'])) {
                    continue;
                }
                $calculations->push(RentalChargeCalculation::query()->create($row));
            }
            $created = $this->charges->createFromCalculations($agreement, $calculations);
            $agreement->expenses()
                ->where('is_billable', true)
                ->where('status', RentalExpenseStatus::Approved->value)
                ->update(['status' => RentalExpenseStatus::Charged->value]);

            return $created;
        });
    }

    /**
     * @param list<array<string, mixed>> $rows
     */
    private function appendDerivedUsage(array &$rows, RentalAgreement $agreement): void
    {
        $approved = $agreement->usageLogs->where('status', RentalUsageLogStatus::Approved);
        $events = $approved->flatMap->events;
        if (! $events->contains('event_type', RentalUsageEventType::ExtraKm)) {
            $distance = $this->math->sum($approved->pluck('distance_km')->map(fn ($value) => (string) $value)->all());
            $extra = $this->math->sub($distance, (string) $agreement->rateSnapshot->allowed_km);
            if ($this->math->compare($extra, '0.000000') > 0) {
                $this->append(
                    $rows,
                    $agreement,
                    'rental_agreement',
                    (int) $agreement->getKey(),
                    RentalChargeCalculationType::ExtraKm,
                    $extra,
                    (string) $agreement->rateSnapshot->extra_km_rate,
                    'Extra mileage above the agreement allowance',
                );
            }
        }
        if (! $events->contains('event_type', RentalUsageEventType::ExtraHour)) {
            $hours = '0.000000';
            foreach ($approved as $log) {
                if ($log->start_time === null || $log->end_time === null) {
                    continue;
                }
                $start = CarbonImmutable::parse($log->usage_date->toDateString().' '.$log->start_time);
                $end = CarbonImmutable::parse($log->usage_date->toDateString().' '.$log->end_time);
                if ($end->lessThanOrEqualTo($start)) {
                    $end = $end->addDay();
                }
                $hours = $this->math->add(
                    $hours,
                    $this->math->div((string) $start->diffInSeconds($end), '3600.000000'),
                );
            }
            $extra = $this->math->sub($hours, (string) $agreement->rateSnapshot->allowed_hours);
            if ($this->math->compare($extra, '0.000000') > 0) {
                $this->append(
                    $rows,
                    $agreement,
                    'rental_agreement',
                    (int) $agreement->getKey(),
                    RentalChargeCalculationType::ExtraHour,
                    $extra,
                    (string) $agreement->rateSnapshot->extra_hour_rate,
                    'Extra usage hours above the agreement allowance',
                );
            }
        }
    }

    /**
     * @param list<array<string, mixed>> $rows
     */
    private function appendUsageEvents(array &$rows, RentalAgreement $agreement): void
    {
        foreach ($agreement->usageLogs->where('status', RentalUsageLogStatus::Approved) as $log) {
            foreach ($log->events as $event) {
                $this->append(
                    $rows,
                    $agreement,
                    'rental_usage_event',
                    (int) $event->getKey(),
                    $this->eventCalculationType($event),
                    (string) $event->quantity,
                    (string) $event->rate_snapshot,
                    $event->remarks ?? str($event->event_type->value)->replace('_', ' ')->title()->toString(),
                );
            }
        }
    }

    /**
     * @param list<array<string, mixed>> $rows
     */
    private function appendExpenses(array &$rows, RentalAgreement $agreement): void
    {
        foreach ($agreement->expenses
            ->where('is_billable', true)
            ->where('status', RentalExpenseStatus::Approved) as $expense) {
            $this->append(
                $rows,
                $agreement,
                'rental_expense',
                (int) $expense->getKey(),
                $this->expenseCalculationType($expense),
                '1.000000',
                (string) $expense->amount,
                $expense->description ?? str($expense->expense_type->value)->title()->append(' expense')->toString(),
            );
        }
    }

    /**
     * @param list<array<string, mixed>> $rows
     */
    private function appendDamages(array &$rows, RentalAgreement $agreement): void
    {
        foreach ($agreement->returnInspections->where('is_damage_billable', true) as $inspection) {
            if ($this->math->compare((string) $inspection->damage_amount, '0.000000') <= 0) {
                continue;
            }
            $this->append(
                $rows,
                $agreement,
                'rental_return_inspection',
                (int) $inspection->getKey(),
                RentalChargeCalculationType::Damage,
                '1.000000',
                (string) $inspection->damage_amount,
                $inspection->damage_notes ?? 'Rental vehicle damage charge',
            );
        }
    }

    private function baseQuantity(RentalAgreement $agreement): string
    {
        $end = $agreement->actual_end_at ?? $agreement->expected_end_at;
        $seconds = (int) max(1, $agreement->start_at->diffInSeconds($end));

        return match ($agreement->rateSnapshot->rate_unit) {
            RentalRateUnit::Trip => '1.000000',
            RentalRateUnit::Km => $this->math->sum($agreement->usageLogs
                ->where('status', RentalUsageLogStatus::Approved)
                ->pluck('distance_km')->map(fn ($value) => (string) $value)->all()),
            RentalRateUnit::Hour => $this->wholeUnits($seconds, 3600),
            RentalRateUnit::Day => $this->wholeUnits($seconds, 86400),
            RentalRateUnit::Week => $this->wholeUnits($seconds, 604800),
            RentalRateUnit::Month => $this->wholeUnits($seconds, 2592000),
        };
    }

    private function wholeUnits(int $seconds, int $unitSeconds): string
    {
        return $this->math->normalize((string) max(1, (int) ceil($seconds / $unitSeconds)));
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
     * @param list<array<string, mixed>> $rows
     */
    private function append(
        array &$rows,
        RentalAgreement $agreement,
        string $sourceType,
        int $sourceId,
        RentalChargeCalculationType $type,
        string $quantity,
        string $rate,
        string $description,
    ): void {
        $quantity = $this->math->normalize($quantity);
        $rate = $this->math->normalize($rate);
        $rows[] = [
            'tenant_id' => $agreement->tenant_id,
            'organization_unit_id' => $agreement->organization_unit_id,
            'agreement_id' => $agreement->getKey(),
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'calculation_type' => $type->value,
            'quantity' => $quantity,
            'rate' => $rate,
            'amount' => $this->math->mul($quantity, $rate),
            'description' => $description,
        ];
    }
}
