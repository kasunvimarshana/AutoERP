<?php

declare(strict_types=1);

namespace Modules\Accounting\Tests\Unit;

use Modules\Accounting\Application\Services\AccountingService;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * Unit tests for AccountingService::postEntry() — structural validation.
 *
 * postEntry() calls DB::transaction() and Eloquent model updates which require
 * a full application bootstrap. These tests validate structural contracts only.
 * Functional post/unpost flows are covered by feature tests.
 */
class AccountingServicePostEntryTest extends TestCase
{
    public function test_accounting_service_has_post_entry_method(): void
    {
        $this->assertTrue(
            method_exists(AccountingService::class, 'postEntry'),
            'AccountingService must expose a public postEntry() method.'
        );
    }

    public function test_post_entry_accepts_int_id(): void
    {
        $reflection = new ReflectionMethod(AccountingService::class, 'postEntry');
        $params     = $reflection->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('entryId', $params[0]->getName());
    }

    public function test_post_entry_is_public(): void
    {
        $reflection = new ReflectionMethod(AccountingService::class, 'postEntry');

        $this->assertTrue($reflection->isPublic());
    }

    public function test_accounting_service_has_create_journal_entry_method(): void
    {
        $this->assertTrue(
            method_exists(AccountingService::class, 'createJournalEntry'),
            'AccountingService must expose a public createJournalEntry() method.'
        );
    }

    public function test_accounting_service_has_list_entries_method(): void
    {
        $this->assertTrue(
            method_exists(AccountingService::class, 'listEntries'),
            'AccountingService must expose a public listEntries() method.'
        );
    }

    public function test_accounting_service_has_list_accounts_method(): void
    {
        $this->assertTrue(
            method_exists(AccountingService::class, 'listAccounts'),
            'AccountingService must expose a public listAccounts() method.'
        );
    }

    public function test_accounting_service_has_create_account_method(): void
    {
        $this->assertTrue(
            method_exists(AccountingService::class, 'createAccount'),
            'AccountingService must expose a public createAccount() method.'
        );
    }
}
