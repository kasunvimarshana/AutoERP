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

enum PayslipStatus: string
{
    case Draft = 'draft';
    case Approved = 'approved';
    case Paid = 'paid';
    case Reversed = 'reversed';
}
