<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Listeners;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Finance\Application\Contracts\CreateJournalEntryServiceInterface;
use Modules\Finance\Domain\RepositoryInterfaces\FiscalPeriodRepositoryInterface;
use Modules\Finance\Infrastructure\Listeners\Concerns\HandlesReplayConflicts;
use Modules\HR\Domain\Events\PayrollRunApproved;

/**
 * Creates a payroll expense journal entry in Finance when a PayrollRun is approved.
 *
 * Journal structure (double-entry):
 *   DR  Payroll Expense Account   totalGross
 *   CR  Payroll Liability Account totalNet   (net wages payable)
 *   CR  Payroll Deductions Account totalDeductions  (taxes / other deductions payable)
 *
 * The Finance module has no knowledge of HR domain internals — it only reads
 * the event payload. All account IDs must be present in event metadata for the
 * entry to be created; missing accounts cause a warning and a graceful skip.
 *
 */
class HandlePayrollRunApproved
{
    use HandlesReplayConflicts;

    public function __construct(
        private readonly FiscalPeriodRepositoryInterface $fiscalPeriodRepository,
        private readonly CreateJournalEntryServiceInterface $createJournalEntryService,
    ) {}

    public function handle(PayrollRunApproved $event): void
    {
        $run      = $event->payrollRun;
        $tenantId = $event->tenantId;
        $runId    = $run->getId();

        $metadata = $run->getMetadata();

        $expenseAccountId    = isset($metadata['payroll_expense_account_id'])
            ? (int) $metadata['payroll_expense_account_id'] : null;
        $liabilityAccountId  = isset($metadata['payroll_liability_account_id'])
            ? (int) $metadata['payroll_liability_account_id'] : null;
        $deductionsAccountId = isset($metadata['payroll_deductions_account_id'])
            ? (int) $metadata['payroll_deductions_account_id'] : null;

        if ($expenseAccountId === null || $liabilityAccountId === null || $deductionsAccountId === null) {
            Log::warning('HandlePayrollRunApproved: payroll account IDs not configured in metadata; skipping journal entry', [
                'payroll_run_id'           => $runId,
                'tenant_id'                => $tenantId,
                'expense_account_id'       => $expenseAccountId,
                'liability_account_id'     => $liabilityAccountId,
                'deductions_account_id'    => $deductionsAccountId,
            ]);

            return;
        }

        if ($this->journalAlreadyPosted($tenantId, 'payroll_run', (int) $runId)) {
            Log::info('HandlePayrollRunApproved: replay detected; journal entry already exists, skipping', [
                'payroll_run_id' => $runId,
                'tenant_id' => $tenantId,
            ]);

            return;
        }

        $totalGross      = $run->getTotalGross();
        $totalNet        = $run->getTotalNet();
        $totalDeductions = $run->getTotalDeductions();

        // Sanity check: gross == net + deductions (to 2dp)
        $expectedGross = bcadd($totalNet, $totalDeductions, 6);
        if (bccomp($totalGross, $expectedGross, 2) !== 0) {
            Log::warning('HandlePayrollRunApproved: totalGross does not equal totalNet + totalDeductions; skipping journal entry', [
                'payroll_run_id'   => $runId,
                'tenant_id'        => $tenantId,
                'total_gross'      => $totalGross,
                'total_net'        => $totalNet,
                'total_deductions' => $totalDeductions,
            ]);

            return;
        }

        $periodEnd = $run->getPeriodEnd();
        $period    = $this->fiscalPeriodRepository->findOpenPeriodForDate($tenantId, $periodEnd);

        if ($period === null) {
            Log::warning('HandlePayrollRunApproved: no open fiscal period for payroll period end date; skipping journal entry', [
                'payroll_run_id' => $runId,
                'period_end'     => $periodEnd->format('Y-m-d'),
                'tenant_id'      => $tenantId,
            ]);

            return;
        }

        $description = 'Payroll expense for run #'.$runId
            .' ('.$run->getPeriodStart()->format('Y-m-d').' – '.$periodEnd->format('Y-m-d').')';

        $lines = [
            // DR Payroll Expense (full gross cost to the business)
            [
                'account_id'          => $expenseAccountId,
                'debit_amount'        => $totalGross,
                'credit_amount'       => '0.000000',
                'description'         => $description,
                'currency_id'         => $metadata['currency_id'] ?? null,
                'exchange_rate'       => (float) ($metadata['exchange_rate'] ?? 1),
                'base_debit_amount'   => $totalGross,
                'base_credit_amount'  => '0.000000',
            ],
            // CR Payroll Liability (net wages owed to employees)
            [
                'account_id'          => $liabilityAccountId,
                'debit_amount'        => '0.000000',
                'credit_amount'       => $totalNet,
                'description'         => $description,
                'currency_id'         => $metadata['currency_id'] ?? null,
                'exchange_rate'       => (float) ($metadata['exchange_rate'] ?? 1),
                'base_debit_amount'   => '0.000000',
                'base_credit_amount'  => $totalNet,
            ],
            // CR Payroll Deductions (taxes / statutory deductions payable)
            [
                'account_id'          => $deductionsAccountId,
                'debit_amount'        => '0.000000',
                'credit_amount'       => $totalDeductions,
                'description'         => $description,
                'currency_id'         => $metadata['currency_id'] ?? null,
                'exchange_rate'       => (float) ($metadata['exchange_rate'] ?? 1),
                'base_debit_amount'   => '0.000000',
                'base_credit_amount'  => $totalDeductions,
            ],
        ];

        try {
            DB::transaction(function () use ($event, $period, $periodEnd, $description, $lines, $runId, $tenantId): void {
                $journalEntry = $this->createJournalEntryService->execute([
                    'tenant_id'        => $tenantId,
                    'fiscal_period_id' => $period->getId(),
                    'entry_date'       => $periodEnd->format('Y-m-d'),
                    'created_by'       => $event->payrollRun->getApprovedBy() ?? 1,
                    'entry_type'       => 'system',
                    'reference_type'   => 'payroll_run',
                    'reference_id'     => $runId,
                    'description'      => $description,
                    'lines'            => $lines,
                ]);

                // Write the created journal entry ID back to all payslips for this run,
                // resolving the known debt noted at the top of this class.
                if ($journalEntry->getId() !== null) {
                    DB::table('hr_payslips')
                        ->where('tenant_id', $tenantId)
                        ->where('payroll_run_id', $runId)
                        ->update(['journal_entry_id' => $journalEntry->getId()]);
                }

                Log::info('HandlePayrollRunApproved: payroll journal entry created', [
                    'payroll_run_id'   => $runId,
                    'journal_entry_id' => $journalEntry->getId(),
                    'tenant_id'        => $tenantId,
                ]);
            });
        } catch (QueryException $exception) {
            if (! $this->isReplayConflict($exception)) {
                throw $exception;
            }

            if (! $this->journalAlreadyPosted($tenantId, 'payroll_run', (int) $runId)) {
                throw new \RuntimeException(
                    'HandlePayrollRunApproved: replay conflict detected with missing journal artifact for payroll_run_id '.$runId,
                    0,
                    $exception
                );
            }

            Log::info('HandlePayrollRunApproved: duplicate-key replay conflict detected; skipping', [
                'payroll_run_id' => $runId,
                'tenant_id' => $tenantId,
            ]);
        }
    }

}
