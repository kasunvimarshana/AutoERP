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

use InvalidArgumentException;
use Modules\Core\Services\DecimalMath;
use Modules\Finance\Contracts\FinancePostingInterface;
use Modules\Finance\Contracts\FinanceSourceReversalInterface;
use Modules\Finance\DTOs\PostingContext;
use Modules\Finance\DTOs\PostingLine;
use Modules\Finance\DTOs\PostingSourceData;
use Modules\Finance\Enums\FinanceAccountRoleCode;
use Modules\Finance\Enums\FinancePostingProfileCode;
use Modules\Payroll\Models\PayrollPayRun;
use Modules\Tenant\Models\TenantModel;

final class PayrollJournalPostingService
{
    public function __construct(
        private readonly FinancePostingInterface $financePostings,
        private readonly FinanceSourceReversalInterface $financeReversals,
        private readonly DecimalMath $math,
    ) {}

    public function postPayRun(PayrollPayRun $payRun, int $postedBy): int
    {
        $period = $payRun->period;
        $payoutDate = $period?->payout_date?->toDateString() ?? now()->toDateString();
        $tenantId = (int) $payRun->tenant_id;
        $orgUnitId = (int) ($payRun->organization_unit_id ?? 0);

        $currencyId = TenantModel::query()->whereKey($tenantId)->value('base_currency_id');
        if ($currencyId === null || (int) $currencyId < 1) {
            throw new InvalidArgumentException('Tenant base currency must be configured before posting payroll journals.');
        }
        $currencyId = (int) $currencyId;

        $zero = $this->math->normalize('0', DecimalMath::SCALE);
        $grossPay = $this->math->normalize((string) $payRun->total_gross_pay, DecimalMath::SCALE);
        $employerStatutory = $this->math->normalize((string) $payRun->total_employer_statutory, DecimalMath::SCALE);
        $tax = $this->math->normalize((string) $payRun->total_tax, DecimalMath::SCALE);
        $employeeStatutory = $this->math->normalize((string) $payRun->total_employee_statutory, DecimalMath::SCALE);
        $deductions = $this->math->normalize((string) $payRun->total_deductions, DecimalMath::SCALE);
        $netPay = $this->math->normalize((string) $payRun->total_net_pay, DecimalMath::SCALE);

        $totalStatutoryLiability = $this->math->add($employeeStatutory, $employerStatutory, DecimalMath::SCALE);

        $lines = [];

        // 1. Debit: Salaries & Wages Expense (Gross Pay)
        if ($this->math->compare($grossPay, $zero, DecimalMath::SCALE) > 0) {
            $lines[] = new PostingLine(
                accountName: 'Salaries and Wages Expense',
                debit: $grossPay,
                credit: $zero,
                description: sprintf('Payroll Gross Pay for Run %s', $payRun->pay_run_number),
                profileKey: FinanceAccountRoleCode::SalariesExpense->value,
            );
        }

        // 2. Debit: Employer Statutory Expense
        if ($this->math->compare($employerStatutory, $zero, DecimalMath::SCALE) > 0) {
            $lines[] = new PostingLine(
                accountName: 'Employer Statutory Expense',
                debit: $employerStatutory,
                credit: $zero,
                description: sprintf('Employer Statutory Liability for Run %s', $payRun->pay_run_number),
                profileKey: FinanceAccountRoleCode::EmployerStatutoryExpense->value,
            );
        }

        // 3. Credit: Withholding Tax Payable (PAYE/APIT)
        if ($this->math->compare($tax, $zero, DecimalMath::SCALE) > 0) {
            $lines[] = new PostingLine(
                accountName: 'PAYE Tax Payable',
                debit: $zero,
                credit: $tax,
                description: sprintf('Withholding Tax Deduction for Run %s', $payRun->pay_run_number),
                profileKey: FinanceAccountRoleCode::WithholdingPayable->value,
            );
        }

        // 4. Credit: Statutory Payable (Employee + Employer)
        if ($this->math->compare($totalStatutoryLiability, $zero, DecimalMath::SCALE) > 0) {
            $lines[] = new PostingLine(
                accountName: 'Statutory Contributions Payable',
                debit: $zero,
                credit: $totalStatutoryLiability,
                description: sprintf('Total Statutory Payable (EPF/ETF/Pension) for Run %s', $payRun->pay_run_number),
                profileKey: FinanceAccountRoleCode::StatutoryPayable->value,
            );
        }

        // 5. Credit: Payroll Deductions Clearing (Unpaid leave / absent / loan deductions)
        if ($this->math->compare($deductions, $zero, DecimalMath::SCALE) > 0) {
            $lines[] = new PostingLine(
                accountName: 'Payroll Deductions Clearing',
                debit: $zero,
                credit: $deductions,
                description: sprintf('Payroll Deductions Clearing for Run %s', $payRun->pay_run_number),
                profileKey: FinanceAccountRoleCode::PayrollDeductionsClearing->value,
            );
        }

        // 6. Credit: Net Salaries Payable
        if ($this->math->compare($netPay, $zero, DecimalMath::SCALE) > 0) {
            $lines[] = new PostingLine(
                accountName: 'Net Salaries Payable',
                debit: $zero,
                credit: $netPay,
                description: sprintf('Net Salaries Payable to Staff for Run %s', $payRun->pay_run_number),
                profileKey: FinanceAccountRoleCode::SalariesPayable->value,
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
            currencyId: $currencyId,
            exchangeRate: '1.000000',
            lines: $lines,
            postingProfileCode: FinancePostingProfileCode::PayrollPayRun->value,
        );

        $result = $this->financePostings->post($context, $postedBy);

        return $result->journalId;
    }

    public function reversePayRun(PayrollPayRun $payRun, string $reversalDate, int $reversedBy, ?string $reason): void
    {
        if ($payRun->finance_journal_id === null) {
            return;
        }

        $this->financeReversals->reverseSource(
            tenantId: (int) $payRun->tenant_id,
            organizationUnitId: $payRun->organization_unit_id !== null ? (int) $payRun->organization_unit_id : null,
            sourceModule: 'payroll',
            sourceType: 'payroll_pay_run',
            sourceId: (int) $payRun->id,
            reversalDate: $reversalDate,
            reversedBy: $reversedBy,
            reason: $reason ?? 'Payroll run reversal',
        );
    }
}
