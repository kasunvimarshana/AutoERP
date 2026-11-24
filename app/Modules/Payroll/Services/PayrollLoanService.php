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

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use LogicException;
use Modules\Payroll\Enums\LoanRepaymentStatus;
use Modules\Payroll\Enums\LoanStatus;
use Modules\Payroll\Enums\LoanType;
use Modules\Payroll\Models\PayrollEmployeeLoan;
use Modules\Payroll\Models\PayrollLoanRepayment;
use Modules\Payroll\Models\PayrollPayPeriod;

final class PayrollLoanService
{
    /**
     * @param array{
     *     tenant_id: int,
     *     organization_unit_id?: ?int,
     *     employee_id: int,
     *     loan_type: LoanType|string,
     *     principal_amount: string|float,
     *     annual_interest_rate?: string|float,
     *     term_months?: int,
     *     deduction_start_period_id?: ?int,
     *     notes?: ?string
     * } $data
     */
    public function applyLoan(array $data): PayrollEmployeeLoan
    {
        $tenantId = (int) $data['tenant_id'];
        $type = $data['loan_type'] instanceof LoanType ? $data['loan_type'] : LoanType::from((string) $data['loan_type']);
        $principal = (string) $data['principal_amount'];

        if (bccomp($principal, '0.0000', 4) <= 0) {
            throw new InvalidArgumentException('Principal amount must be greater than zero.');
        }

        $termMonths = $type === LoanType::Advance ? 1 : max(1, (int) ($data['term_months'] ?? 1));
        $interestRate = $type === LoanType::Advance ? '0.00' : (string) ($data['annual_interest_rate'] ?? '0.00');

        // Simple interest calculation: Total Interest = Principal * (Rate / 100) * (Months / 12)
        $totalInterest = '0.0000';
        if (bccomp($interestRate, '0.00', 2) > 0) {
            $rateDecimal = bcdiv($interestRate, '100', 6);
            $yearFraction = bcdiv((string) $termMonths, '12', 6);
            $totalInterest = bcmul($principal, bcmul($rateDecimal, $yearFraction, 6), 4);
        }

        $totalPayable = bcadd($principal, $totalInterest, 4);
        $installment = bcdiv($totalPayable, (string) $termMonths, 4);

        $prefix = $type === LoanType::Advance ? 'ADV' : 'LN';
        $ref = sprintf('%s-%s-%s', $prefix, now()->format('Ym'), strtoupper(bin2hex(random_bytes(3))));

        return PayrollEmployeeLoan::create([
            'tenant_id' => $tenantId,
            'organization_unit_id' => $data['organization_unit_id'] ?? null,
            'employee_id' => (int) $data['employee_id'],
            'loan_reference' => $ref,
            'loan_type' => $type,
            'principal_amount' => $principal,
            'annual_interest_rate' => $interestRate,
            'term_months' => $termMonths,
            'monthly_installment' => $installment,
            'total_payable' => $totalPayable,
            'total_paid' => '0.0000',
            'balance_remaining' => $totalPayable,
            'deduction_start_period_id' => $data['deduction_start_period_id'] ?? null,
            'status' => LoanStatus::Draft,
            'notes' => $data['notes'] ?? null,
        ]);
    }

    public function approveLoan(PayrollEmployeeLoan $loan, int $userId): PayrollEmployeeLoan
    {
        if ($loan->status !== LoanStatus::Draft) {
            throw new LogicException(sprintf('Loan in status "%s" cannot be approved.', $loan->status->value));
        }

        $loan->update([
            'row_version' => $loan->row_version + 1,
            'status' => LoanStatus::Approved,
            'approved_by' => $userId,
            'approved_at' => now(),
        ]);

        return $loan->refresh();
    }

    public function disburseLoan(PayrollEmployeeLoan $loan, string $disbursedDate, ?int $startPeriodId = null): PayrollEmployeeLoan
    {
        if ($loan->status !== LoanStatus::Approved) {
            throw new LogicException(sprintf('Loan must be approved before disbursement. Current status: "%s".', $loan->status->value));
        }

        $loan->update([
            'row_version' => $loan->row_version + 1,
            'status' => LoanStatus::Active,
            'disbursed_date' => $disbursedDate,
            'deduction_start_period_id' => $startPeriodId ?? $loan->deduction_start_period_id,
        ]);

        return $loan->refresh();
    }

    public function cancelLoan(PayrollEmployeeLoan $loan, string $reason): PayrollEmployeeLoan
    {
        if ($loan->status === LoanStatus::Completed) {
            throw new LogicException('Cannot cancel a completed loan.');
        }

        if (bccomp((string) $loan->total_paid, '0.0000', 4) > 0) {
            throw new LogicException('Cannot cancel a loan with existing repayments.');
        }

        $loan->update([
            'row_version' => $loan->row_version + 1,
            'status' => LoanStatus::Cancelled,
            'notes' => trim($loan->notes."\nCancelled: ".$reason),
        ]);

        return $loan->refresh();
    }

    /**
     * @return Collection<int, PayrollEmployeeLoan>
     */
    public function getActiveLoansForEmployee(int $tenantId, int $employeeId, ?int $payPeriodId = null): Collection
    {
        $query = PayrollEmployeeLoan::query()
            ->where('tenant_id', $tenantId)
            ->where('employee_id', $employeeId)
            ->where('status', LoanStatus::Active)
            ->where('balance_remaining', '>', 0);

        if ($payPeriodId !== null) {
            $period = PayrollPayPeriod::query()->where('tenant_id', $tenantId)->find($payPeriodId);
            if ($period !== null) {
                $query->where(function ($q) use ($period): void {
                    $q->whereNull('deduction_start_period_id')
                        ->orWhereHas('deductionStartPeriod', function ($sub) use ($period): void {
                            $sub->where('period_start', '<=', $period->period_start);
                        });
                });
            }
        }

        return $query->get();
    }

    public function recordDeduction(
        PayrollEmployeeLoan $loan,
        string $amount,
        int $payRunId,
        int $payslipId,
        string $civilDate,
    ): PayrollLoanRepayment {
        return DB::transaction(function () use ($loan, $amount, $payRunId, $payslipId, $civilDate): PayrollLoanRepayment {
            /** @var PayrollEmployeeLoan $lockedLoan */
            $lockedLoan = PayrollEmployeeLoan::query()->lockForUpdate()->findOrFail($loan->id);

            $newPaid = bcadd((string) $lockedLoan->total_paid, $amount, 4);
            $newBalance = bcsub((string) $lockedLoan->balance_remaining, $amount, 4);

            if (bccomp($newBalance, '0.0000', 4) < 0) {
                $newBalance = '0.0000';
            }

            $repayment = PayrollLoanRepayment::create([
                'tenant_id' => $lockedLoan->tenant_id,
                'organization_unit_id' => $lockedLoan->organization_unit_id,
                'payroll_employee_loan_id' => $lockedLoan->id,
                'pay_run_id' => $payRunId,
                'payslip_id' => $payslipId,
                'repayment_date' => $civilDate,
                'principal_amount' => $amount,
                'interest_amount' => '0.0000',
                'total_repaid' => $amount,
                'balance_after' => $newBalance,
                'status' => LoanRepaymentStatus::Deducted,
                'notes' => sprintf('Payroll deduction via PayRun #%d Payslip #%d', $payRunId, $payslipId),
            ]);

            $isCompleted = bccomp($newBalance, '0.0001', 4) <= 0;

            $lockedLoan->update([
                'row_version' => $lockedLoan->row_version + 1,
                'total_paid' => $newPaid,
                'balance_remaining' => $newBalance,
                'status' => $isCompleted ? LoanStatus::Completed : LoanStatus::Active,
            ]);

            return $repayment;
        });
    }

    public function reverseRepayment(PayrollLoanRepayment $repayment, string $reason): void
    {
        DB::transaction(function () use ($repayment, $reason): void {
            if ($repayment->status === LoanRepaymentStatus::Reversed) {
                return;
            }

            /** @var PayrollEmployeeLoan $lockedLoan */
            $lockedLoan = PayrollEmployeeLoan::query()->lockForUpdate()->findOrFail($repayment->payroll_employee_loan_id);

            $repaymentAmount = (string) $repayment->total_repaid;
            $restoredBalance = bcadd((string) $lockedLoan->balance_remaining, $repaymentAmount, 4);
            $restoredPaid = bcsub((string) $lockedLoan->total_paid, $repaymentAmount, 4);

            if (bccomp($restoredPaid, '0.0000', 4) < 0) {
                $restoredPaid = '0.0000';
            }

            $lockedLoan->update([
                'row_version' => $lockedLoan->row_version + 1,
                'total_paid' => $restoredPaid,
                'balance_remaining' => $restoredBalance,
                'status' => LoanStatus::Active, // Reactivate if was completed
            ]);

            $repayment->update([
                'row_version' => $repayment->row_version + 1,
                'status' => LoanRepaymentStatus::Reversed,
                'notes' => trim($repayment->notes."\nReversed: ".$reason),
            ]);
        });
    }
}
