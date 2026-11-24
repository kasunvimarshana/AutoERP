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

use Modules\Finance\Contracts\FinancePostingInterface;
use Modules\Finance\DTOs\PostingContext;
use Modules\Finance\DTOs\PostingLine;
use Modules\Finance\DTOs\PostingSourceData;
use Modules\Payroll\Models\PayrollPayRun;

final class PayrollJournalPostingService
{
    public function __construct(
        private readonly FinancePostingInterface $financePostings,
    ) {}

    public function postPayRun(PayrollPayRun $payRun, int $postedBy): int
    {
        $period = $payRun->period;
        $payoutDate = $period?->payout_date?->toDateString() ?? now()->toDateString();
        $tenantId = (int) $payRun->tenant_id;
        $orgUnitId = (int) ($payRun->organization_unit_id ?? 0);

        $grossPay = (string) $payRun->total_gross_pay;
        $employerStatutory = (string) $payRun->total_employer_statutory;
        $tax = (string) $payRun->total_tax;
        $employeeStatutory = (string) $payRun->total_employee_statutory;
        $deductions = (string) $payRun->total_deductions;
        $netPay = (string) $payRun->total_net_pay;

        $totalStatutoryLiability = bcadd($employeeStatutory, $employerStatutory, 4);

        $lines = [];

        // 1. Debit: Salaries & Wages Expense (Gross Pay)
        if (bccomp($grossPay, '0.0000', 4) > 0) {
            $lines[] = new PostingLine(
                accountName: 'Salaries and Wages Expense',
                debit: $grossPay,
                credit: '0.000000',
                description: sprintf('Payroll Gross Pay for Run %s', $payRun->pay_run_number),
            );
        }

        // 2. Debit: Employer Statutory Expense
        if (bccomp($employerStatutory, '0.0000', 4) > 0) {
            $lines[] = new PostingLine(
                accountName: 'Employer Statutory Expense',
                debit: $employerStatutory,
                credit: '0.000000',
                description: sprintf('Employer Statutory Liability for Run %s', $payRun->pay_run_number),
            );
        }

        // 3. Credit: Withholding Tax Payable (PAYE/APIT)
        if (bccomp($tax, '0.0000', 4) > 0) {
            $lines[] = new PostingLine(
                accountName: 'PAYE Tax Payable',
                debit: '0.000000',
                credit: $tax,
                description: sprintf('Withholding Tax Deduction for Run %s', $payRun->pay_run_number),
            );
        }

        // 4. Credit: Statutory Payable (Employee + Employer)
        if (bccomp($totalStatutoryLiability, '0.0000', 4) > 0) {
            $lines[] = new PostingLine(
                accountName: 'Statutory Contributions Payable',
                debit: '0.000000',
                credit: $totalStatutoryLiability,
                description: sprintf('Total Statutory Payable (EPF/ETF/Pension) for Run %s', $payRun->pay_run_number),
            );
        }

        // 5. Credit: Payroll Deductions Clearing (Unpaid leave / absent / loan deductions)
        if (bccomp($deductions, '0.0000', 4) > 0) {
            $lines[] = new PostingLine(
                accountName: 'Payroll Deductions Clearing',
                debit: '0.000000',
                credit: $deductions,
                description: sprintf('Payroll Deductions Clearing for Run %s', $payRun->pay_run_number),
            );
        }

        // 6. Credit: Net Salaries Payable
        if (bccomp($netPay, '0.0000', 4) > 0) {
            $lines[] = new PostingLine(
                accountName: 'Net Salaries Payable',
                debit: '0.000000',
                credit: $netPay,
                description: sprintf('Net Salaries Payable to Staff for Run %s', $payRun->pay_run_number),
            );
        }

        $source = new PostingSourceData(
            sourceType: 'payroll_pay_run',
            sourceId: (int) $payRun->id,
            tenantId: $tenantId,
            organizationUnitId: $orgUnitId,
            sourceModule: 'payroll',
            sourceNumber: (string) $payRun->pay_run_number,
            sourceDate: $payoutDate,
        );

        $context = new PostingContext(
            source: $source,
            postingDate: $payoutDate,
            currencyId: null,
            exchangeRate: '1.000000',
            lines: $lines,
        );

        $result = $this->financePostings->post($context, $postedBy);

        return $result->journalId;
    }

    public function reversePayRun(PayrollPayRun $payRun, string $reversalDate, int $reversedBy, ?string $reason): void
    {
        if ($payRun->finance_journal_id === null) {
            return;
        }

        $this->financePostings->reverseJournal(
            journalId: (int) $payRun->finance_journal_id,
            reversalDate: $reversalDate,
            reversedBy: $reversedBy,
            reason: $reason ?? 'Payroll run reversal',
        );
    }
}
