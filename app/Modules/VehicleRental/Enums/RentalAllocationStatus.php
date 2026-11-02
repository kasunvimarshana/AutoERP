<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Enums;

enum RentalAllocationStatus: string
{
    case Planned = 'planned';
    case Active = 'active';
    case Replaced = 'replaced';
    case Returned = 'returned';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
}
