<?php

declare(strict_types=1);

namespace Modules\HR\Tests\Unit\Listeners;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Mockery;
use Modules\Accounting\Repositories\Contracts\AccountRepositoryInterface;
use Modules\Accounting\Repositories\Contracts\JournalEntryLineRepositoryInterface;
use Modules\Accounting\Repositories\Contracts\JournalEntryRepositoryInterface;
use Modules\HR\Events\PayrollProcessed;
use Modules\HR\Listeners\CreatePayrollJournalListener;
use Tests\TestCase;

class CreatePayrollJournalListenerTest extends TestCase
{
    private $journalEntryRepository;

    private $journalEntryLineRepository;

    private $accountRepository;

    private $listener;

    protected function setUp(): void
    {
        parent::setUp();

        $this->journalEntryRepository = Mockery::mock(JournalEntryRepositoryInterface::class);
        $this->journalEntryLineRepository = Mockery::mock(JournalEntryLineRepositoryInterface::class);
        $this->accountRepository = Mockery::mock(AccountRepositoryInterface::class);

        $this->listener = new CreatePayrollJournalListener(
            $this->journalEntryRepository,
            $this->journalEntryLineRepository,
            $this->accountRepository
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_it_creates_journal_entry_when_payroll_processed_event_fires(): void
    {
        // Arrange
        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('commit')->once();
        Log::shouldReceive('info')->once();

        $payroll = $this->createMockPayroll();

        $journalEntry = Mockery::mock();
        $journalEntry->id = 'journal-entry-uuid-123';

        $this->mockAccountRepository();

        $this->journalEntryRepository
            ->shouldReceive('create')
            ->once()
            ->withArgs(function ($data) use ($payroll) {
                return $data['tenant_id'] === $payroll->tenant_id
                    && $data['entry_type'] === 'payroll'
                    && $data['reference_type'] === 'payroll'
                    && $data['reference_id'] === $payroll->id
                    && $data['reference_number'] === $payroll->payroll_number
                    && $data['status'] === 'posted';
            })
            ->andReturn($journalEntry);

        $this->journalEntryLineRepository
            ->shouldReceive('create')
            ->times(7) // Salary expense + employee tax + net salary + employer tax expense + employer tax payable + benefits expense + benefits payable
            ->andReturn(Mockery::mock());

        $event = new PayrollProcessed($payroll);

        // Act
        $this->listener->handle($event);

        // Assert
        $this->assertTrue(true);
    }

    public function test_it_creates_all_required_journal_entry_lines(): void
    {
        // Arrange
        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('commit')->once();
        Log::shouldReceive('info')->once();

        $payroll = $this->createMockPayroll();

        $journalEntry = Mockery::mock();
        $journalEntry->id = 'journal-entry-uuid-123';

        $accounts = $this->mockAccountRepository();

        $this->journalEntryRepository
            ->shouldReceive('create')
            ->once()
            ->andReturn($journalEntry);

        // Expect salary expense (Debit)
        $this->journalEntryLineRepository
            ->shouldReceive('create')
            ->once()
            ->withArgs(function ($data) use ($accounts, $payroll) {
                return $data['account_id'] === $accounts['salaryExpense']->id
                    && $data['debit_amount'] === $payroll->gross_salary
                    && $data['credit_amount'] === 0;
            })
            ->andReturn(Mockery::mock());

        // Expect employer tax expense (Debit)
        $this->journalEntryLineRepository
            ->shouldReceive('create')
            ->once()
            ->withArgs(function ($data) use ($accounts, $payroll) {
                return $data['account_id'] === $accounts['employerTaxExpense']->id
                    && $data['debit_amount'] === $payroll->employer_tax_amount
                    && $data['credit_amount'] === 0;
            })
            ->andReturn(Mockery::mock());

        // Expect employer benefits expense (Debit)
        $this->journalEntryLineRepository
            ->shouldReceive('create')
            ->once()
            ->withArgs(function ($data) use ($accounts, $payroll) {
                return $data['account_id'] === $accounts['benefitsExpense']->id
                    && $data['debit_amount'] === $payroll->employer_benefits_amount
                    && $data['credit_amount'] === 0;
            })
            ->andReturn(Mockery::mock());

        // Expect employee tax withholding (Credit)
        $this->journalEntryLineRepository
            ->shouldReceive('create')
            ->once()
            ->withArgs(function ($data) use ($accounts, $payroll) {
                return $data['account_id'] === $accounts['employeeTaxPayable']->id
                    && $data['debit_amount'] === 0
                    && $data['credit_amount'] === $payroll->employee_tax_amount;
            })
            ->andReturn(Mockery::mock());

        // Expect other deductions (Credit)
        $this->journalEntryLineRepository
            ->shouldReceive('create')
            ->once()
            ->withArgs(function ($data) use ($accounts, $payroll) {
                return $data['account_id'] === $accounts['employeeTaxPayable']->id
                    && $data['debit_amount'] === 0
                    && $data['credit_amount'] === $payroll->other_deductions_amount;
            })
            ->andReturn(Mockery::mock());

        // Expect net salary payable (Credit)
        $this->journalEntryLineRepository
            ->shouldReceive('create')
            ->once()
            ->withArgs(function ($data) use ($accounts, $payroll) {
                return $data['account_id'] === $accounts['salariesPayable']->id
                    && $data['debit_amount'] === 0
                    && $data['credit_amount'] === $payroll->net_salary;
            })
            ->andReturn(Mockery::mock());

        // Expect employer taxes payable (Credit)
        $this->journalEntryLineRepository
            ->shouldReceive('create')
            ->once()
            ->withArgs(function ($data) use ($accounts, $payroll) {
                return $data['account_id'] === $accounts['employeeTaxPayable']->id
                    && $data['debit_amount'] === 0
                    && $data['credit_amount'] === $payroll->employer_tax_amount;
            })
            ->andReturn(Mockery::mock());

        // Expect employer benefits payable (Credit)
        $this->journalEntryLineRepository
            ->shouldReceive('create')
            ->once()
            ->withArgs(function ($data) use ($accounts, $payroll) {
                return $data['account_id'] === $accounts['employeeTaxPayable']->id
                    && $data['debit_amount'] === 0
                    && $data['credit_amount'] === $payroll->employer_benefits_amount;
            })
            ->andReturn(Mockery::mock());

        $event = new PayrollProcessed($payroll);

        // Act
        $this->listener->handle($event);

        // Assert
        $this->assertTrue(true);
    }

    public function test_it_validates_debit_credit_balance(): void
    {
        // Arrange
        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('commit')->once();
        Log::shouldReceive('info')->once();

        $payroll = $this->createMockPayroll();

        $journalEntry = Mockery::mock();
        $journalEntry->id = 'journal-entry-uuid-123';

        $this->mockAccountRepository();

        $this->journalEntryRepository
            ->shouldReceive('create')
            ->once()
            ->andReturn($journalEntry);

        $totalDebits = 0;
        $totalCredits = 0;

        $this->journalEntryLineRepository
            ->shouldReceive('create')
            ->times(7)
            ->andReturnUsing(function ($data) use (&$totalDebits, &$totalCredits) {
                $totalDebits += $data['debit_amount'];
                $totalCredits += $data['credit_amount'];

                return Mockery::mock();
            });

        $event = new PayrollProcessed($payroll);

        // Act
        $this->listener->handle($event);

        // Assert - Verify debits equal credits
        // Total Debits = Gross Salary + Employer Tax + Employer Benefits
        // Total Debits = 100,000 + 7,650 + 5,000 = 112,650
        // Total Credits = Employee Tax + Other Deductions + Net Salary + Employer Tax + Employer Benefits
        // Total Credits = 15,000 + 2,000 + 83,000 + 7,650 + 5,000 = 112,650
        $this->assertEquals($totalDebits, $totalCredits, 'Debits must equal credits');
    }

    public function test_it_skips_zero_amount_entries(): void
    {
        // Arrange
        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('commit')->once();
        Log::shouldReceive('info')->once();

        // Payroll with no employer taxes or benefits
        $payroll = Mockery::mock();
        $payroll->id = 'payroll-uuid-123';
        $payroll->tenant_id = 'tenant-uuid-123';
        $payroll->payroll_number = 'PAY-20240209-001';
        $payroll->period_start = '2024-02-01';
        $payroll->period_end = '2024-02-29';
        $payroll->payment_date = now();
        $payroll->gross_salary = 100000.00;
        $payroll->employee_tax_amount = 15000.00;
        $payroll->employer_tax_amount = 0; // No employer tax
        $payroll->employer_benefits_amount = 0; // No benefits
        $payroll->other_deductions_amount = 2000.00;
        $payroll->net_salary = 83000.00;

        $journalEntry = Mockery::mock();
        $journalEntry->id = 'journal-entry-uuid-123';

        $this->mockAccountRepository();

        $this->journalEntryRepository
            ->shouldReceive('create')
            ->once()
            ->andReturn($journalEntry);

        // Expect only 4 lines (no employer tax or benefits)
        $this->journalEntryLineRepository
            ->shouldReceive('create')
            ->times(4) // Salary expense + employee tax + other deductions + net salary
            ->andReturn(Mockery::mock());

        $event = new PayrollProcessed($payroll);

        // Act
        $this->listener->handle($event);

        // Assert
        $this->assertTrue(true);
    }

    public function test_it_rolls_back_transaction_on_failure(): void
    {
        // Arrange
        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('rollBack')->once();
        DB::shouldReceive('commit')->never();
        Log::shouldReceive('error')->once();

        $payroll = $this->createMockPayroll();

        $this->accountRepository
            ->shouldReceive('findByCode')
            ->andThrow(new \Exception('Database error'));

        $event = new PayrollProcessed($payroll);

        // Act & Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Database error');

        $this->listener->handle($event);
    }

    public function test_it_creates_accounts_if_they_dont_exist(): void
    {
        // Arrange
        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('commit')->once();
        Log::shouldReceive('info')->once();

        $payroll = $this->createMockPayroll();

        $journalEntry = Mockery::mock();
        $journalEntry->id = 'journal-entry-uuid-123';

        // Mock accounts that will be created
        $salaryExpenseAccount = Mockery::mock();
        $salaryExpenseAccount->id = 'salary-expense-uuid';

        $employeeTaxPayableAccount = Mockery::mock();
        $employeeTaxPayableAccount->id = 'employee-tax-payable-uuid';

        $employerTaxExpenseAccount = Mockery::mock();
        $employerTaxExpenseAccount->id = 'employer-tax-expense-uuid';

        $benefitsExpenseAccount = Mockery::mock();
        $benefitsExpenseAccount->id = 'benefits-expense-uuid';

        $salariesPayableAccount = Mockery::mock();
        $salariesPayableAccount->id = 'salaries-payable-uuid';

        // findByCode returns null (accounts don't exist)
        $this->accountRepository
            ->shouldReceive('findByCode')
            ->times(5)
            ->andReturn(null);

        // Expect accounts to be created
        $this->accountRepository
            ->shouldReceive('create')
            ->times(5)
            ->withArgs(function ($data) use ($payroll) {
                return $data['tenant_id'] === $payroll->tenant_id
                    && isset($data['code'])
                    && isset($data['account_type'])
                    && $data['is_system'] === true;
            })
            ->andReturn(
                $salaryExpenseAccount,
                $employeeTaxPayableAccount,
                $employerTaxExpenseAccount,
                $benefitsExpenseAccount,
                $salariesPayableAccount
            );

        $this->journalEntryRepository
            ->shouldReceive('create')
            ->once()
            ->andReturn($journalEntry);

        $this->journalEntryLineRepository
            ->shouldReceive('create')
            ->times(7)
            ->andReturn(Mockery::mock());

        $event = new PayrollProcessed($payroll);

        // Act
        $this->listener->handle($event);

        // Assert
        $this->assertTrue(true);
    }

    public function test_it_generates_unique_journal_entry_number(): void
    {
        // Arrange
        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('commit')->once();
        Log::shouldReceive('info')->once();

        $payroll = $this->createMockPayroll();

        $journalEntry = Mockery::mock();
        $journalEntry->id = 'journal-entry-uuid-123';

        $this->mockAccountRepository();

        $this->journalEntryRepository
            ->shouldReceive('create')
            ->once()
            ->withArgs(function ($data) {
                // Verify entry number format: JE-PAY-YYYYMMDD-XXXX
                return preg_match('/^JE-PAY-\d{8}-\d{4}$/', $data['entry_number']) === 1;
            })
            ->andReturn($journalEntry);

        $this->journalEntryLineRepository
            ->shouldReceive('create')
            ->times(7)
            ->andReturn(Mockery::mock());

        $event = new PayrollProcessed($payroll);

        // Act
        $this->listener->handle($event);

        // Assert
        $this->assertTrue(true);
    }

    public function test_it_logs_journal_entry_creation_success(): void
    {
        // Arrange
        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('commit')->once();

        $payroll = $this->createMockPayroll();

        $journalEntry = Mockery::mock();
        $journalEntry->id = 'journal-entry-uuid-123';

        $this->mockAccountRepository();

        $this->journalEntryRepository
            ->shouldReceive('create')
            ->once()
            ->andReturn($journalEntry);

        $this->journalEntryLineRepository
            ->shouldReceive('create')
            ->times(7)
            ->andReturn(Mockery::mock());

        Log::shouldReceive('info')
            ->once()
            ->withArgs(function ($message, $context) use ($payroll, $journalEntry) {
                return $message === 'Payroll journal entry created'
                    && $context['payroll_id'] === $payroll->id
                    && $context['payroll_number'] === $payroll->payroll_number
                    && $context['period_start'] === $payroll->period_start
                    && $context['period_end'] === $payroll->period_end
                    && $context['gross_salary'] === $payroll->gross_salary
                    && $context['net_salary'] === $payroll->net_salary
                    && $context['journal_entry_id'] === $journalEntry->id;
            });

        $event = new PayrollProcessed($payroll);

        // Act
        $this->listener->handle($event);

        // Assert
        $this->assertTrue(true);
    }

    public function test_failed_method_logs_permanent_failure(): void
    {
        // Arrange
        $payroll = Mockery::mock();
        $payroll->id = 'payroll-uuid-123';

        $exception = new \Exception('Permanent failure');
        $event = new PayrollProcessed($payroll);

        Log::shouldReceive('error')
            ->once()
            ->withArgs(function ($message, $context) use ($payroll, $exception) {
                return $message === 'Payroll journal entry creation failed permanently'
                    && $context['payroll_id'] === $payroll->id
                    && $context['error'] === $exception->getMessage();
            });

        // Act
        $this->listener->failed($event, $exception);

        // Assert
        $this->assertTrue(true);
    }

    public function test_it_includes_payroll_period_in_description(): void
    {
        // Arrange
        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('commit')->once();
        Log::shouldReceive('info')->once();

        $payroll = $this->createMockPayroll();

        $journalEntry = Mockery::mock();
        $journalEntry->id = 'journal-entry-uuid-123';

        $this->mockAccountRepository();

        $this->journalEntryRepository
            ->shouldReceive('create')
            ->once()
            ->withArgs(function ($data) use ($payroll) {
                return str_contains($data['description'], $payroll->period_start)
                    && str_contains($data['description'], $payroll->period_end);
            })
            ->andReturn($journalEntry);

        $this->journalEntryLineRepository
            ->shouldReceive('create')
            ->times(7)
            ->andReturn(Mockery::mock());

        $event = new PayrollProcessed($payroll);

        // Act
        $this->listener->handle($event);

        // Assert
        $this->assertTrue(true);
    }

    /**
     * Helper method to create mock payroll
     */
    private function createMockPayroll()
    {
        $payroll = Mockery::mock();
        $payroll->id = 'payroll-uuid-123';
        $payroll->tenant_id = 'tenant-uuid-123';
        $payroll->payroll_number = 'PAY-20240209-001';
        $payroll->period_start = '2024-02-01';
        $payroll->period_end = '2024-02-29';
        $payroll->payment_date = now();
        $payroll->gross_salary = 100000.00;
        $payroll->employee_tax_amount = 15000.00;
        $payroll->employer_tax_amount = 7650.00;
        $payroll->employer_benefits_amount = 5000.00;
        $payroll->other_deductions_amount = 2000.00;
        $payroll->net_salary = 83000.00; // 100,000 - 15,000 - 2,000

        return $payroll;
    }

    /**
     * Helper method to mock account repository
     */
    private function mockAccountRepository(): array
    {
        $salaryExpenseAccount = Mockery::mock();
        $salaryExpenseAccount->id = 'salary-expense-uuid';

        $employeeTaxPayableAccount = Mockery::mock();
        $employeeTaxPayableAccount->id = 'employee-tax-payable-uuid';

        $employerTaxExpenseAccount = Mockery::mock();
        $employerTaxExpenseAccount->id = 'employer-tax-expense-uuid';

        $benefitsExpenseAccount = Mockery::mock();
        $benefitsExpenseAccount->id = 'benefits-expense-uuid';

        $salariesPayableAccount = Mockery::mock();
        $salariesPayableAccount->id = 'salaries-payable-uuid';

        $this->accountRepository
            ->shouldReceive('findByCode')
            ->with('6200')
            ->andReturn($salaryExpenseAccount);

        $this->accountRepository
            ->shouldReceive('findByCode')
            ->with('2200')
            ->andReturn($employeeTaxPayableAccount);

        $this->accountRepository
            ->shouldReceive('findByCode')
            ->with('6210')
            ->andReturn($employerTaxExpenseAccount);

        $this->accountRepository
            ->shouldReceive('findByCode')
            ->with('6220')
            ->andReturn($benefitsExpenseAccount);

        $this->accountRepository
            ->shouldReceive('findByCode')
            ->with('2210')
            ->andReturn($salariesPayableAccount);

        return [
            'salaryExpense' => $salaryExpenseAccount,
            'employeeTaxPayable' => $employeeTaxPayableAccount,
            'employerTaxExpense' => $employerTaxExpenseAccount,
            'benefitsExpense' => $benefitsExpenseAccount,
            'salariesPayable' => $salariesPayableAccount,
        ];
    }
}
