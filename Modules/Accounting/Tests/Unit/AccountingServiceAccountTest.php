<?php

declare(strict_types=1);

namespace Modules\Accounting\Tests\Unit;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Modules\Accounting\Application\Services\AccountingService;
use Modules\Accounting\Domain\Contracts\AccountRepositoryContract;
use Modules\Accounting\Domain\Contracts\FiscalPeriodRepositoryContract;
use Modules\Accounting\Domain\Contracts\JournalEntryRepositoryContract;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for AccountingService chart-of-accounts methods.
 *
 * Tests listAccounts() delegation and method existence for createAccount().
 * No database or Laravel bootstrap required — repositories are mocked.
 */
class AccountingServiceAccountTest extends TestCase
{
    private function makeService(
        ?JournalEntryRepositoryContract $journalRepo = null,
        ?AccountRepositoryContract $accountRepo = null,
    ): AccountingService {
        return new AccountingService(
            $journalRepo ?? $this->createMock(JournalEntryRepositoryContract::class),
            $accountRepo ?? $this->createMock(AccountRepositoryContract::class),
            $this->createMock(FiscalPeriodRepositoryContract::class),
        );
    }

    // -------------------------------------------------------------------------
    // listAccounts — delegates to accountRepository->all()
    // -------------------------------------------------------------------------

    public function test_list_accounts_delegates_to_repository_all(): void
    {
        $expected = new Collection();

        $accountRepo = $this->createMock(AccountRepositoryContract::class);
        $accountRepo->expects($this->once())
            ->method('all')
            ->willReturn($expected);

        $result = $this->makeService(accountRepo: $accountRepo)->listAccounts();

        $this->assertSame($expected, $result);
    }

    public function test_list_accounts_returns_collection(): void
    {
        $accountRepo = $this->createMock(AccountRepositoryContract::class);
        $accountRepo->method('all')->willReturn(new Collection());

        $result = $this->makeService(accountRepo: $accountRepo)->listAccounts();

        $this->assertInstanceOf(Collection::class, $result);
    }

    public function test_list_accounts_returns_empty_collection_when_none_exist(): void
    {
        $accountRepo = $this->createMock(AccountRepositoryContract::class);
        $accountRepo->method('all')->willReturn(new Collection());

        $result = $this->makeService(accountRepo: $accountRepo)->listAccounts();

        $this->assertCount(0, $result);
    }

    public function test_list_accounts_returns_populated_collection(): void
    {
        $model1 = $this->createMock(Model::class);
        $model2 = $this->createMock(Model::class);

        $accountRepo = $this->createMock(AccountRepositoryContract::class);
        $accountRepo->method('all')->willReturn(new Collection([$model1, $model2]));

        $result = $this->makeService(accountRepo: $accountRepo)->listAccounts();

        $this->assertCount(2, $result);
    }

    // -------------------------------------------------------------------------
    // createAccount — method existence and signature
    // -------------------------------------------------------------------------

    public function test_accounting_service_has_create_account_method(): void
    {
        $this->assertTrue(
            method_exists(AccountingService::class, 'createAccount'),
            'AccountingService must expose a public createAccount() method.'
        );
    }

    public function test_create_account_accepts_array_parameter(): void
    {
        $reflection = new \ReflectionMethod(AccountingService::class, 'createAccount');
        $params     = $reflection->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('data', $params[0]->getName());
    }

    // -------------------------------------------------------------------------
    // listAccounts — method existence
    // -------------------------------------------------------------------------

    public function test_accounting_service_has_list_accounts_method(): void
    {
        $this->assertTrue(
            method_exists(AccountingService::class, 'listAccounts'),
            'AccountingService must expose a public listAccounts() method.'
        );
    }

    public function test_list_accounts_return_type_is_collection(): void
    {
        $reflection = new \ReflectionMethod(AccountingService::class, 'listAccounts');

        $this->assertSame(Collection::class, (string) $reflection->getReturnType());
    }
}
