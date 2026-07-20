<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Enums;

enum RentalBillingBasis: string
{
    case Daily = 'daily';
    case Monthly = 'monthly';
}
