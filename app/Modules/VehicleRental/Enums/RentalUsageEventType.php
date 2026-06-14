<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Enums;

enum RentalUsageEventType: string
{
    case ExtraHour = 'extra_hour';
    case ExtraKm = 'extra_km';
    case Overtime = 'overtime';
    case DoubleOvertime = 'double_overtime';
    case NightShift = 'night_shift';
    case Weekend = 'weekend';
    case Holiday = 'holiday';
    case DayOut = 'day_out';
    case NightOut = 'night_out';
    case Driver = 'driver';
    case Outstation = 'outstation';
    case Waiting = 'waiting';
    case Pass = 'pass';
    case Other = 'other';
}
