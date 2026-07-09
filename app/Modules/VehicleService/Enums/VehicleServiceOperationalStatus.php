<?php

declare(strict_types=1);

namespace Modules\VehicleService\Enums;

enum VehicleServiceOperationalStatus: string
{
    case Draft = 'draft';
    case Inspected = 'inspected';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
}
