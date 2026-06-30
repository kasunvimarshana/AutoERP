<?php

declare(strict_types=1);

namespace Modules\Finance\Tests;

use PHPUnit\Framework\TestCase;

final class FinanceAccountAssignmentScopeTest extends TestCase
{
    public function test_runtime_and_migration_both_enforce_account_scope(): void
    {
        $service = file_get_contents(__DIR__.'/../Services/AccountRoleAssignmentService.php');
        $migration = file_get_contents(__DIR__.'/../Database/Migrations/2026_06_30_000003_validate_account_assignment_scope.php');

        self::assertIsString($service);
        self::assertIsString($migration);
        self::assertStringContainsString('Finance account assignment account belongs to a different scope.', $service);
        self::assertStringContainsString('Finance account assignment scope does not match its assigned account scope.', $migration);
        self::assertStringContainsString("whereColumn('assignment.tenant_id', '<>', 'account.tenant_id')", $migration);
        self::assertStringContainsString("whereColumn('assignment.organization_unit_id', '<>', 'account.organization_unit_id')", $migration);
    }
}
