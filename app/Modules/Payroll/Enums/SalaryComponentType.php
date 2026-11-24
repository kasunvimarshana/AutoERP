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

enum SalaryComponentType: string
{
    case Earning = 'earning';
    case Deduction = 'deduction';
    case Statutory = 'statutory';
    case EmployerContribution = 'employer_contribution';
}
