<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Enums;

enum RentalRateUnit: string
{
    case Fixed = 'fixed';
    case Minute = 'minute';
    case Hour = 'hour';
    case Day = 'day';
    case Week = 'week';
    case Month = 'month';
    case Kilometre = 'km';
    case Trip = 'trip';
    case Count = 'count';
    case Litre = 'litre';
}
