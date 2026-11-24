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
use Illuminate\Support\Facades\DB;
use LogicException;
use Modules\Payroll\Enums\AdjustmentStatus;
use Modules\Payroll\Enums\LoanRepaymentStatus;
use Modules\Payroll\Enums\PayRunStatus;
use Modules\Payroll\Enums\PayRunType;
use Modules\Payroll\Enums\PayslipItemType;
use Modules\Payroll\Enums\PayslipStatus;
use Modules\Payroll\Models\PayrollAdjustment;
use Modules\Payroll\Models\PayrollEmployeeLoan;
use Modules\Payroll\Models\PayrollEmployeeSalaryAssignment;
use Modules\Payroll\Models\PayrollLoanRepayment;
use Modules\Payroll\Models\PayrollPayPeriod;
use Modules\Payroll\Models\PayrollPayRun;
use Modules\Payroll\Models\PayrollPayslip;
use Modules\Payroll\Models\PayrollPayslipItem;

final class PayRunExecutionService
{
    public function __construct(
        private readonly PayrollCalculationEngine $calculationEngine,
        private readonly PayrollJournalPostingService $journalPostingService,
        private readonly PayrollLoanService $loanService,
    ) {}

    public function createPayRun(
        int $tenantId,
        int $payPeriodId,
        ?int $structureId = null,
        string $type = 'standard',
        ?string $notes = null,
        ?int $userId = null,
    ): PayrollPayRun {
        $period = PayrollPayPeriod::query()
            ->where('tenant_id', $tenantId)
            ->findOrFail($payPeriodId);

        $runNumber = sprintf('PR-%s-%s', CarbonImmutable::parse($period->period_start)->format('Ym'), strtoupper(bin2hex(random_bytes(3))));

        return PayrollPayRun::create([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $period->organization_unit_id,
            'pay_run_number' => $runNumber,
            'pay_period_id' => $period->id,
            'salary_structure_id' => $structureId,
            'pay_run_type' => PayRunType::from($type),
            'status' => PayRunStatus::Draft,
            'notes' => $notes,
            'calculated_by' => $userId,
        ]);
    }

    public function calculatePayRun(int $payRunId, int $userId): PayrollPayRun
    {
        return DB::transaction(function () use ($payRunId, $userId): PayrollPayRun {
            /** @var PayrollPayRun $payRun */
            $payRun = PayrollPayRun::query()->with(['period'])->lockForUpdate()->findOrFail($payRunId);

            if (in_array($payRun->status, [PayRunStatus::Approved, PayRunStatus::Paid, PayRunStatus::Reversed], true)) {
                throw new LogicException(sprintf('Cannot recalculate a pay run with status "%s".', $payRun->status->value));
            }

            $period = $payRun->period;
            if ($period === null) {
                throw new LogicException('Pay run period not found.');
            }

            // Remove existing draft payslips and line items
            PayrollPayslip::query()
                ->where('pay_run_id', $payRun->id)
                ->where('status', PayslipStatus::Draft->value)
                ->delete();

            // Query active salary assignments for this tenant
            $query = PayrollEmployeeSalaryAssignment::query()
                ->with(['employee', 'structure.items.component'])
                ->where('tenant_id', $payRun->tenant_id)
                ->where('is_active', true)
                ->where('effective_from', '<=', $period->period_end)
                ->where(function ($q) use ($period): void {
                    $q->whereNull('effective_to')
                        ->orWhere('effective_to', '>=', $period->period_start);
                });

            if ($payRun->salary_structure_id !== null) {
                $query->where('salary_structure_id', $payRun->salary_structure_id);
            }

            $assignments = $query->get();

            $totalGross = '0.0000';
            $totalDeductions = '0.0000';
            $totalEeStatutory = '0.0000';
            $totalErStatutory = '0.0000';
            $totalTax = '0.0000';
            $totalNet = '0.0000';
            $employeeCount = 0;

            foreach ($assignments as $assignment) {
                $employee = $assignment->employee;
                $structure = $assignment->structure;

                if ($employee === null || $structure === null) {
                    continue;
                }

                $calculated = $this->calculationEngine->calculateForEmployee($employee, $period, $structure, $assignment);
                if ($calculated === null) {
                    continue;
                }

                $payslipNumber = sprintf('PS-%s-%04d', $payRun->pay_run_number, $employee->id);

                /** @var PayrollPayslip $payslip */
                $payslip = PayrollPayslip::create([
                    'tenant_id' => $payRun->tenant_id,
                    'organization_unit_id' => $payRun->organization_unit_id,
                    'payslip_number' => $payslipNumber,
                    'pay_run_id' => $payRun->id,
                    'employee_id' => $employee->id,
                    'salary_structure_id' => $structure->id,
                    'period_start' => $period->period_start,
                    'period_end' => $period->period_end,
                    'status' => PayslipStatus::Draft,
                    'eligible_days' => $calculated->eligibleDays,
                    'standard_days' => $calculated->standardDays,
                    'worked_days' => $calculated->workedDays,
                    'unpaid_leave_days' => $calculated->unpaidLeaveDays,
                    'absent_days' => $calculated->absentDays,
                    'overtime_hours' => $calculated->overtimeHours,
                    'double_time_hours' => $calculated->doubleTimeHours,
                    'base_salary' => $calculated->baseSalary,
                    'prorated_basic_salary' => $calculated->proratedBasicSalary,
                    'gross_pay' => $calculated->grossPay,
                    'total_allowances' => $calculated->totalAllowances,
                    'total_deductions' => $calculated->totalDeductions,
                    'employee_statutory_deductions' => $calculated->employeeStatutoryDeductions,
                    'employer_statutory_contributions' => $calculated->employerStatutoryContributions,
                    'tax_deduction' => $calculated->taxDeduction,
                    'net_pay' => $calculated->netPay,
                    'payment_method' => $calculated->paymentMethod,
                ]);

                foreach ($calculated->items as $item) {
                    PayrollPayslipItem::create([
                        'tenant_id' => $payRun->tenant_id,
                        'organization_unit_id' => $payRun->organization_unit_id,
                        'payslip_id' => $payslip->id,
                        'salary_component_id' => $item->salaryComponentId,
                        'item_type' => $item->itemType,
                        'item_code' => $item->itemCode,
                        'item_name' => $item->itemName,
                        'rate' => $item->rate,
                        'hours_or_quantity' => $item->hoursOrQuantity,
                        'amount' => $item->amount,
                        'is_taxable' => $item->isTaxable,
                        'is_statutory' => $item->isStatutory,
                        'calculation_basis' => $item->calculationBasis,
                    ]);
                }

                $totalGross = bcadd($totalGross, $calculated->grossPay, 4);
                $totalDeductions = bcadd($totalDeductions, $calculated->totalDeductions, 4);
                $totalEeStatutory = bcadd($totalEeStatutory, $calculated->employeeStatutoryDeductions, 4);
                $totalErStatutory = bcadd($totalErStatutory, $calculated->employerStatutoryContributions, 4);
                $totalTax = bcadd($totalTax, $calculated->taxDeduction, 4);
                $totalNet = bcadd($totalNet, $calculated->netPay, 4);
                $employeeCount++;
            }

            $payRun->update([
                'status' => PayRunStatus::Calculated,
                'total_gross_pay' => $totalGross,
                'total_deductions' => $totalDeductions,
                'total_employee_statutory' => $totalEeStatutory,
                'total_employer_statutory' => $totalErStatutory,
                'total_tax' => $totalTax,
                'total_net_pay' => $totalNet,
                'total_employees' => $employeeCount,
                'calculated_by' => $userId,
                'calculated_at' => now(),
            ]);

            return $payRun->fresh(['period', 'structure', 'payslips.items']);
        });
    }

    public function approvePayRun(int $payRunId, int $userId): PayrollPayRun
    {
        return DB::transaction(function () use ($payRunId, $userId): PayrollPayRun {
            /** @var PayrollPayRun $payRun */
            $payRun = PayrollPayRun::query()->lockForUpdate()->findOrFail($payRunId);

            if ($payRun->status !== PayRunStatus::Calculated && $payRun->status !== PayRunStatus::Verified) {
                throw new LogicException(sprintf('Pay run in status "%s" cannot be approved.', $payRun->status->value));
            }

            // Update all child payslips to Approved
            PayrollPayslip::query()
                ->where('pay_run_id', $payRun->id)
                ->update(['status' => PayslipStatus::Approved->value]);

            $payRun->update([
                'status' => PayRunStatus::Approved,
                'approved_by' => $userId,
                'approved_at' => now(),
            ]);

            return $payRun->fresh();
        });
    }

    public function payPayRun(int $payRunId, int $userId): PayrollPayRun
    {
        return DB::transaction(function () use ($payRunId, $userId): PayrollPayRun {
            /** @var PayrollPayRun $payRun */
            $payRun = PayrollPayRun::query()->with(['period'])->lockForUpdate()->findOrFail($payRunId);

            if ($payRun->status !== PayRunStatus::Approved) {
                throw new LogicException(sprintf('Only approved pay runs can be paid. Current status: "%s".', $payRun->status->value));
            }

            // Post General Ledger journal entries
            $journalId = $this->journalPostingService->postPayRun($payRun, $userId);

            // Update all payslips to Paid
            PayrollPayslip::query()
                ->where('pay_run_id', $payRun->id)
                ->update(['status' => PayslipStatus::Paid->value]);

            // Mark pending adjustments for these employees as applied
            $payslips = PayrollPayslip::query()->with('items')->where('pay_run_id', $payRun->id)->get();
            $payoutDate = $payRun->period?->payout_date?->toDateString() ?? now()->toDateString();

            foreach ($payslips as $slip) {
                PayrollAdjustment::query()
                    ->where('tenant_id', $payRun->tenant_id)
                    ->where('employee_id', $slip->employee_id)
                    ->where('status', AdjustmentStatus::Pending->value)
                    ->where(function ($q) use ($payRun): void {
                        $q->whereNull('effective_pay_period_id')
                            ->orWhere('effective_pay_period_id', $payRun->pay_period_id);
                    })
                    ->update([
                        'status' => AdjustmentStatus::Applied->value,
                        'applied_payslip_id' => $slip->id,
                    ]);

                // Record Loan and Salary Advance Deductions
                foreach ($slip->items as $item) {
                    if (
                        $item->item_type === PayslipItemType::Deduction
                        && in_array($item->item_code, ['LOAN_REPAYMENT', 'SALARY_ADVANCE_RECOVERY'], true)
                    ) {
                        // Extract loan id from calculation_basis (Format: "Loan #<id> (<ref>) installment...")
                        if (preg_match('/Loan #(\d+)/', (string) $item->calculation_basis, $matches)) {
                            $loanId = (int) $matches[1];
                            $loan = PayrollEmployeeLoan::query()->where('tenant_id', $payRun->tenant_id)->find($loanId);
                            if ($loan !== null) {
                                $this->loanService->recordDeduction($loan, (string) $item->amount, (int) $payRun->id, (int) $slip->id, $payoutDate);
                            }
                        }
                    }
                }
            }

            $payRun->update([
                'status' => PayRunStatus::Paid,
                'finance_journal_id' => $journalId,
                'paid_by' => $userId,
                'paid_at' => now(),
            ]);

            return $payRun->fresh();
        });
    }

    public function reversePayRun(int $payRunId, int $userId, string $reason): PayrollPayRun
    {
        return DB::transaction(function () use ($payRunId, $userId, $reason): PayrollPayRun {
            /** @var PayrollPayRun $payRun */
            $payRun = PayrollPayRun::query()->with(['period'])->lockForUpdate()->findOrFail($payRunId);

            if (! in_array($payRun->status, [PayRunStatus::Approved, PayRunStatus::Paid], true)) {
                throw new LogicException(sprintf('Only approved or paid pay runs can be reversed. Current status: "%s".', $payRun->status->value));
            }

            // Reverse Finance GL journal if posted
            if ($payRun->finance_journal_id !== null) {
                $reversalDate = now()->toDateString();
                $this->journalPostingService->reversePayRun($payRun, $reversalDate, $userId, $reason);
            }

            // Reverse loan repayments deducted in this pay run
            $repayments = PayrollLoanRepayment::query()
                ->where('pay_run_id', $payRun->id)
                ->where('status', LoanRepaymentStatus::Deducted)
                ->get();

            foreach ($repayments as $repayment) {
                $this->loanService->reverseRepayment($repayment, 'Pay run '.$payRun->pay_run_number.' reversed: '.$reason);
            }

            // Reset applied adjustments back to pending
            PayrollAdjustment::query()
                ->whereHas('appliedPayslip', fn ($q) => $q->where('pay_run_id', $payRun->id))
                ->update([
                    'status' => AdjustmentStatus::Pending->value,
                    'applied_payslip_id' => null,
                ]);

            // Update payslips to reversed
            PayrollPayslip::query()
                ->where('pay_run_id', $payRun->id)
                ->update(['status' => PayslipStatus::Reversed->value]);

            $payRun->update([
                'status' => PayRunStatus::Reversed,
                'reversed_by' => $userId,
                'reversed_at' => now(),
                'reversal_reason' => $reason,
            ]);

            return $payRun->fresh();
        });
    }
}
