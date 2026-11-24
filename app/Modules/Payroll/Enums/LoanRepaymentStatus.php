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

enum LoanRepaymentStatus: string
{
    case Scheduled = 'scheduled';
    case Deducted = 'deducted';
    case ManualPayment = 'manual_payment';
    case Reversed = 'reversed';
}
