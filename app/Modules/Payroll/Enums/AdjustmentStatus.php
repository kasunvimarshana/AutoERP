<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Payroll\Enums;

enum AdjustmentStatus: string
{
    case Pending = 'pending';
    case Applied = 'applied';
    case Cancelled = 'cancelled';
}
