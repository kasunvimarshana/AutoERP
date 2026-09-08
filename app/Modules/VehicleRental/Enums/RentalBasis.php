<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Enums;

enum RentalBasis: string
{
    case Daily = 'daily';
    case Monthly = 'monthly';
}
