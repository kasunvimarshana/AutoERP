<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Enums;

enum RentalRateUnit: string
{
    case Hour = 'hour';
    case Day = 'day';
    case Week = 'week';
    case Month = 'month';
    case Km = 'km';
    case Trip = 'trip';
}
