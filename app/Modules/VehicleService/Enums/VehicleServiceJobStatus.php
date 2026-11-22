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

enum VehicleServiceJobStatus: string
{
    case Draft = 'draft';
    case Inspected = 'inspected';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Invoiced = 'invoiced';
    case PartiallyPaid = 'partially_paid';
    case Paid = 'paid';
    case Cancelled = 'cancelled';
}
