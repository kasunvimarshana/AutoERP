<?php

declare(strict_types=1);

namespace Tests\Unit\VehicleRental;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\VehicleRental\Enums\RentalAcMode;
use Modules\VehicleRental\Enums\RentalAgreementKind;
use Modules\VehicleRental\Enums\RentalBillingBasis;
use Modules\VehicleRental\Enums\RentalRateCode;
use Modules\VehicleRental\Enums\RentalRateUnit;
use Modules\VehicleRental\Models\RentalAgreement;
use Modules\VehicleRental\Models\RentalRateLine;
use Modules\VehicleRental\Models\RentalRateVersion;
use Modules\VehicleRental\Models\RentalRunningChart;
use Modules\VehicleRental\Services\RentalCalculationEngine;
use Tests\TestCase;

final class RentalCalculationEngineTest extends TestCase
{
    private RentalCalculationEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new RentalCalculationEngine(new DecimalMath());
    }

    public function test_daily_customer_calculation_uses_operational_facts_and_exact_decimal_rates(): void
    {
        $result = $this->engine->calculate(
            $this->agreement(RentalAgreementKind::Customer, RentalBillingBasis::Daily, '100'),
            $this->version([
                $this->rate(1, RentalRateCode::BaseRental, RentalRateUnit::Day, '100'),
                $this->rate(2, RentalRateCode::ExcessKm, RentalRateUnit::Kilometre, '2'),
                $this->rate(3, RentalRateCode::DriverSalary, RentalRateUnit::Day, '20'),
                $this->rate(4, RentalRateCode::NormalOvertime, RentalRateUnit::Hour, '5'),
                $this->rate(5, RentalRateCode::NightOut, RentalRateUnit::Occurrence, '10'),
            ]),
            CarbonImmutable::parse('2026-07-01'),
            CarbonImmutable::parse('2026-07-02'),
            collect([
                $this->chart('2026-07-01', '250', '3', '0', '0', 1),
                $this->chart('2026-07-02', '100', '1', '0', '0', 0),
            ]),
        );

        self::assertSame(2, $result->operatingDays);
        self::assertSame('350.000000', $result->commercialKm);
        self::assertSame('200.000000', $result->includedKm);
        self::assertSame('150.000000', $result->excessKm);
        self::assertSame('570.000000', $result->subtotalAmount);
        self::assertSame(
            ['base_rental', 'excess_km', 'driver_salary', 'normal_overtime', 'night_out'],
            array_map(static fn ($line): string => $line->rateCode->value, $result->lines),
        );
    }

    public function test_daily_ac_mode_calculation_uses_each_explicit_mode_rate(): void
    {
        $first = $this->chart('2026-07-01', '10', '0', '0', '0', 0);
        $first->forceFill(['ac_mode' => RentalAcMode::NonAc->value]);
        $second = $this->chart('2026-07-02', '10', '0', '0', '0', 0);
        $second->forceFill(['ac_mode' => RentalAcMode::DualAc->value]);

        $result = $this->engine->calculate(
            $this->agreement(RentalAgreementKind::Customer, RentalBillingBasis::Daily, '0'),
            $this->version([
                $this->rate(1, RentalRateCode::NonAc, RentalRateUnit::Day, '80'),
                $this->rate(2, RentalRateCode::DualAc, RentalRateUnit::Day, '120'),
            ]),
            CarbonImmutable::parse('2026-07-01'),
            CarbonImmutable::parse('2026-07-02'),
            collect([$first, $second]),
        );

        self::assertSame('200.000000', $result->subtotalAmount);
        self::assertSame(['non_ac', 'dual_ac'], array_map(static fn ($line): string => $line->rateCode->value, $result->lines));
    }

    public function test_monthly_owner_calculation_uses_owner_rates_and_one_month_allowance(): void
    {
        $result = $this->engine->calculate(
            $this->agreement(RentalAgreementKind::Owner, RentalBillingBasis::Monthly, '1000'),
            $this->version([
                $this->rate(1, RentalRateCode::BaseRental, RentalRateUnit::Month, '1000'),
                $this->rate(2, RentalRateCode::ExcessKm, RentalRateUnit::Kilometre, '1.5'),
            ]),
            CarbonImmutable::parse('2026-07-01'),
            CarbonImmutable::parse('2026-07-31'),
            collect([
                $this->chart('2026-07-01', '700', '0', '0', '0', 0),
                $this->chart('2026-07-15', '500', '0', '0', '0', 0),
            ]),
        );

        self::assertSame('1200.000000', $result->commercialKm);
        self::assertSame('1000.000000', $result->includedKm);
        self::assertSame('200.000000', $result->excessKm);
        self::assertSame('1300.000000', $result->subtotalAmount);
    }

    public function test_monthly_calculation_requires_a_complete_calendar_month(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('complete calendar month');

        $this->engine->calculate(
            $this->agreement(RentalAgreementKind::Owner, RentalBillingBasis::Monthly, '1000'),
            $this->version([$this->rate(1, RentalRateCode::BaseRental, RentalRateUnit::Month, '1000')]),
            CarbonImmutable::parse('2026-07-05'),
            CarbonImmutable::parse('2026-07-31'),
            collect([$this->chart('2026-07-05', '10', '0', '0', '0', 0)]),
        );
    }

    public function test_daily_replacement_day_with_multiple_charts_fails_closed(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Replacement-day and concurrent-vehicle charging require an explicit business rule');

        $this->engine->calculate(
            $this->agreement(RentalAgreementKind::Customer, RentalBillingBasis::Daily, '0'),
            $this->version([$this->rate(1, RentalRateCode::BaseRental, RentalRateUnit::Day, '100')]),
            CarbonImmutable::parse('2026-07-01'),
            CarbonImmutable::parse('2026-07-01'),
            collect([
                $this->chart('2026-07-01', '10', '0', '0', '0', 0),
                $this->chart('2026-07-01', '20', '0', '0', '0', 0),
            ]),
        );
    }


    public function test_self_drive_charts_do_not_generate_driver_salary(): void
    {
        $chart = $this->chart('2026-07-01', '10', '0', '0', '0', 0);
        $chart->forceFill(['driver_employee_id' => null]);

        $result = $this->engine->calculate(
            $this->agreement(RentalAgreementKind::Customer, RentalBillingBasis::Daily, '0'),
            $this->version([
                $this->rate(1, RentalRateCode::BaseRental, RentalRateUnit::Day, '100'),
                $this->rate(2, RentalRateCode::DriverSalary, RentalRateUnit::Day, '25'),
            ]),
            CarbonImmutable::parse('2026-07-01'),
            CarbonImmutable::parse('2026-07-01'),
            collect([$chart]),
        );

        self::assertSame('100.000000', $result->subtotalAmount);
        self::assertSame(['base_rental'], array_map(static fn ($line): string => $line->rateCode->value, $result->lines));
    }

    public function test_self_drive_charts_reject_driver_overtime_and_night_out_facts(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Self-drive running charts');

        $chart = $this->chart('2026-07-01', '10', '1', '0', '0', 0);
        $chart->forceFill(['driver_employee_id' => null]);
        $this->engine->calculate(
            $this->agreement(RentalAgreementKind::Customer, RentalBillingBasis::Daily, '0'),
            $this->version([$this->rate(1, RentalRateCode::BaseRental, RentalRateUnit::Day, '100')]),
            CarbonImmutable::parse('2026-07-01'),
            CarbonImmutable::parse('2026-07-01'),
            collect([$chart]),
        );
    }

    public function test_unconfirmed_other_rate_is_not_silently_ignored(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('unconfirmed fixed Other rate');

        $this->engine->calculate(
            $this->agreement(RentalAgreementKind::Customer, RentalBillingBasis::Daily, '0'),
            $this->version([
                $this->rate(1, RentalRateCode::BaseRental, RentalRateUnit::Day, '100'),
                $this->rate(2, RentalRateCode::Other, RentalRateUnit::Fixed, '50'),
            ]),
            CarbonImmutable::parse('2026-07-01'),
            CarbonImmutable::parse('2026-07-01'),
            collect([$this->chart('2026-07-01', '10', '0', '0', '0', 0)]),
        );
    }

    private function agreement(RentalAgreementKind $kind, RentalBillingBasis $basis, string $includedKm): RentalAgreement
    {
        $agreement = new RentalAgreement();
        $agreement->forceFill([
            'kind' => $kind->value,
            'billing_basis' => $basis->value,
            'included_km' => $includedKm,
        ]);

        return $agreement;
    }

    /** @param list<RentalRateLine> $lines */
    private function version(array $lines): RentalRateVersion
    {
        $version = new RentalRateVersion();
        $version->setRelation('lines', new Collection($lines));

        return $version;
    }

    private function rate(int $id, RentalRateCode $code, RentalRateUnit $unit, string $rate): RentalRateLine
    {
        $line = new RentalRateLine();
        $line->forceFill([
            'id' => $id,
            'rate_code' => $code->value,
            'unit' => $unit->value,
            'rate' => $rate,
            'is_taxable' => false,
        ]);

        return $line;
    }

    private function chart(
        string $date,
        string $commercialKm,
        string $normalOvertime,
        string $doubleOvertime,
        string $tripleOvertime,
        int $nightOuts,
    ): RentalRunningChart {
        $chart = new RentalRunningChart();
        $chart->forceFill([
            'operational_date' => $date,
            'commercial_km' => $commercialKm,
            'normal_overtime_hours' => $normalOvertime,
            'double_overtime_hours' => $doubleOvertime,
            'triple_overtime_hours' => $tripleOvertime,
            'night_out_count' => $nightOuts,
            'driver_employee_id' => 1,
        ]);

        return $chart;
    }
}
