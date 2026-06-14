<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Enums;

enum RentalBillingCycle: string
{
    case PerTrip = 'per_trip';
    case Daily = 'daily';
    case Weekly = 'weekly';
    case Monthly = 'monthly';
    case Final = 'final';
}
