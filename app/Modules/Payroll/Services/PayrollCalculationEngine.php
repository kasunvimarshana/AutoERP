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

use Carbon\CarbonImmutable;
use Modules\Hr\Models\HrEmployee;
use Modules\Payroll\DTOs\CalculatedLineItemDTO;
use Modules\Payroll\DTOs\CalculatedPayslipDTO;
use Modules\Payroll\Enums\AdjustmentStatus;
use Modules\Payroll\Enums\AdjustmentType;
use Modules\Payroll\Enums\CalculationType;
use Modules\Payroll\Enums\LoanType;
use Modules\Payroll\Enums\PayslipItemType;
use Modules\Payroll\Enums\SalaryComponentType;
use Modules\Payroll\Models\PayrollAdjustment;
use Modules\Payroll\Models\PayrollEmployeeSalaryAssignment;
use Modules\Payroll\Models\PayrollPayPeriod;
use Modules\Payroll\Models\PayrollSalaryStructure;

final class PayrollCalculationEngine
{
    public function __construct(
        private readonly PayrollProrationService $prorationService,
        private readonly PayrollAttendanceIntegrationService $attendanceService,
        private readonly PayrollStatutoryService $statutoryService,
        private readonly PayrollTaxService $taxService,
        private readonly PayrollLoanService $loanService,
    ) {}

    public function calculateForEmployee(
        HrEmployee $employee,
        PayrollPayPeriod $period,
        PayrollSalaryStructure $structure,
        PayrollEmployeeSalaryAssignment $assignment,
    ): ?CalculatedPayslipDTO {
        $tenantId = (int) $employee->tenant_id;
        $proration = $this->prorationService->calculateProration($employee, $period);

        if (! $proration['is_eligible']) {
            return null;
        }

        $baseSalary = (string) $assignment->base_salary;
        $standardDays = (float) $proration['standard_days'];
        $dailyRate = $standardDays > 0 ? bcdiv($baseSalary, (string) $standardDays, 4) : '0.0000';

        $hourlyRate = bccomp((string) $assignment->hourly_rate, '0.0000', 4) > 0
            ? (string) $assignment->hourly_rate
            : ($standardDays > 0 ? bcdiv($baseSalary, (string) ($standardDays * 8), 4) : '0.0000');

        $proratedBasic = bcmul($baseSalary, $proration['proration_factor'], 4);

        /** @var list<CalculatedLineItemDTO> $items */
        $items = [];

        // 1. Basic Salary Line Item
        $items[] = new CalculatedLineItemDTO(
            itemType: PayslipItemType::Earning,
            itemCode: 'BASIC',
            itemName: 'Basic Salary',
            rate: $dailyRate,
            hoursOrQuantity: $proration['eligible_days'],
            amount: $proratedBasic,
            isTaxable: true,
            isStatutory: true,
            calculationBasis: $proration['proration_reason'] ?? 'Standard full-period basic salary',
        );

        // 2. Attendance & Leave Aggregations
        $attendance = $this->attendanceService->aggregateAttendance(
            $tenantId,
            (int) $employee->id,
            (string) $period->period_start,
            (string) $period->period_end,
        );

        $unpaidLeaveDeduction = '0.0000';
        if (bccomp($attendance['unpaid_leave_days'], '0.00', 2) > 0) {
            $unpaidLeaveDeduction = bcmul($attendance['unpaid_leave_days'], $dailyRate, 4);
            $items[] = new CalculatedLineItemDTO(
                itemType: PayslipItemType::Deduction,
                itemCode: 'UNPAID_LEAVE',
                itemName: 'Unpaid Leave Deduction',
                rate: $dailyRate,
                hoursOrQuantity: $attendance['unpaid_leave_days'],
                amount: $unpaidLeaveDeduction,
                isTaxable: true,
                isStatutory: false,
                calculationBasis: sprintf('%s unpaid days @ %s/day', $attendance['unpaid_leave_days'], $dailyRate),
            );
        }

        $absenceDeduction = '0.0000';
        if (bccomp($attendance['absent_days'], '0.00', 2) > 0) {
            $absenceDeduction = bcmul($attendance['absent_days'], $dailyRate, 4);
            $items[] = new CalculatedLineItemDTO(
                itemType: PayslipItemType::Deduction,
                itemCode: 'ABSENCE_DEDUCTION',
                itemName: 'Unexcused Absence Deduction',
                rate: $dailyRate,
                hoursOrQuantity: $attendance['absent_days'],
                amount: $absenceDeduction,
                isTaxable: true,
                isStatutory: false,
                calculationBasis: sprintf('%s absent days @ %s/day', $attendance['absent_days'], $dailyRate),
            );
        }

        // 3. Overtime Earnings
        $normalOtRate = bcmul($hourlyRate, '1.5', 4);
        $normalOtAmount = '0.0000';
        if (bccomp($attendance['overtime_hours'], '0.00', 2) > 0) {
            $normalOtAmount = bcmul($attendance['overtime_hours'], $normalOtRate, 4);
            $items[] = new CalculatedLineItemDTO(
                itemType: PayslipItemType::Overtime,
                itemCode: 'OT_NORMAL',
                itemName: 'Overtime (1.5x)',
                rate: $normalOtRate,
                hoursOrQuantity: $attendance['overtime_hours'],
                amount: $normalOtAmount,
                isTaxable: true,
                isStatutory: false,
                calculationBasis: sprintf('%s hrs @ %s/hr (1.5x)', $attendance['overtime_hours'], $normalOtRate),
            );
        }

        $doubleOtRate = bcmul($hourlyRate, '2.0', 4);
        $doubleOtAmount = '0.0000';
        if (bccomp($attendance['double_time_hours'], '0.00', 2) > 0) {
            $doubleOtAmount = bcmul($attendance['double_time_hours'], $doubleOtRate, 4);
            $items[] = new CalculatedLineItemDTO(
                itemType: PayslipItemType::Overtime,
                itemCode: 'OT_DOUBLE',
                itemName: 'Holiday/Rest Day Overtime (2.0x)',
                rate: $doubleOtRate,
                hoursOrQuantity: $attendance['double_time_hours'],
                amount: $doubleOtAmount,
                isTaxable: true,
                isStatutory: false,
                calculationBasis: sprintf('%s hrs @ %s/hr (2.0x)', $attendance['double_time_hours'], $doubleOtRate),
            );
        }

        // 4. Structure Items (Allowances & Deductions)
        $totalAllowances = '0.0000';
        $structureDeductions = '0.0000';
        $statutoryBase = $proratedBasic;

        foreach ($structure->items as $structItem) {
            $component = $structItem->component;
            if ($component === null || ! $component->is_active) {
                continue;
            }

            $amount = '0.0000';
            $calcType = $structItem->calculation_type;

            if ($calcType === CalculationType::Fixed) {
                // Fixed allowance prorated if mid-joiner/leaver
                $amount = bcmul((string) $structItem->amount_or_rate, $proration['proration_factor'], 4);
            } elseif ($calcType === CalculationType::Percentage) {
                $basisAmount = $proratedBasic;
                $rateDecimal = bcdiv((string) $structItem->amount_or_rate, '100', 4);
                $amount = bcmul($basisAmount, $rateDecimal, 4);
            }

            if (bccomp($amount, '0.0000', 4) <= 0) {
                continue;
            }

            if ($component->type === SalaryComponentType::Earning) {
                $totalAllowances = bcadd($totalAllowances, $amount, 4);
                if ($component->is_statutory_base) {
                    $statutoryBase = bcadd($statutoryBase, $amount, 4);
                }
                $items[] = new CalculatedLineItemDTO(
                    itemType: PayslipItemType::Allowance,
                    itemCode: $component->code,
                    itemName: $component->name,
                    rate: (string) $structItem->amount_or_rate,
                    hoursOrQuantity: '1.00',
                    amount: $amount,
                    isTaxable: (bool) $component->is_taxable,
                    isStatutory: (bool) $component->is_statutory_base,
                    calculationBasis: sprintf('Structure %s (%s)', $component->code, $calcType->value),
                    salaryComponentId: (int) $component->id,
                );
            } elseif ($component->type === SalaryComponentType::Deduction) {
                $structureDeductions = bcadd($structureDeductions, $amount, 4);
                $items[] = new CalculatedLineItemDTO(
                    itemType: PayslipItemType::Deduction,
                    itemCode: $component->code,
                    itemName: $component->name,
                    rate: (string) $structItem->amount_or_rate,
                    hoursOrQuantity: '1.00',
                    amount: $amount,
                    isTaxable: (bool) $component->is_taxable,
                    isStatutory: false,
                    calculationBasis: sprintf('Structure Deduction (%s)', $calcType->value),
                    salaryComponentId: (int) $component->id,
                );
            }
        }

        // 5. Pending Adjustments (Retro-pay, bonuses, corrections)
        $adjustments = PayrollAdjustment::query()
            ->where('tenant_id', $tenantId)
            ->where('employee_id', $employee->id)
            ->where('status', AdjustmentStatus::Pending->value)
            ->where(function ($q) use ($period): void {
                $q->whereNull('effective_pay_period_id')
                    ->orWhere('effective_pay_period_id', $period->id);
            })
            ->get();

        $adjustmentAdditions = '0.0000';
        $adjustmentDeductions = '0.0000';
        $appliedAdjustmentIds = [];

        foreach ($adjustments as $adjustment) {
            $appliedAdjustmentIds[] = (int) $adjustment->id;
            $adjAmount = (string) $adjustment->amount;

            if (in_array($adjustment->adjustment_type, [AdjustmentType::Addition, AdjustmentType::RetroPay, AdjustmentType::Reimbursement], true)) {
                $adjustmentAdditions = bcadd($adjustmentAdditions, $adjAmount, 4);
                $items[] = new CalculatedLineItemDTO(
                    itemType: PayslipItemType::Adjustment,
                    itemCode: 'ADJ_'.$adjustment->adjustment_type->value,
                    itemName: 'Adjustment: '.$adjustment->adjustment_type->value,
                    rate: $adjAmount,
                    hoursOrQuantity: '1.00',
                    amount: $adjAmount,
                    isTaxable: true,
                    isStatutory: false,
                    calculationBasis: $adjustment->reason,
                );
            } else {
                $adjustmentDeductions = bcadd($adjustmentDeductions, $adjAmount, 4);
                $items[] = new CalculatedLineItemDTO(
                    itemType: PayslipItemType::Adjustment,
                    itemCode: 'ADJ_DED_'.$adjustment->adjustment_type->value,
                    itemName: 'Adjustment Deduction: '.$adjustment->adjustment_type->value,
                    rate: $adjAmount,
                    hoursOrQuantity: '1.00',
                    amount: $adjAmount,
                    isTaxable: false,
                    isStatutory: false,
                    calculationBasis: $adjustment->reason,
                );
            }
        }

        // 5b. Active Loans and Salary Advances
        $activeLoans = $this->loanService->getActiveLoansForEmployee($tenantId, (int) $employee->id, (int) $period->id);
        $loanDeductions = '0.0000';
        foreach ($activeLoans as $loan) {
            $remaining = (string) $loan->balance_remaining;
            $installment = (string) $loan->monthly_installment;
            $deduction = bccomp($installment, $remaining, 4) > 0 ? $remaining : $installment;

            if (bccomp($deduction, '0.0000', 4) <= 0) {
                continue;
            }

            $loanDeductions = bcadd($loanDeductions, $deduction, 4);
            $isAdvance = $loan->loan_type === LoanType::Advance;
            $items[] = new CalculatedLineItemDTO(
                itemType: PayslipItemType::Deduction,
                itemCode: $isAdvance ? 'SALARY_ADVANCE_RECOVERY' : 'LOAN_REPAYMENT',
                itemName: ($isAdvance ? 'Salary Advance Recovery: ' : 'Loan Installment: ').$loan->loan_reference,
                rate: $deduction,
                hoursOrQuantity: '1.00',
                amount: $deduction,
                isTaxable: false,
                isStatutory: false,
                calculationBasis: sprintf('Loan #%d (%s) installment (Balance: %s)', $loan->id, $loan->loan_reference, $remaining),
            );
        }

        // 6. Gross Pay
        // Gross = Prorated Basic + Allowances + Normal OT + Double OT + Adjustment Additions
        $grossPay = bcadd($proratedBasic, $totalAllowances, 4);
        $grossPay = bcadd($grossPay, $normalOtAmount, 4);
        $grossPay = bcadd($grossPay, $doubleOtAmount, 4);
        $grossPay = bcadd($grossPay, $adjustmentAdditions, 4);

        // 7. Statutory Contributions
        $statutoryResult = $this->statutoryService->calculateStatutory($tenantId, $statutoryBase);
        $employeeStatutory = $statutoryResult['total_employee_deduction'];
        $employerStatutory = $statutoryResult['total_employer_contribution'];
        foreach ($statutoryResult['items'] as $statutoryItem) {
            $items[] = $statutoryItem;
        }

        // 8. Total Deductions
        // Total Deductions = Unpaid Leave + Absent Deduction + Structure Deductions + Adjustment Deductions + Loan Deductions
        $totalDeductions = bcadd($unpaidLeaveDeduction, $absenceDeduction, 4);
        $totalDeductions = bcadd($totalDeductions, $structureDeductions, 4);
        $totalDeductions = bcadd($totalDeductions, $adjustmentDeductions, 4);
        $totalDeductions = bcadd($totalDeductions, $loanDeductions, 4);

        // 9. Tax Calculation (PAYE / Progressive APIT)
        // Taxable Base = max(0, GrossPay - EmployeeStatutory - Deductions)
        $taxableBase = bcsub($grossPay, $employeeStatutory, 4);
        $taxYear = CarbonImmutable::parse($period->payout_date)->year;
        $taxDeduction = $this->taxService->calculateTax($tenantId, $taxableBase, $taxYear);

        if (bccomp($taxDeduction, '0.0000', 4) > 0) {
            $items[] = new CalculatedLineItemDTO(
                itemType: PayslipItemType::Tax,
                itemCode: 'PAYE_TAX',
                itemName: 'Withholding Tax (PAYE/APIT)',
                rate: $taxDeduction,
                hoursOrQuantity: '1.00',
                amount: $taxDeduction,
                isTaxable: false,
                isStatutory: false,
                calculationBasis: sprintf('Progressive Tax on base %s', $taxableBase),
            );
        }

        // 10. Net Pay
        // Net Pay = Gross Pay - Total Deductions - Employee Statutory - Tax Deduction
        $netPay = bcsub($grossPay, $totalDeductions, 4);
        $netPay = bcsub($netPay, $employeeStatutory, 4);
        $netPay = bcsub($netPay, $taxDeduction, 4);

        if (bccomp($netPay, '0.0000', 4) < 0) {
            $netPay = '0.0000';
        }

        return new CalculatedPayslipDTO(
            employeeId: (int) $employee->id,
            salaryStructureId: (int) $structure->id,
            eligibleDays: $proration['eligible_days'],
            standardDays: $proration['standard_days'],
            workedDays: $attendance['worked_days'],
            unpaidLeaveDays: $attendance['unpaid_leave_days'],
            absentDays: $attendance['absent_days'],
            overtimeHours: $attendance['overtime_hours'],
            doubleTimeHours: $attendance['double_time_hours'],
            baseSalary: $baseSalary,
            proratedBasicSalary: $proratedBasic,
            grossPay: $grossPay,
            totalAllowances: $totalAllowances,
            totalDeductions: $totalDeductions,
            employeeStatutoryDeductions: $employeeStatutory,
            employerStatutoryContributions: $employerStatutory,
            taxDeduction: $taxDeduction,
            netPay: $netPay,
            paymentMethod: $assignment->payment_method->value,
            items: $items,
            appliedAdjustmentIds: $appliedAdjustmentIds,
        );
    }
}
