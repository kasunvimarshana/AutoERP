<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Enums;

enum BillingCycleItemCategory: string
{
    case DriverSalary = 'driver_salary';
    case DriverAllowance = 'driver_allowance';
    case Overtime = 'overtime';
    case ExcessKm = 'excess_km';
    case TollCharge = 'toll_charge';
    case Toll = 'toll';
    case FuelCharge = 'fuel_charge';
    case Fuel = 'fuel';
    case NightOut = 'night_out';
    case NightOutAllowance = 'night_out_allowance';
    case DamageRecharge = 'damage_recharge';
    case GoodwillDiscount = 'goodwill_discount';
    case DowntimeRebate = 'downtime_rebate';
    case Deduction = 'deduction';
    case Other = 'other';
}
