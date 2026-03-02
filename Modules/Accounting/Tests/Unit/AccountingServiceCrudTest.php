<?php

declare(strict_types=1);

namespace Modules\Accounting\Tests\Unit;

use Illuminate\Database\Eloquent\Model;
use Modules\Accounting\Application\Services\AccountingService;
use Modules\Accounting\Domain\Contracts\AccountRepositoryContract;
use Modules\Accounting\Domain\Contracts\FiscalPeriodRepositoryContract;
use Modules\Accounting\Domain\Contracts\JournalEntryRepositoryContract;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for AccountingService CRUD methods.
 *
 * Verifies method existence, visibility, and parameter signatures
 * for showAccount, updateAccount, showJournalEntry, and showFiscalPeriod.
 * No database or Laravel bootstrap required — uses reflection only.
 */
class AccountingServiceCrudTest extends TestCase
{
    // -------------------------------------------------------------------------
    // showAccount
    // -------------------------------------------------------------------------

    public function test_accounting_service_has_show_account_method(): void
    {
        $this->assertTrue(
            method_exists(AccountingService::class, 'showAccount'),
            'AccountingService must expose a public showAccount() method.'
        );
    }

    public function test_show_account_is_public(): void
    {
        $reflection = new \ReflectionMethod(AccountingService::class, 'showAccount');

        $this->assertTrue($reflection->isPublic());
    }

    public function test_show_account_accepts_id_parameter(): void
    {
        $reflection = new \ReflectionMethod(AccountingService::class, 'showAccount');
        $params     = $reflection->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('id', $params[0]->getName());
    }

    public function test_show_account_returns_model(): void
    {
        $reflection  = new \ReflectionMethod(AccountingService::class, 'showAccount');
        $returnType  = (string) $reflection->getReturnType();

        $this->assertSame(Model::class, $returnType);
    }

    // -------------------------------------------------------------------------
    // updateAccount
    // -------------------------------------------------------------------------

    public function test_accounting_service_has_update_account_method(): void
    {
        $this->assertTrue(
            method_exists(AccountingService::class, 'updateAccount'),
            'AccountingService must expose a public updateAccount() method.'
        );
    }

    public function test_update_account_is_public(): void
    {
        $reflection = new \ReflectionMethod(AccountingService::class, 'updateAccount');

        $this->assertTrue($reflection->isPublic());
    }

    public function test_update_account_accepts_id_and_data_parameters(): void
    {
        $reflection = new \ReflectionMethod(AccountingService::class, 'updateAccount');
        $params     = $reflection->getParameters();

        $this->assertCount(2, $params);
        $this->assertSame('id', $params[0]->getName());
        $this->assertSame('data', $params[1]->getName());
    }

    // -------------------------------------------------------------------------
    // showJournalEntry
    // -------------------------------------------------------------------------

    public function test_accounting_service_has_show_journal_entry_method(): void
    {
        $this->assertTrue(
            method_exists(AccountingService::class, 'showJournalEntry'),
            'AccountingService must expose a public showJournalEntry() method.'
        );
    }

    public function test_show_journal_entry_is_public(): void
    {
        $reflection = new \ReflectionMethod(AccountingService::class, 'showJournalEntry');

        $this->assertTrue($reflection->isPublic());
    }

    public function test_show_journal_entry_accepts_id_parameter(): void
    {
        $reflection = new \ReflectionMethod(AccountingService::class, 'showJournalEntry');
        $params     = $reflection->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('id', $params[0]->getName());
    }

    // -------------------------------------------------------------------------
    // showFiscalPeriod
    // -------------------------------------------------------------------------

    public function test_accounting_service_has_show_fiscal_period_method(): void
    {
        $this->assertTrue(
            method_exists(AccountingService::class, 'showFiscalPeriod'),
            'AccountingService must expose a public showFiscalPeriod() method.'
        );
    }

    public function test_show_fiscal_period_is_public(): void
    {
        $reflection = new \ReflectionMethod(AccountingService::class, 'showFiscalPeriod');

        $this->assertTrue($reflection->isPublic());
    }

    public function test_show_fiscal_period_accepts_id_parameter(): void
    {
        $reflection = new \ReflectionMethod(AccountingService::class, 'showFiscalPeriod');
        $params     = $reflection->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('id', $params[0]->getName());
    }

    // -------------------------------------------------------------------------
    // Delegation checks via mocking
    // -------------------------------------------------------------------------

    public function test_show_account_delegates_to_account_repository_find_or_fail(): void
    {
        $expected    = $this->createMock(Model::class);
        $accountRepo = $this->createMock(AccountRepositoryContract::class);
        $accountRepo->expects($this->once())
            ->method('findOrFail')
            ->with(42)
            ->willReturn($expected);

        $service = new AccountingService(
            $this->createMock(JournalEntryRepositoryContract::class),
            $accountRepo,
            $this->createMock(FiscalPeriodRepositoryContract::class),
        );

        $result = $service->showAccount(42);

        $this->assertSame($expected, $result);
    }

    public function test_show_journal_entry_delegates_to_journal_repository_find_or_fail(): void
    {
        $expected     = $this->createMock(Model::class);
        $journalRepo  = $this->createMock(JournalEntryRepositoryContract::class);
        $journalRepo->expects($this->once())
            ->method('findOrFail')
            ->with(7)
            ->willReturn($expected);

        $service = new AccountingService(
            $journalRepo,
            $this->createMock(AccountRepositoryContract::class),
            $this->createMock(FiscalPeriodRepositoryContract::class),
        );

        $result = $service->showJournalEntry(7);

        $this->assertSame($expected, $result);
    }

    public function test_show_fiscal_period_delegates_to_fiscal_period_repository_find_or_fail(): void
    {
        $expected      = $this->createMock(Model::class);
        $fiscalRepo    = $this->createMock(FiscalPeriodRepositoryContract::class);
        $fiscalRepo->expects($this->once())
            ->method('findOrFail')
            ->with(3)
            ->willReturn($expected);

        $service = new AccountingService(
            $this->createMock(JournalEntryRepositoryContract::class),
            $this->createMock(AccountRepositoryContract::class),
            $fiscalRepo,
        );

        $result = $service->showFiscalPeriod(3);

        $this->assertSame($expected, $result);
    }
}
