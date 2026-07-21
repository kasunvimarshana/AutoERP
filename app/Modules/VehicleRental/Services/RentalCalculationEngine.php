<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\VehicleRental\DTOs\RentalCalculationLineResult;
use Modules\VehicleRental\DTOs\RentalCalculationResult;
use Modules\VehicleRental\Enums\RentalBillingBasis;
use Modules\VehicleRental\Enums\RentalRateCode;
use Modules\VehicleRental\Enums\RentalRateUnit;
use Modules\VehicleRental\Models\RentalAgreement;
use Modules\VehicleRental\Models\RentalRateLine;
use Modules\VehicleRental\Models\RentalRateVersion;
use Modules\VehicleRental\Models\RentalRunningChart;

final class RentalCalculationEngine
{
    public function __construct(private readonly DecimalMath $math) {}

    /** @param Collection<int, RentalRunningChart> $charts */
    public function calculate(
        RentalAgreement $agreement,
        RentalRateVersion $rateVersion,
        CarbonImmutable $periodStart,
        CarbonImmutable $periodEnd,
        Collection $charts,
    ): RentalCalculationResult {
        if ($periodEnd->lessThan($periodStart)) {
            throw new InvalidArgumentException('Calculation period end cannot be before its start.');
        }
        if ($charts->isEmpty()) {
            throw new InvalidArgumentException('No finalized running charts are available for the calculation period.');
        }

        $this->assertDriverFacts($charts);

        $rateVersion->loadMissing('lines');
        /** @var Collection<string, RentalRateLine> $rates */
        $rates = $rateVersion->lines->keyBy(fn (RentalRateLine $line): string => $line->rate_code->value);
        if ($rates->has(RentalRateCode::Other->value)) {
            throw new InvalidArgumentException('Automatic calculations do not support the unconfirmed fixed Other rate. Use a governed adjustment in a later document workflow.');
        }
        $hasBase = $rates->has(RentalRateCode::BaseRental->value);
        $hasAcRates = $rates->contains(fn (RentalRateLine $line): bool => $line->rate_code->isAcModeRate());
        if ($hasBase && $hasAcRates) {
            throw new InvalidArgumentException('A calculation cannot mix a standard base rate with AC-mode rates.');
        }
        if ($agreement->billing_basis === RentalBillingBasis::Monthly && $hasAcRates) {
            throw new InvalidArgumentException('Monthly calculations do not support AC-mode rates.');
        }

        $operatingDays = $charts->pluck('operational_date')->map(
            fn ($date): string => CarbonImmutable::parse($date)->toDateString(),
        )->unique()->count();
        if ($charts->count() !== $operatingDays) {
            throw new InvalidArgumentException('Calculations cannot contain multiple running charts for the same operational date. Replacement-day and concurrent-vehicle charging require an explicit business rule.');
        }
        $commercialKm = $this->math->sum($charts->map(fn (RentalRunningChart $chart): string => (string) $chart->commercial_km));
        $includedKm = $agreement->billing_basis === RentalBillingBasis::Daily
            ? $this->math->mul((string) $agreement->included_km, (string) $operatingDays)
            : $this->monthlyIncludedKm($agreement, $periodStart, $periodEnd);
        $excessKm = $this->math->compare($commercialKm, $includedKm) > 0
            ? $this->math->sub($commercialKm, $includedKm)
            : $this->math->normalize('0');

        $lines = [];
        if ($agreement->billing_basis === RentalBillingBasis::Daily) {
            $lines = [...$lines, ...$this->dailyBaseLines($rates, $charts)];
        } else {
            $lines[] = $this->proratedMonthlyLine(
                $this->requiredRate($rates, RentalRateCode::BaseRental, RentalRateUnit::Month),
                $periodStart,
                $periodEnd,
            );
        }

        if ($rates->has(RentalRateCode::ExcessKm->value) && ! $this->math->isZero($excessKm)) {
            $lines[] = $this->line($this->requiredRate($rates, RentalRateCode::ExcessKm, RentalRateUnit::Kilometre), $excessKm);
        }
        if ($rates->has(RentalRateCode::DriverSalary->value)) {
            $driverRate = $rates->get(RentalRateCode::DriverSalary->value);
            if (! $driverRate instanceof RentalRateLine) {
                throw new InvalidArgumentException('Driver salary rate is invalid.');
            }
            $driverCharts = $charts->filter(
                fn (RentalRunningChart $chart): bool => $chart->driver_employee_id !== null,
            );
            if ($driverCharts->isNotEmpty()) {
                $lines[] = match ($driverRate->unit) {
                    RentalRateUnit::Day => $this->line(
                        $driverRate,
                        (string) $driverCharts->pluck('operational_date')
                            ->map(fn ($date): string => CarbonImmutable::parse($date)->toDateString())
                            ->unique()
                            ->count(),
                    ),
                    RentalRateUnit::Month => $this->monthlyDriverLine(
                        $driverRate,
                        $periodStart,
                        $periodEnd,
                        $charts,
                        $driverCharts,
                    ),
                    default => throw new InvalidArgumentException('Driver salary must use a day or month unit.'),
                };
            }
        }

        $this->appendMeasuredLine($lines, $rates, RentalRateCode::NormalOvertime, $charts, 'normal_overtime_hours', RentalRateUnit::Hour);
        $this->appendMeasuredLine($lines, $rates, RentalRateCode::DoubleOvertime, $charts, 'double_overtime_hours', RentalRateUnit::Hour);
        $this->appendMeasuredLine($lines, $rates, RentalRateCode::TripleOvertime, $charts, 'triple_overtime_hours', RentalRateUnit::Hour);
        $nightOuts = (string) $charts->sum(fn (RentalRunningChart $chart): int => (int) $chart->night_out_count);
        if ($rates->has(RentalRateCode::NightOut->value) && (int) $nightOuts > 0) {
            $lines[] = $this->line($this->requiredRate($rates, RentalRateCode::NightOut, RentalRateUnit::Occurrence), $nightOuts);
        }

        return new RentalCalculationResult(
            operatingDays: $operatingDays,
            commercialKm: $commercialKm,
            includedKm: $includedKm,
            excessKm: $excessKm,
            subtotalAmount: $this->math->sum(array_map(fn (RentalCalculationLineResult $line): string => $line->lineTotal, $lines)),
            lines: $lines,
        );
    }

    /** @param Collection<string, RentalRateLine> $rates @param Collection<int, RentalRunningChart> $charts @return list<RentalCalculationLineResult> */
    private function dailyBaseLines(Collection $rates, Collection $charts): array
    {
        $acRates = $rates->filter(fn (RentalRateLine $line): bool => $line->rate_code->isAcModeRate());
        if ($acRates->isEmpty()) {
            return [$this->line($this->requiredRate($rates, RentalRateCode::BaseRental, RentalRateUnit::Day), (string) $charts->count())];
        }

        $counts = [];
        foreach ($charts as $chart) {
            if ($chart->ac_mode === null) {
                throw new InvalidArgumentException('Every daily running chart requires an AC mode for this agreement.');
            }
            $code = $chart->ac_mode->rateCode();
            $counts[$code->value] = ($counts[$code->value] ?? 0) + 1;
        }

        $lines = [];
        foreach ($counts as $code => $quantity) {
            $lines[] = $this->line(
                $this->requiredRate($rates, RentalRateCode::from($code), RentalRateUnit::Day),
                (string) $quantity,
            );
        }

        return $lines;
    }

    /** @param list<RentalCalculationLineResult> $lines @param Collection<string, RentalRateLine> $rates @param Collection<int, RentalRunningChart> $charts */
    private function appendMeasuredLine(array &$lines, Collection $rates, RentalRateCode $code, Collection $charts, string $field, RentalRateUnit $unit): void
    {
        if (! $rates->has($code->value)) {
            return;
        }
        $quantity = $this->math->sum($charts->map(fn (RentalRunningChart $chart): string => (string) $chart->{$field}));
        if ($this->math->isZero($quantity)) {
            return;
        }
        $lines[] = $this->line($this->requiredRate($rates, $code, $unit), $quantity);
    }

    /** @param Collection<string, RentalRateLine> $rates */
    private function requiredRate(Collection $rates, RentalRateCode $code, RentalRateUnit $unit): RentalRateLine
    {
        $rate = $rates->get($code->value);
        if (! $rate instanceof RentalRateLine || $rate->unit !== $unit) {
            throw new InvalidArgumentException(sprintf('Rate [%s] with unit [%s] is required.', $code->value, $unit->value));
        }

        return $rate;
    }

    private function line(RentalRateLine $rate, string $quantity): RentalCalculationLineResult
    {
        $normalizedQuantity = $this->math->normalize($quantity);
        $unitRate = $this->math->normalize((string) $rate->rate);

        return new RentalCalculationLineResult(
            rateLineId: (int) $rate->getKey(),
            rateCode: $rate->rate_code,
            unit: $rate->unit,
            quantity: $normalizedQuantity,
            unitRate: $unitRate,
            lineTotal: $this->math->mul($normalizedQuantity, $unitRate),
            isTaxable: (bool) $rate->is_taxable,
            description: $rate->description,
        );
    }

    private function proratedMonthlyLine(
        RentalRateLine $rate,
        CarbonImmutable $periodStart,
        CarbonImmutable $periodEnd,
    ): RentalCalculationLineResult {
        $proratedAmount = $this->proratedMonthlyValue((string) $rate->rate, $periodStart, $periodEnd);

        return new RentalCalculationLineResult(
            rateLineId: (int) $rate->getKey(),
            rateCode: $rate->rate_code,
            unit: $rate->unit,
            quantity: $this->math->normalize('1'),
            unitRate: $proratedAmount,
            lineTotal: $proratedAmount,
            isTaxable: (bool) $rate->is_taxable,
            description: $rate->description,
        );
    }

    private function monthlyIncludedKm(
        RentalAgreement $agreement,
        CarbonImmutable $periodStart,
        CarbonImmutable $periodEnd,
    ): string {
        return $this->proratedMonthlyValue((string) $agreement->included_km, $periodStart, $periodEnd);
    }

    /** @param Collection<int, RentalRunningChart> $charts @param Collection<int, RentalRunningChart> $driverCharts */
    private function monthlyDriverLine(
        RentalRateLine $driverRate,
        CarbonImmutable $periodStart,
        CarbonImmutable $periodEnd,
        Collection $charts,
        Collection $driverCharts,
    ): RentalCalculationLineResult {
        if ($driverCharts->count() !== $charts->count()) {
            throw new InvalidArgumentException('Monthly driver salary cannot be calculated from mixed driver and self-drive running charts. Split the calculation period.');
        }

        return $this->proratedMonthlyLine($driverRate, $periodStart, $periodEnd);
    }

    private function proratedMonthlyValue(
        string $monthlyValue,
        CarbonImmutable $periodStart,
        CarbonImmutable $periodEnd,
    ): string {
        $this->assertSingleCalendarMonth($periodStart, $periodEnd);
        $billedDays = $periodStart->diffInDays($periodEnd) + 1;
        $unrounded = $this->math->div(
            $this->math->mul($monthlyValue, (string) $billedDays, DecimalMath::MAX_SCALE),
            (string) $periodStart->daysInMonth,
            DecimalMath::SCALE + 1,
        );

        return $this->roundHalfUp($unrounded);
    }

    private function roundHalfUp(string $value): string
    {
        $roundingIncrement = '0.'.str_repeat('0', DecimalMath::SCALE).'5';
        $adjusted = $this->math->compare($value, '0', DecimalMath::SCALE + 1) < 0
            ? $this->math->sub($value, $roundingIncrement, DecimalMath::SCALE + 1)
            : $this->math->add($value, $roundingIncrement, DecimalMath::SCALE + 1);

        return $this->math->add($adjusted, '0', DecimalMath::SCALE);
    }

    /** @param Collection<int, RentalRunningChart> $charts */
    private function assertDriverFacts(Collection $charts): void
    {
        foreach ($charts as $chart) {
            if ($chart->driver_employee_id !== null) {
                continue;
            }
            $hasDriverFacts = ! $this->math->isZero((string) $chart->normal_overtime_hours)
                || ! $this->math->isZero((string) $chart->double_overtime_hours)
                || ! $this->math->isZero((string) $chart->triple_overtime_hours)
                || (int) $chart->night_out_count > 0;
            if ($hasDriverFacts) {
                throw new InvalidArgumentException('Self-drive running charts cannot contain driver overtime or night-out facts.');
            }
        }
    }

    private function assertSingleCalendarMonth(CarbonImmutable $periodStart, CarbonImmutable $periodEnd): void
    {
        if (! $periodStart->isSameMonth($periodEnd)) {
            throw new InvalidArgumentException('Monthly rental calculations must stay within one calendar month. Split the agreement range into monthly billing periods.');
        }
    }
}
