<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

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
