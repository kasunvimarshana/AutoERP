<?php

declare(strict_types=1);

namespace Modules\HR\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Accounting\Repositories\Contracts\AccountRepositoryInterface;
use Modules\Accounting\Repositories\Contracts\JournalEntryLineRepositoryInterface;
use Modules\Accounting\Repositories\Contracts\JournalEntryRepositoryInterface;
use Modules\HR\Events\PayrollProcessed;

/**
 * Create Payroll Journal Listener
 *
 * Creates journal entries for payroll when payroll is processed.
 * Records salary expense, deductions, and tax liabilities.
 * Implements event-driven integration between HR and Accounting modules.
 */
class CreatePayrollJournalListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * The number of times the job may be attempted
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying
     */
    public int $backoff = 10;

    /**
     * Create a new listener instance
     */
    public function __construct(
        private JournalEntryRepositoryInterface $journalEntryRepository,
        private JournalEntryLineRepositoryInterface $journalEntryLineRepository,
        private AccountRepositoryInterface $accountRepository
    ) {}

    /**
     * Handle the event
     */
    public function handle(PayrollProcessed $event): void
    {
        DB::beginTransaction();
        try {
            $payroll = $event->payroll;

            // Create journal entry for payroll
            $journalEntry = $this->journalEntryRepository->create([
                'tenant_id' => $payroll->tenant_id,
                'entry_number' => $this->generateEntryNumber(),
                'entry_date' => $payroll->payment_date ?? now(),
                'entry_type' => 'payroll',
                'reference_type' => 'payroll',
                'reference_id' => $payroll->id,
                'reference_number' => $payroll->payroll_number,
                'description' => "Payroll for period {$payroll->period_start} to {$payroll->period_end}",
                'status' => 'posted',
            ]);

            // Get or create required accounts
            $salaryExpenseAccount = $this->getAccount('6200', 'Salary Expense', 'expense', 'operating_expense', $payroll->tenant_id);
            $employeeTaxPayableAccount = $this->getAccount('2200', 'Employee Tax Payable', 'liability', 'current_liability', $payroll->tenant_id);
            $employerTaxExpenseAccount = $this->getAccount('6210', 'Employer Tax Expense', 'expense', 'operating_expense', $payroll->tenant_id);
            $benefitsExpenseAccount = $this->getAccount('6220', 'Employee Benefits Expense', 'expense', 'operating_expense', $payroll->tenant_id);
            $salariesPayableAccount = $this->getAccount('2210', 'Salaries Payable', 'liability', 'current_liability', $payroll->tenant_id);

            // Record salary expense (Debit)
            $this->journalEntryLineRepository->create([
                'tenant_id' => $payroll->tenant_id,
                'journal_entry_id' => $journalEntry->id,
                'account_id' => $salaryExpenseAccount->id,
                'description' => 'Gross salary expense',
                'debit_amount' => $payroll->gross_salary,
                'credit_amount' => 0,
            ]);

            // Record employer tax expense (Debit)
            if ($payroll->employer_tax_amount > 0) {
                $this->journalEntryLineRepository->create([
                    'tenant_id' => $payroll->tenant_id,
                    'journal_entry_id' => $journalEntry->id,
                    'account_id' => $employerTaxExpenseAccount->id,
                    'description' => 'Employer tax expense',
                    'debit_amount' => $payroll->employer_tax_amount,
                    'credit_amount' => 0,
                ]);
            }

            // Record employer benefits expense (Debit)
            if ($payroll->employer_benefits_amount > 0) {
                $this->journalEntryLineRepository->create([
                    'tenant_id' => $payroll->tenant_id,
                    'journal_entry_id' => $journalEntry->id,
                    'account_id' => $benefitsExpenseAccount->id,
                    'description' => 'Employer benefits expense',
                    'debit_amount' => $payroll->employer_benefits_amount,
                    'credit_amount' => 0,
                ]);
            }

            // Record employee tax withholding (Credit)
            if ($payroll->employee_tax_amount > 0) {
                $this->journalEntryLineRepository->create([
                    'tenant_id' => $payroll->tenant_id,
                    'journal_entry_id' => $journalEntry->id,
                    'account_id' => $employeeTaxPayableAccount->id,
                    'description' => 'Employee tax withholding',
                    'debit_amount' => 0,
                    'credit_amount' => $payroll->employee_tax_amount,
                ]);
            }

            // Record other deductions (Credit)
            if ($payroll->other_deductions_amount > 0) {
                $this->journalEntryLineRepository->create([
                    'tenant_id' => $payroll->tenant_id,
                    'journal_entry_id' => $journalEntry->id,
                    'account_id' => $employeeTaxPayableAccount->id,
                    'description' => 'Other deductions',
                    'debit_amount' => 0,
                    'credit_amount' => $payroll->other_deductions_amount,
                ]);
            }

            // Record net salary payable (Credit)
            $this->journalEntryLineRepository->create([
                'tenant_id' => $payroll->tenant_id,
                'journal_entry_id' => $journalEntry->id,
                'account_id' => $salariesPayableAccount->id,
                'description' => 'Net salary payable to employees',
                'debit_amount' => 0,
                'credit_amount' => $payroll->net_salary,
            ]);

            // Record employer taxes payable (Credit)
            if ($payroll->employer_tax_amount > 0) {
                $this->journalEntryLineRepository->create([
                    'tenant_id' => $payroll->tenant_id,
                    'journal_entry_id' => $journalEntry->id,
                    'account_id' => $employeeTaxPayableAccount->id,
                    'description' => 'Employer tax payable',
                    'debit_amount' => 0,
                    'credit_amount' => $payroll->employer_tax_amount,
                ]);
            }

            // Record employer benefits payable (Credit)
            if ($payroll->employer_benefits_amount > 0) {
                $this->journalEntryLineRepository->create([
                    'tenant_id' => $payroll->tenant_id,
                    'journal_entry_id' => $journalEntry->id,
                    'account_id' => $employeeTaxPayableAccount->id,
                    'description' => 'Employer benefits payable',
                    'debit_amount' => 0,
                    'credit_amount' => $payroll->employer_benefits_amount,
                ]);
            }

            DB::commit();

            Log::info('Payroll journal entry created', [
                'payroll_id' => $payroll->id,
                'payroll_number' => $payroll->payroll_number,
                'period_start' => $payroll->period_start,
                'period_end' => $payroll->period_end,
                'gross_salary' => $payroll->gross_salary,
                'net_salary' => $payroll->net_salary,
                'journal_entry_id' => $journalEntry->id,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to create payroll journal entry', [
                'payroll_id' => $event->payroll->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Get or create account
     */
    private function getAccount(
        string $code,
        string $name,
        string $type,
        string $subtype,
        string $tenantId
    ) {
        $account = $this->accountRepository->findByCode($code);

        if (! $account) {
            $account = $this->accountRepository->create([
                'tenant_id' => $tenantId,
                'code' => $code,
                'name' => json_encode(['en' => $name]),
                'account_type' => $type,
                'account_subtype' => $subtype,
                'is_active' => true,
                'is_system' => true,
            ]);
        }

        return $account;
    }

    /**
     * Generate unique journal entry number
     */
    private function generateEntryNumber(): string
    {
        $prefix = 'JE-PAY';
        $date = now()->format('Ymd');
        $random = str_pad((string) rand(1, 9999), 4, '0', STR_PAD_LEFT);

        return "{$prefix}-{$date}-{$random}";
    }

    /**
     * Handle a job failure
     */
    public function failed(PayrollProcessed $event, \Throwable $exception): void
    {
        Log::error('Payroll journal entry creation failed permanently', [
            'payroll_id' => $event->payroll->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
