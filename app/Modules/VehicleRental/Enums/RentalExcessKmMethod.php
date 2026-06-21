<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Enums;

enum RentalExcessKmMethod: string
{
    case Period = 'period';
    case PerHire = 'per_hire';
    case PerUsageLog = 'per_usage_log';
}
