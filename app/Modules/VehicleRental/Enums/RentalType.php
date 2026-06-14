<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Enums;

enum RentalType: string
{
    case Hourly = 'hourly';
    case Daily = 'daily';
    case Weekly = 'weekly';
    case Monthly = 'monthly';
    case Lease = 'lease';
    case Subscription = 'subscription';
    case WithDriver = 'with_driver';
    case WithoutDriver = 'without_driver';
}
