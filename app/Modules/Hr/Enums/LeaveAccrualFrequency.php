<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Hr\Enums;

enum LeaveAccrualFrequency: string
{
    case AnnualUpfront = 'annual_upfront';
    case MonthlyProrated = 'monthly_prorated';
    case Quarterly = 'quarterly';
    case NoAccrual = 'no_accrual';
}
