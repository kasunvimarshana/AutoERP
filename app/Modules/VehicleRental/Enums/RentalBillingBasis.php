<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Enums;

enum RentalBillingBasis: string
{
    case CalendarMonth = 'calendar_month';
    case Anniversary = 'anniversary';
    case FixedPeriod = 'fixed_period';
    case PerHire = 'per_hire';
    case PerUsageLog = 'per_usage_log';
}
