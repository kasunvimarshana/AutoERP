<?php

declare(strict_types=1);

namespace Modules\Inventory\Enums;

enum ReservationStatus: string
{
    case Active = 'active';
    case PartiallyAllocated = 'partially_allocated';
    case Allocated = 'allocated';
    case Released = 'released';
    case Cancelled = 'cancelled';
    case Expired = 'expired';
}
