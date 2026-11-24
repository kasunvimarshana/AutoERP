<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

namespace Modules\Payroll\DTOs;

final readonly class CalculatedPayslipDTO
{
    /**
     * @param  list<CalculatedLineItemDTO>  $items
     * @param  list<int>  $appliedAdjustmentIds
     */
    public function __construct(
        public int $employeeId,
        public int $salaryStructureId,
        public string $eligibleDays,
        public string $standardDays,
        public string $workedDays,
        public string $unpaidLeaveDays,
        public string $absentDays,
        public string $overtimeHours,
        public string $doubleTimeHours,
        public string $baseSalary,
        public string $proratedBasicSalary,
        public string $grossPay,
        public string $totalAllowances,
        public string $totalDeductions,
        public string $employeeStatutoryDeductions,
        public string $employerStatutoryContributions,
        public string $taxDeduction,
        public string $netPay,
        public string $paymentMethod,
        public array $items,
        public array $appliedAdjustmentIds = [],
    ) {}
}
