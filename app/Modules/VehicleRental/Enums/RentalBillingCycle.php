<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Enums;

enum RentalBillingCycle: string
{
    case Hourly = 'hourly';
    case Daily = 'daily';
    case Weekly = 'weekly';
    case Monthly = 'monthly';
    case PerHire = 'per_hire';
}
