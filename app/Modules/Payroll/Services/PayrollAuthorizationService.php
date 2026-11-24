<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Payroll\Services;

final class PayrollAuthorizationService
{
    public const VIEW_PAYROLL = 'payroll.view';

    public const MANAGE_COMPONENTS = 'payroll.components.manage';

    public const MANAGE_STRUCTURES = 'payroll.structures.manage';

    public const ASSIGN_SALARY = 'payroll.assignments.manage';

    public const MANAGE_TAX_TABLES = 'payroll.tax-tables.manage';

    public const MANAGE_STATUTORY_RULES = 'payroll.statutory-rules.manage';

    public const MANAGE_PERIODS = 'payroll.periods.manage';

    public const CALCULATE_PAY_RUN = 'payroll.pay-runs.calculate';

    public const APPROVE_PAY_RUN = 'payroll.pay-runs.approve';

    public const PAY_PAY_RUN = 'payroll.pay-runs.pay';

    public const REVERSE_PAY_RUN = 'payroll.pay-runs.reverse';

    public const VIEW_PAYSLIPS = 'payroll.payslips.view';

    public const MANAGE_ADJUSTMENTS = 'payroll.adjustments.manage';

    /** @return array<string,string> */
    public static function descriptions(): array
    {
        return [
            self::VIEW_PAYROLL => 'View payroll records, pay runs, and summary reports',
            self::MANAGE_COMPONENTS => 'Create and modify salary components',
            self::MANAGE_STRUCTURES => 'Create and modify salary structures and formulas',
            self::ASSIGN_SALARY => 'Assign salary structures and compensation to employees',
            self::MANAGE_TAX_TABLES => 'Manage progressive tax tables and tax brackets',
            self::MANAGE_STATUTORY_RULES => 'Manage statutory contribution rules and rates',
            self::MANAGE_PERIODS => 'Create and manage payroll periods and calendars',
            self::CALCULATE_PAY_RUN => 'Initiate and recalculate batch pay runs',
            self::APPROVE_PAY_RUN => 'Approve calculated pay runs',
            self::PAY_PAY_RUN => 'Finalize payout and post payroll journals to General Ledger',
            self::REVERSE_PAY_RUN => 'Reverse approved or paid pay runs and reverse GL journals',
            self::VIEW_PAYSLIPS => 'View and download employee payslips',
            self::MANAGE_ADJUSTMENTS => 'Create and apply salary adjustments, retro-pays, and bonuses',
        ];
    }
}
