<?php

declare(strict_types=1);

namespace Tests\Unit\VehicleRental;

use Modules\VehicleRental\Enums\RentalRateCode;
use Modules\VehicleRental\Enums\RentalRateUnit;
use PHPUnit\Framework\TestCase;

final class RentalRateCodeTest extends TestCase
{
    public function test_rate_codes_expose_only_supported_units(): void
    {
        self::assertSame([RentalRateUnit::Day, RentalRateUnit::Month], RentalRateCode::BaseRental->allowedUnits());
        self::assertSame([RentalRateUnit::Kilometre], RentalRateCode::ExcessKm->allowedUnits());
        self::assertSame([RentalRateUnit::Day], RentalRateCode::NonAc->allowedUnits());
        self::assertSame([RentalRateUnit::Day], RentalRateCode::FrontAc->allowedUnits());
        self::assertSame([RentalRateUnit::Day], RentalRateCode::DualAc->allowedUnits());
        self::assertSame([RentalRateUnit::Day, RentalRateUnit::Month], RentalRateCode::DriverSalary->allowedUnits());
        self::assertSame([RentalRateUnit::Hour], RentalRateCode::NormalOvertime->allowedUnits());
        self::assertSame([RentalRateUnit::Hour], RentalRateCode::DoubleOvertime->allowedUnits());
        self::assertSame([RentalRateUnit::Hour], RentalRateCode::TripleOvertime->allowedUnits());
        self::assertSame([RentalRateUnit::Occurrence], RentalRateCode::NightOut->allowedUnits());
        self::assertSame([RentalRateUnit::Fixed], RentalRateCode::Other->allowedUnits());
    }

    public function test_ac_mode_rates_are_explicit_commercial_rates(): void
    {
        self::assertTrue(RentalRateCode::NonAc->isAcModeRate());
        self::assertTrue(RentalRateCode::FrontAc->isAcModeRate());
        self::assertTrue(RentalRateCode::DualAc->isAcModeRate());
        self::assertFalse(RentalRateCode::BaseRental->isAcModeRate());
        self::assertFalse(RentalRateCode::ExcessKm->isAcModeRate());
    }
}
