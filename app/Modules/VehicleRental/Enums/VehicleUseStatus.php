<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Enums;

enum VehicleUseStatus: string
{
    case Planned = 'planned';
    case InCustody = 'in_custody';
    case Returned = 'returned';
    case Cancelled = 'cancelled';
}
