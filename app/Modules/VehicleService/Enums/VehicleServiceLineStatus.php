<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\VehicleService\Enums;

enum VehicleServiceLineStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Issued = 'issued';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
}
