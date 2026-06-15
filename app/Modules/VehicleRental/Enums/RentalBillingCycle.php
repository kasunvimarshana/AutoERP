<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Enums;

enum RentalBillingCycle: string
{
    case Hourly = 'hourly';
    case PerTrip = 'per_trip';
    case Daily = 'daily';
    case Weekly = 'weekly';
    case Monthly = 'monthly';
    case Anniversary = 'anniversary_cycle';
    case FixedPeriod = 'fixed_period';
    case Final = 'final';
}
