<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Enums;

enum RentalRateUnit: string
{
    case Day = 'day';
    case Month = 'month';
    case Kilometre = 'kilometre';
    case Hour = 'hour';
    case Occurrence = 'occurrence';
    case Fixed = 'fixed';
}
