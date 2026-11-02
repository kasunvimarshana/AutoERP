<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Enums;

enum RentalRateComponentCode: string
{
    case BaseRental = 'base_rental';
    case ExcessKm = 'excess_km';
    case DriverSalary = 'driver_salary';
    case NormalOvertime = 'normal_overtime';
    case DoubleOvertime = 'double_overtime';
    case TripleOvertime = 'triple_overtime';
    case NightOut = 'night_out';
    case Parking = 'parking';
    case Toll = 'toll';
    case Waiting = 'waiting';
    case Outstation = 'outstation';
    case Fuel = 'fuel';
    case Damage = 'damage';
    case Repair = 'repair';
    case OtherRecovery = 'other_recovery';
    case WithholdingTax = 'withholding_tax';
}
