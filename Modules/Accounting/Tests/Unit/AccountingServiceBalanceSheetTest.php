<?php

declare(strict_types=1);

namespace Modules\Accounting\Tests\Unit;

use Illuminate\Database\Eloquent\Collection;
use Modules\Accounting\Application\Services\AccountingService;
use Modules\Accounting\Domain\Contracts\AccountRepositoryContract;
use Modules\Accounting\Domain\Contracts\FiscalPeriodRepositoryContract;
use Modules\Accounting\Domain\Contracts\JournalEntryRepositoryContract;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * Unit tests for AccountingService::getBalanceSheet().
 *
 * Validates method signature, return type structure, section keys,
 * and BCMath balance arithmetic. No DB required.
 */
class AccountingServiceBalanceSheetTest extends TestCase
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
    // Method existence and visibility
    // -------------------------------------------------------------------------

    public function test_get_balance_sheet_method_exists(): void
    {
        $this->assertTrue(
            method_exists(AccountingService::class, 'getBalanceSheet'),
            'AccountingService must have a getBalanceSheet() method.'
        );
    }

    public function test_get_balance_sheet_is_public(): void
    {
        $ref = new ReflectionMethod(AccountingService::class, 'getBalanceSheet');
        $this->assertTrue($ref->isPublic(), 'getBalanceSheet() must be public.');
    }

    // -------------------------------------------------------------------------
    // Parameter signature
    // -------------------------------------------------------------------------

    public function test_get_balance_sheet_accepts_integer_period_id(): void
    {
        $ref    = new ReflectionMethod(AccountingService::class, 'getBalanceSheet');
        $params = $ref->getParameters();

        $this->assertCount(1, $params, 'getBalanceSheet() must accept exactly 1 parameter.');
        $this->assertSame('fiscalPeriodId', $params[0]->getName());
        $this->assertSame('int', $params[0]->getType()?->getName());
    }

    // -------------------------------------------------------------------------
    // Return type
    // -------------------------------------------------------------------------

    public function test_get_balance_sheet_return_type_is_array(): void
    {
        $ref = new ReflectionMethod(AccountingService::class, 'getBalanceSheet');
        $this->assertSame('array', $ref->getReturnType()?->getName());
    }

    // -------------------------------------------------------------------------
    // Delegation and response structure
    // -------------------------------------------------------------------------

    public function test_get_balance_sheet_delegates_to_journal_entry_repository(): void
    {
        $journalRepo = $this->createMock(JournalEntryRepositoryContract::class);
        $journalRepo->expects($this->once())
            ->method('findPostedLinesByPeriod')
            ->with(5)
            ->willReturn(new Collection());

        $accountRepo      = $this->createStub(AccountRepositoryContract::class);
        $fiscalPeriodRepo = $this->createStub(FiscalPeriodRepositoryContract::class);

        $service = new AccountingService($journalRepo, $accountRepo, $fiscalPeriodRepo);
        $result  = $service->getBalanceSheet(5);

        $this->assertIsArray($result);
    }

    public function test_get_balance_sheet_result_has_required_section_keys(): void
    {
        $journalRepo = $this->createMock(JournalEntryRepositoryContract::class);
        $journalRepo->method('findPostedLinesByPeriod')->willReturn(new Collection());

        $accountRepo      = $this->createStub(AccountRepositoryContract::class);
        $fiscalPeriodRepo = $this->createStub(FiscalPeriodRepositoryContract::class);

        $service = new AccountingService($journalRepo, $accountRepo, $fiscalPeriodRepo);
        $result  = $service->getBalanceSheet(1);

        $this->assertArrayHasKey('assets', $result);
        $this->assertArrayHasKey('liabilities', $result);
        $this->assertArrayHasKey('equity', $result);
        $this->assertArrayHasKey('total_assets', $result);
        $this->assertArrayHasKey('total_liabilities', $result);
        $this->assertArrayHasKey('total_equity', $result);
    }

    public function test_get_balance_sheet_totals_initialise_to_zero(): void
    {
        $journalRepo = $this->createMock(JournalEntryRepositoryContract::class);
        $journalRepo->method('findPostedLinesByPeriod')->willReturn(new Collection());

        $accountRepo      = $this->createStub(AccountRepositoryContract::class);
        $fiscalPeriodRepo = $this->createStub(FiscalPeriodRepositoryContract::class);

        $service = new AccountingService($journalRepo, $accountRepo, $fiscalPeriodRepo);
        $result  = $service->getBalanceSheet(1);

        $this->assertSame('0.0000', $result['total_assets']);
        $this->assertSame('0.0000', $result['total_liabilities']);
        $this->assertSame('0.0000', $result['total_equity']);
    }

    // -------------------------------------------------------------------------
    // BCMath balance arithmetic (pure logic, no DB)
    // -------------------------------------------------------------------------

    public function test_bcmath_asset_debit_increases_balance(): void
    {
        // Assets are debit-normal: a debit of 5000.0000 should give balance = 5000.0000
        $debit   = '5000.0000';
        $initial = '0.0000';

        $balance = \Modules\Core\Application\Helpers\DecimalHelper::add($initial, $debit);

        $this->assertSame('5000.0000', $balance);
    }

    public function test_bcmath_liability_credit_increases_balance(): void
    {
        // Liabilities are credit-normal: a credit of 3000.0000 gives balance = 3000.0000
        $credit  = '3000.0000';
        $initial = '0.0000';

        $balance = \Modules\Core\Application\Helpers\DecimalHelper::add($initial, $credit);

        $this->assertSame('3000.0000', $balance);
    }
}
