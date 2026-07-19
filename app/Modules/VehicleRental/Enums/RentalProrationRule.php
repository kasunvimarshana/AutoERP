<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Enums;

enum RentalProrationRule: string
{
    case ExactDayCount = 'exact_day_count';
    case FixedThirtyDay = 'fixed_30_day';
    case NoProration = 'no_proration';
}
