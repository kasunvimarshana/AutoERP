<?php

namespace Modules\Accounting\Application\Listeners;

use Modules\Accounting\Application\UseCases\CreateJournalEntryUseCase;
use Modules\HR\Domain\Events\PayrollRunCompleted;


class HandlePayrollRunCompletedListener
{
    public function __construct(
        private CreateJournalEntryUseCase $createJournalEntry,
    ) {}

    public function handle(PayrollRunCompleted $event): void
    {
        if ($event->tenantId === '') {
            return;
        }

        if (bccomp($event->totalGross, '0', 8) <= 0) {
            return;
        }

        $periodNote = $event->periodLabel !== ''
            ? ' — ' . $event->periodLabel
            : '';

        $lines = [
            // Debit: Salary Expense (gross wages)
            [
                'account_code' => 'SALARY-EXPENSE',
                'description'  => 'Salary expense' . $periodNote,
                'debit'        => $event->totalGross,
                'credit'       => '0',
            ],
            // Credit: Salary Payable (net amount owed to employees)
            [
                'account_code' => 'SALARY-PAYABLE',
                'description'  => 'Net salary payable' . $periodNote,
                'debit'        => '0',
                'credit'       => $event->totalNet,
            ],
        ];

        // Only add a deductions line when there are deductions to record.
        if (bccomp($event->totalDeductions, '0', 8) > 0) {
            $lines[] = [
                'account_code' => 'PAYROLL-DEDUCTIONS-PAYABLE',
                'description'  => 'Payroll deductions payable' . $periodNote,
                'debit'        => '0',
                'credit'       => $event->totalDeductions,
            ];
        }

        try {
            $this->createJournalEntry->execute([
                'tenant_id'  => $event->tenantId,
                'reference'  => 'payroll_run',
                'notes'      => 'Auto-created from payroll run ' . $event->payrollRunId,
                'lines'      => $lines,
            ]);
        } catch (\Throwable) {
            // Graceful degradation: a journal entry creation failure must never
            // prevent the payroll run from being marked as completed.
        }
    }
}
