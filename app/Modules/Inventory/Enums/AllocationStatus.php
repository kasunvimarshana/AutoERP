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

enum AllocationStatus: string
{
    case Active = 'active';
    case Issued = 'issued';
    case Reversed = 'reversed';
    case Released = 'released';
    case Cancelled = 'cancelled';
}
