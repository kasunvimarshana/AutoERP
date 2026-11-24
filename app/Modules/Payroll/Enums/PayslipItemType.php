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

enum PayslipItemType: string
{
    case Earning = 'earning';
    case Allowance = 'allowance';
    case Overtime = 'overtime';
    case Deduction = 'deduction';
    case EmployeeStatutory = 'employee_statutory';
    case EmployerStatutory = 'employer_statutory';
    case Tax = 'tax';
    case Adjustment = 'adjustment';
}
