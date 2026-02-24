<?php

namespace Tests\Unit\Accounting;

use Mockery;
use Modules\Accounting\Application\Listeners\HandlePayrollRunCompletedListener;
use Modules\Accounting\Application\UseCases\CreateJournalEntryUseCase;
use Modules\HR\Domain\Events\PayrollRunCompleted;
use PHPUnit\Framework\TestCase;


class PayrollRunCompletedJournalEntryListenerTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function makeEvent(
        string $payrollRunId    = 'run-1',
        string $tenantId        = 'tenant-1',
        string $totalGross      = '50000.00000000',
        string $totalDeductions = '5000.00000000',
        string $totalNet        = '45000.00000000',
        string $periodLabel     = 'Jan 2026',
    ): PayrollRunCompleted {
        return new PayrollRunCompleted(
            payrollRunId:    $payrollRunId,
            tenantId:        $tenantId,
            totalGross:      $totalGross,
            totalDeductions: $totalDeductions,
            totalNet:        $totalNet,
            periodLabel:     $periodLabel,
        );
    }

    // -------------------------------------------------------------------------
    // Guard: skip when tenantId is empty
    // -------------------------------------------------------------------------

    public function test_skips_when_tenant_id_empty(): void
    {
        $useCase = Mockery::mock(CreateJournalEntryUseCase::class);
        $useCase->shouldNotReceive('execute');

        $event = $this->makeEvent(tenantId: '');

        (new HandlePayrollRunCompletedListener($useCase))->handle($event);

        $this->addToAssertionCount(1);
    }

    // -------------------------------------------------------------------------
    // Guard: skip when totalGross is zero
    // -------------------------------------------------------------------------

    public function test_skips_when_total_gross_is_zero(): void
    {
        $useCase = Mockery::mock(CreateJournalEntryUseCase::class);
        $useCase->shouldNotReceive('execute');

        $event = $this->makeEvent(totalGross: '0', totalDeductions: '0', totalNet: '0');

        (new HandlePayrollRunCompletedListener($useCase))->handle($event);

        $this->addToAssertionCount(1);
    }

    // -------------------------------------------------------------------------
    // Guard: skip when totalGross is negative
    // -------------------------------------------------------------------------

    public function test_skips_when_total_gross_is_negative(): void
    {
        $useCase = Mockery::mock(CreateJournalEntryUseCase::class);
        $useCase->shouldNotReceive('execute');

        $event = $this->makeEvent(totalGross: '-1000.00000000');

        (new HandlePayrollRunCompletedListener($useCase))->handle($event);

        $this->addToAssertionCount(1);
    }

    // -------------------------------------------------------------------------
    // Happy path: calls CreateJournalEntryUseCase with correct tenant_id
    // -------------------------------------------------------------------------

    public function test_creates_journal_entry_with_correct_tenant_id(): void
    {
        $useCase = Mockery::mock(CreateJournalEntryUseCase::class);

        $useCase->shouldReceive('execute')
            ->once()
            ->with(Mockery::on(fn (array $data) => $data['tenant_id'] === 'tenant-abc'))
            ->andReturn((object) ['id' => 'je-1']);

        $event = $this->makeEvent(tenantId: 'tenant-abc');

        (new HandlePayrollRunCompletedListener($useCase))->handle($event);

        $this->addToAssertionCount(1);
    }

    // -------------------------------------------------------------------------
    // Happy path: salary expense line has correct gross debit amount
    // -------------------------------------------------------------------------

    public function test_salary_expense_line_has_correct_gross_debit(): void
    {
        $useCase = Mockery::mock(CreateJournalEntryUseCase::class);

        $useCase->shouldReceive('execute')
            ->once()
            ->with(Mockery::on(function (array $data) {
                $expenseLine = collect($data['lines'])->firstWhere('account_code', 'SALARY-EXPENSE');
                return $expenseLine !== null
                    && $expenseLine['debit']  === '50000.00000000'
                    && $expenseLine['credit'] === '0';
            }))
            ->andReturn((object) ['id' => 'je-1']);

        $event = $this->makeEvent(totalGross: '50000.00000000');

        (new HandlePayrollRunCompletedListener($useCase))->handle($event);

        $this->addToAssertionCount(1);
    }

    // -------------------------------------------------------------------------
    // Happy path: salary payable line has correct net credit amount
    // -------------------------------------------------------------------------

    public function test_salary_payable_line_has_correct_net_credit(): void
    {
        $useCase = Mockery::mock(CreateJournalEntryUseCase::class);

        $useCase->shouldReceive('execute')
            ->once()
            ->with(Mockery::on(function (array $data) {
                $payableLine = collect($data['lines'])->firstWhere('account_code', 'SALARY-PAYABLE');
                return $payableLine !== null
                    && $payableLine['debit']  === '0'
                    && $payableLine['credit'] === '45000.00000000';
            }))
            ->andReturn((object) ['id' => 'je-1']);

        $event = $this->makeEvent(totalNet: '45000.00000000');

        (new HandlePayrollRunCompletedListener($useCase))->handle($event);

        $this->addToAssertionCount(1);
    }

    // -------------------------------------------------------------------------
    // Happy path: deductions payable line present when deductions > 0
    // -------------------------------------------------------------------------

    public function test_deductions_payable_line_present_when_deductions_positive(): void
    {
        $useCase = Mockery::mock(CreateJournalEntryUseCase::class);

        $useCase->shouldReceive('execute')
            ->once()
            ->with(Mockery::on(function (array $data) {
                $dedLine = collect($data['lines'])->firstWhere('account_code', 'PAYROLL-DEDUCTIONS-PAYABLE');
                return $dedLine !== null
                    && $dedLine['debit']  === '0'
                    && $dedLine['credit'] === '5000.00000000';
            }))
            ->andReturn((object) ['id' => 'je-1']);

        $event = $this->makeEvent(
            totalGross:      '50000.00000000',
            totalDeductions: '5000.00000000',
            totalNet:        '45000.00000000',
        );

        (new HandlePayrollRunCompletedListener($useCase))->handle($event);

        $this->addToAssertionCount(1);
    }

    // -------------------------------------------------------------------------
    // Edge case: deductions payable line absent when deductions = 0
    // -------------------------------------------------------------------------

    public function test_deductions_payable_line_absent_when_deductions_zero(): void
    {
        $useCase = Mockery::mock(CreateJournalEntryUseCase::class);

        $useCase->shouldReceive('execute')
            ->once()
            ->with(Mockery::on(function (array $data) {
                $dedLine = collect($data['lines'])->firstWhere('account_code', 'PAYROLL-DEDUCTIONS-PAYABLE');
                return $dedLine === null;
            }))
            ->andReturn((object) ['id' => 'je-1']);

        $event = $this->makeEvent(
            totalGross:      '30000.00000000',
            totalDeductions: '0',
            totalNet:        '30000.00000000',
        );

        (new HandlePayrollRunCompletedListener($useCase))->handle($event);

        $this->addToAssertionCount(1);
    }

    // -------------------------------------------------------------------------
    // Happy path: notes reference payroll run ID
    // -------------------------------------------------------------------------

    public function test_notes_reference_payroll_run_id(): void
    {
        $useCase = Mockery::mock(CreateJournalEntryUseCase::class);

        $useCase->shouldReceive('execute')
            ->once()
            ->with(Mockery::on(fn (array $data) => str_contains($data['notes'], 'run-99')))
            ->andReturn((object) ['id' => 'je-1']);

        $event = $this->makeEvent(payrollRunId: 'run-99');

        (new HandlePayrollRunCompletedListener($useCase))->handle($event);

        $this->addToAssertionCount(1);
    }

    // -------------------------------------------------------------------------
    // Happy path: period label appears in line descriptions when provided
    // -------------------------------------------------------------------------

    public function test_period_label_appears_in_line_descriptions(): void
    {
        $useCase = Mockery::mock(CreateJournalEntryUseCase::class);

        $useCase->shouldReceive('execute')
            ->once()
            ->with(Mockery::on(function (array $data) {
                $expenseLine = collect($data['lines'])->firstWhere('account_code', 'SALARY-EXPENSE');
                return $expenseLine !== null
                    && str_contains($expenseLine['description'], 'Feb 2026');
            }))
            ->andReturn((object) ['id' => 'je-1']);

        $event = $this->makeEvent(periodLabel: 'Feb 2026');

        (new HandlePayrollRunCompletedListener($useCase))->handle($event);

        $this->addToAssertionCount(1);
    }

    // -------------------------------------------------------------------------
    // Edge case: period label absent → descriptions have no dash
    // -------------------------------------------------------------------------

    public function test_line_descriptions_work_without_period_label(): void
    {
        $useCase = Mockery::mock(CreateJournalEntryUseCase::class);

        $useCase->shouldReceive('execute')
            ->once()
            ->with(Mockery::on(function (array $data) {
                $expenseLine = collect($data['lines'])->firstWhere('account_code', 'SALARY-EXPENSE');
                return $expenseLine !== null
                    && $expenseLine['description'] === 'Salary expense';
            }))
            ->andReturn((object) ['id' => 'je-1']);

        $event = $this->makeEvent(periodLabel: '');

        (new HandlePayrollRunCompletedListener($useCase))->handle($event);

        $this->addToAssertionCount(1);
    }

    // -------------------------------------------------------------------------
    // Graceful degradation: CreateJournalEntryUseCase throws DomainException
    // -------------------------------------------------------------------------

    public function test_graceful_degradation_when_use_case_throws_domain_exception(): void
    {
        $useCase = Mockery::mock(CreateJournalEntryUseCase::class);

        $useCase->shouldReceive('execute')
            ->once()
            ->andThrow(new \DomainException('Account not found.'));

        $event = $this->makeEvent();

        // Must not throw — graceful degradation.
        (new HandlePayrollRunCompletedListener($useCase))->handle($event);

        $this->addToAssertionCount(1);
    }

    // -------------------------------------------------------------------------
    // Graceful degradation: CreateJournalEntryUseCase throws RuntimeException
    // -------------------------------------------------------------------------

    public function test_graceful_degradation_when_use_case_throws_runtime_exception(): void
    {
        $useCase = Mockery::mock(CreateJournalEntryUseCase::class);

        $useCase->shouldReceive('execute')
            ->once()
            ->andThrow(new \RuntimeException('DB connection error'));

        $event = $this->makeEvent();

        // Must not throw — graceful degradation.
        (new HandlePayrollRunCompletedListener($useCase))->handle($event);

        $this->addToAssertionCount(1);
    }

    // -------------------------------------------------------------------------
    // Backwards compatibility: event with only required field (payrollRunId)
    // -------------------------------------------------------------------------

    public function test_event_defaults_when_optional_fields_not_provided(): void
    {
        $event = new PayrollRunCompleted(payrollRunId: 'run-legacy');

        $this->assertSame('run-legacy', $event->payrollRunId);
        $this->assertSame('', $event->tenantId);
        $this->assertSame('0', $event->totalGross);
        $this->assertSame('0', $event->totalDeductions);
        $this->assertSame('0', $event->totalNet);
        $this->assertSame('', $event->periodLabel);
    }

    // -------------------------------------------------------------------------
    // Event carries enriched fields when provided
    // -------------------------------------------------------------------------

    public function test_event_carries_enriched_fields_when_provided(): void
    {
        $event = $this->makeEvent(
            payrollRunId:    'run-enrich',
            tenantId:        'tenant-enrich',
            totalGross:      '100000.00000000',
            totalDeductions: '20000.00000000',
            totalNet:        '80000.00000000',
            periodLabel:     'Mar 2026',
        );

        $this->assertSame('run-enrich', $event->payrollRunId);
        $this->assertSame('tenant-enrich', $event->tenantId);
        $this->assertSame('100000.00000000', $event->totalGross);
        $this->assertSame('20000.00000000', $event->totalDeductions);
        $this->assertSame('80000.00000000', $event->totalNet);
        $this->assertSame('Mar 2026', $event->periodLabel);
    }
}
