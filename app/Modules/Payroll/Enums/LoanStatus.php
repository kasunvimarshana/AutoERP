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

enum LoanStatus: string
{
    case Draft = 'draft';
    case Approved = 'approved';
    case Active = 'active';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
}
