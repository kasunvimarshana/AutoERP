<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Enums;

enum BillingCycleItemCategory: string
{
    case DriverSalary = 'driver_salary';
    case DriverAllowance = 'driver_allowance';
    case TollCharge = 'toll_charge';
    case FuelCharge = 'fuel_charge';
    case DamageRecharge = 'damage_recharge';
    case GoodwillDiscount = 'goodwill_discount';
    case DowntimeRebate = 'downtime_rebate';
    case Other = 'other';
}
