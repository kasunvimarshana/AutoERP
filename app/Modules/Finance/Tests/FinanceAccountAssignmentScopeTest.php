<?php

declare(strict_types=1);

namespace Modules\Finance\Tests;

use PHPUnit\Framework\TestCase;

final class FinanceAccountAssignmentScopeTest extends TestCase
{
    public function test_runtime_and_migration_both_enforce_account_scope(): void
    {
        $service = file_get_contents(__DIR__.'/../Services/AccountRoleAssignmentService.php');
        $migration = file_get_contents(__DIR__.'/../Database/Migrations/2026_06_12_070018_create_finance_account_assignments_table.php');

        self::assertIsString($service);
        self::assertIsString($migration);
        self::assertStringContainsString('Finance account assignment account belongs to a different scope.', $service);
        self::assertStringContainsString("['account_id', 'tenant_id']", $migration);
        self::assertStringContainsString("->on('finance_accounts')", $migration);
        self::assertStringContainsString('organization_unit_id', $service);
        self::assertStringContainsString('effective period overlaps', $service);
    }
}
