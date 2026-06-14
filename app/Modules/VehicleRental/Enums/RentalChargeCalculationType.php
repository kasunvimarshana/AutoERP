<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Enums;

enum RentalChargeCalculationType: string
{
    case BaseRental = 'base_rental';
    case ExtraKm = 'extra_km';
    case ExtraHour = 'extra_hour';
    case Overtime = 'overtime';
    case DoubleOvertime = 'double_overtime';
    case NightShift = 'night_shift';
    case Weekend = 'weekend';
    case Holiday = 'holiday';
    case DayOut = 'day_out';
    case NightOut = 'night_out';
    case Fuel = 'fuel';
    case Toll = 'toll';
    case Parking = 'parking';
    case Driver = 'driver';
    case Outstation = 'outstation';
    case Waiting = 'waiting';
    case Damage = 'damage';
    case Other = 'other';
}
