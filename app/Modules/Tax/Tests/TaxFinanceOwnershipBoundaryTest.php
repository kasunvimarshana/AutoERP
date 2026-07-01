<?php

declare(strict_types=1);

namespace Modules\Tax\Tests;

use PHPUnit\Framework\TestCase;

final class TaxFinanceOwnershipBoundaryTest extends TestCase
{
    public function test_tax_transactions_do_not_own_finance_account_identity(): void
    {
        $model = $this->source('../Models/TaxTransaction.php');
        $service = $this->source('../Services/TaxSnapshotService.php');
        $migration = $this->source('../Database/Migrations/2026_06_12_080007_create_tax_transactions_table.php');
        $source = $model.$service.$migration;

        self::assertStringNotContainsString('Modules\\Finance', $source);
        self::assertStringNotContainsString('FinanceAccount', $source);
        self::assertStringNotContainsString("'account_id'", $source);
        self::assertStringNotContainsString("finance_accounts", $source);
    }

    private function source(string $relativePath): string
    {
        $source = file_get_contents(__DIR__.'/'.$relativePath);
        self::assertIsString($source);

        return $source;
    }
}
