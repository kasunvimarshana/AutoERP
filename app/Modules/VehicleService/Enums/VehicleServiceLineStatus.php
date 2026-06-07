<?php

declare(strict_types=1);

namespace Modules\VehicleService\Enums;

enum VehicleServiceLineStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Issued = 'issued';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
}
