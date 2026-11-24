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

enum PayRunStatus: string
{
    case Draft = 'draft';
    case Calculated = 'calculated';
    case Verified = 'verified';
    case Approved = 'approved';
    case Paid = 'paid';
    case Reversed = 'reversed';
}
