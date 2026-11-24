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

enum PayrollCycleType: string
{
    case Monthly = 'monthly';
    case BiWeekly = 'bi_weekly';
    case Weekly = 'weekly';
}
