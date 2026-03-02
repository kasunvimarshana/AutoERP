<?php

declare(strict_types=1);

namespace Modules\Accounting\Tests\Unit;

use Modules\Accounting\Application\Services\AccountingService;
use Modules\Accounting\Domain\Contracts\AccountRepositoryContract;
use Modules\Accounting\Domain\Contracts\FiscalPeriodRepositoryContract;
use Modules\Accounting\Domain\Contracts\JournalEntryRepositoryContract;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * Unit tests for AccountingService financial statement methods.
 *
 * Validates method signatures, return types, and structural correctness
 * for getTrialBalance() and getProfitAndLoss().
 * These are pure reflection / structural tests — no DB required.
 */
class AccountingServiceFinancialStatementsTest extends TestCase
{
    private AccountingService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $journalRepo      = $this->createStub(JournalEntryRepositoryContract::class);
        $accountRepo      = $this->createStub(AccountRepositoryContract::class);
        $fiscalPeriodRepo = $this->createStub(FiscalPeriodRepositoryContract::class);

        $this->service = new AccountingService($journalRepo, $accountRepo, $fiscalPeriodRepo);
    }

    // -------------------------------------------------------------------------
    // getTrialBalance
    // -------------------------------------------------------------------------

    public function test_get_trial_balance_method_exists(): void
    {
        $this->assertTrue(
            method_exists(AccountingService::class, 'getTrialBalance'),
            'AccountingService must have a getTrialBalance() method.'
        );
    }

    public function test_get_trial_balance_is_public(): void
    {
        $ref = new ReflectionMethod(AccountingService::class, 'getTrialBalance');
        $this->assertTrue($ref->isPublic(), 'getTrialBalance() must be public.');
    }

    public function test_get_trial_balance_accepts_integer_period_id(): void
    {
        $ref    = new ReflectionMethod(AccountingService::class, 'getTrialBalance');
        $params = $ref->getParameters();

        $this->assertCount(1, $params, 'getTrialBalance() must accept exactly 1 parameter.');
        $this->assertSame('fiscalPeriodId', $params[0]->getName());
        $this->assertSame('int', $params[0]->getType()?->getName());
    }

    public function test_get_trial_balance_return_type_is_array(): void
    {
        $ref = new ReflectionMethod(AccountingService::class, 'getTrialBalance');
        $this->assertSame('array', $ref->getReturnType()?->getName());
    }

    // -------------------------------------------------------------------------
    // getProfitAndLoss
    // -------------------------------------------------------------------------

    public function test_get_profit_and_loss_method_exists(): void
    {
        $this->assertTrue(
            method_exists(AccountingService::class, 'getProfitAndLoss'),
            'AccountingService must have a getProfitAndLoss() method.'
        );
    }

    public function test_get_profit_and_loss_is_public(): void
    {
        $ref = new ReflectionMethod(AccountingService::class, 'getProfitAndLoss');
        $this->assertTrue($ref->isPublic(), 'getProfitAndLoss() must be public.');
    }

    public function test_get_profit_and_loss_accepts_integer_period_id(): void
    {
        $ref    = new ReflectionMethod(AccountingService::class, 'getProfitAndLoss');
        $params = $ref->getParameters();

        $this->assertCount(1, $params, 'getProfitAndLoss() must accept exactly 1 parameter.');
        $this->assertSame('fiscalPeriodId', $params[0]->getName());
        $this->assertSame('int', $params[0]->getType()?->getName());
    }

    public function test_get_profit_and_loss_return_type_is_array(): void
    {
        $ref = new ReflectionMethod(AccountingService::class, 'getProfitAndLoss');
        $this->assertSame('array', $ref->getReturnType()?->getName());
    }

    // -------------------------------------------------------------------------
    // Service instantiation
    // -------------------------------------------------------------------------

    public function test_service_can_be_instantiated_with_three_repository_contracts(): void
    {
        $this->assertInstanceOf(AccountingService::class, $this->service);
    }

    // -------------------------------------------------------------------------
    // Service delegates to journal entry repository
    // -------------------------------------------------------------------------

    public function test_get_trial_balance_delegates_to_journal_entry_repository(): void
    {
        $journalRepo      = $this->createMock(JournalEntryRepositoryContract::class);
        $journalRepo->expects($this->once())
            ->method('findPostedLinesByPeriod')
            ->with(42)
            ->willReturn(new \Illuminate\Database\Eloquent\Collection());

        $accountRepo      = $this->createStub(AccountRepositoryContract::class);
        $fiscalPeriodRepo = $this->createStub(FiscalPeriodRepositoryContract::class);

        $service = new AccountingService($journalRepo, $accountRepo, $fiscalPeriodRepo);
        $result  = $service->getTrialBalance(42);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_get_profit_and_loss_delegates_to_journal_entry_repository(): void
    {
        $journalRepo      = $this->createMock(JournalEntryRepositoryContract::class);
        $journalRepo->expects($this->once())
            ->method('findPostedLinesByPeriod')
            ->with(7)
            ->willReturn(new \Illuminate\Database\Eloquent\Collection());

        $accountRepo      = $this->createStub(AccountRepositoryContract::class);
        $fiscalPeriodRepo = $this->createStub(FiscalPeriodRepositoryContract::class);

        $service = new AccountingService($journalRepo, $accountRepo, $fiscalPeriodRepo);
        $result  = $service->getProfitAndLoss(7);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total_revenue', $result);
        $this->assertArrayHasKey('total_expense', $result);
        $this->assertArrayHasKey('net_profit', $result);
    }

    // -------------------------------------------------------------------------
    // BCMath net-balance arithmetic (pure logic, no DB)
    // -------------------------------------------------------------------------

    public function test_net_profit_bcmath_calculation_is_accurate(): void
    {
        // Verify: revenue=1000.0000, expense=750.0000 → net=250.0000 (BCMath, no float)
        $revenue = '1000.0000';
        $expense = '750.0000';
        $net     = \Modules\Core\Application\Helpers\DecimalHelper::sub($revenue, $expense);

        $this->assertSame('250.0000', $net);
    }

    public function test_trial_balance_net_is_debit_minus_credit(): void
    {
        // Verify that net_balance = total_debit - total_credit for a single account
        $debit  = '500.0000';
        $credit = '350.0000';
        $net    = \Modules\Core\Application\Helpers\DecimalHelper::sub($debit, $credit);

        $this->assertSame('150.0000', $net);
    }
}
