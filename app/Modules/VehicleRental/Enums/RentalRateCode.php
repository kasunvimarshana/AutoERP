<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Enums;

enum RentalRateCode: string
{
    case BaseRental = 'base_rental';
    case ExcessKm = 'excess_km';
    case NonAc = 'non_ac';
    case FrontAc = 'front_ac';
    case DualAc = 'dual_ac';
    case DriverSalary = 'driver_salary';
    case NormalOvertime = 'normal_overtime';
    case DoubleOvertime = 'double_overtime';
    case TripleOvertime = 'triple_overtime';
    case NightOut = 'night_out';
    case Other = 'other';

    public function isAcModeRate(): bool
    {
        return in_array($this, [self::NonAc, self::FrontAc, self::DualAc], true);
    }

    /** @return list<RentalRateUnit> */
    public function allowedUnits(): array
    {
        return match ($this) {
            self::BaseRental => [RentalRateUnit::Day, RentalRateUnit::Month],
            self::ExcessKm => [RentalRateUnit::Kilometre],
            self::NonAc, self::FrontAc, self::DualAc => [RentalRateUnit::Day],
            self::DriverSalary => [RentalRateUnit::Day, RentalRateUnit::Month],
            self::NormalOvertime, self::DoubleOvertime, self::TripleOvertime => [RentalRateUnit::Hour],
            self::NightOut => [RentalRateUnit::Occurrence],
            self::Other => [RentalRateUnit::Fixed],
        };
    }
}
