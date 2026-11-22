<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\VehicleRental\Enums;

enum VehicleUseStatus: string
{
    case Planned = 'planned';
    case InCustody = 'in_custody';
    case Returned = 'returned';
    case Cancelled = 'cancelled';
}
