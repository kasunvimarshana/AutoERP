<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use PHPUnit\Framework\TestCase;

final class FinanceBalanceSourceContractTest extends TestCase
{
    public function test_account_master_does_not_own_financial_balances(): void
    {
        foreach ([
            'Finance/Database/Migrations/2026_06_12_070003_create_finance_accounts_table.php',
            'Finance/Models/FinanceAccount.php',
            'Finance/DTOs/CreateAccountData.php',
            'Finance/Services/ChartOfAccountsService.php',
            'Finance/Database/Seeders/FinanceSeeder.php',
            'Finance/Http/Resources/FinanceAccountResource.php',
        ] as $relativePath) {
            $source = $this->source($relativePath);

            self::assertStringNotContainsString('opening_balance', $source, $relativePath);
            self::assertStringNotContainsString('current_balance', $source, $relativePath);
        }
    }

    public function test_account_api_explicitly_rejects_balance_mutations(): void
    {
        $source = $this->source('Finance/Http/Requests/StoreFinanceAccountRequest.php');

        self::assertStringContainsString("'opening_balance' => ['prohibited']", $source);
        self::assertStringContainsString("'current_balance' => ['prohibited']", $source);
    }

    public function test_ledger_facts_remain_authoritative_and_balance_after_is_rebuildable(): void
    {
        $migration = $this->source('Finance/Database/Migrations/2026_06_12_070011_create_finance_ledger_entries_table.php');
        $posting = $this->source('Finance/Services/LedgerPostingService.php');
        $projection = $this->source('Finance/Services/LedgerBalanceProjectionService.php');

        self::assertStringContainsString("decimal('balance_after'", $migration);
        self::assertStringContainsString('Rebuildable chronological projection', $migration);
        self::assertStringNotContainsString('current_balance', $posting);
        self::assertStringContainsString('rebuildForAccounts', $posting);
        self::assertStringContainsString("orderBy('entry_date')", $projection);
        self::assertStringContainsString("orderBy('id')", $projection);
        self::assertStringContainsString("'balance_after' => $runningBalance", $projection);
    }

    private function source(string $relativePath): string
    {
        $path = dirname(__DIR__, 3).'/app/Modules/'.$relativePath;
        $source = file_get_contents($path);
        self::assertNotFalse($source, $path);

        return $source;
    }
}
