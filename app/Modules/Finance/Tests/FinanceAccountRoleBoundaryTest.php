<?php

declare(strict_types=1);

namespace Modules\Finance\Tests;

use PHPUnit\Framework\TestCase;

final class FinanceAccountRoleBoundaryTest extends TestCase
{
    public function test_posting_profiles_map_semantic_keys_to_roles_not_accounts(): void
    {
        $rule = $this->source('../Models/FinancePostingProfileRule.php');
        $request = $this->source('../Http/Requests/UpsertPostingProfileRequest.php');
        $service = $this->source('../Services/PostingProfileService.php');
        $resource = $this->source('../Http/Resources/PostingProfileRuleResource.php');

        self::assertStringContainsString('account_role_id', $rule.$request.$service.$resource);
        self::assertStringNotContainsString("'account_id' => 'integer'", $rule);
        self::assertStringNotContainsString('function account()', $rule);
        self::assertStringNotContainsString("'rules.*.account_id' => ['required'", $request);
        self::assertStringContainsString("'rules.*.account_id' => ['prohibited']", $request);
        self::assertStringContainsString('rules.role', $service);
        self::assertStringContainsString('use a semantic posting profile key', $service);
    }

    public function test_account_resolution_is_effective_dated_and_scope_aware(): void
    {
        $service = $this->source('../Services/AccountRoleAssignmentService.php');
        $posting = $this->source('../Services/PostingProfileService.php');

        self::assertStringContainsString("whereDate('effective_from', '<=', $date)", $service);
        self::assertStringContainsString("whereNull('effective_to')->orWhereDate('effective_to', '>=', $date)", $service);
        self::assertStringContainsString("orderByRaw('organization_unit_id IS NULL ASC')", $service);
        self::assertStringContainsString('ambiguous effective assignments', $service);
        self::assertStringContainsString('effective period overlaps', $service);
        self::assertStringContainsString('$request->postingDate', $posting);
    }

    public function test_schema_migrates_direct_rules_without_silent_conflict_resolution(): void
    {
        $migration = $this->source('../Database/Migrations/2026_06_30_000002_create_effective_account_role_assignments.php');

        self::assertStringContainsString("Schema::create(self::ROLES", $migration);
        self::assertStringContainsString("Schema::create(self::ASSIGNMENTS", $migration);
        self::assertStringContainsString('account_role_id', $migration);
        self::assertStringContainsString('Conflicting direct posting mappings exist', $migration);
        self::assertStringContainsString("Schema::drop(self::RULES)", $migration);
        self::assertStringNotContainsString('suspense', strtolower($migration));
    }

    public function test_finance_configuration_routes_have_a_single_owner(): void
    {
        $routes = $this->source('../Routes/api.php');
        $controller = $this->source('../Http/Controllers/FinanceConfigurationController.php');
        $frontendTypes = $this->externalSource(dirname(__DIR__, 4).'/resources/js/modules/finance/financeTypes.ts');

        self::assertStringContainsString('FinanceConfigurationController', $routes);
        self::assertStringContainsString('account-roles', $routes);
        self::assertStringContainsString('account-assignments', $routes);
        self::assertStringContainsString("with('rules.role')", $controller);
        self::assertStringContainsString('account_roles', $frontendTypes);
        self::assertStringContainsString('account_assignments', $frontendTypes);
        self::assertStringNotContainsString('account_id: number; description', $frontendTypes);
    }

    private function source(string $relativePath): string
    {
        return $this->externalSource(__DIR__.'/'.$relativePath);
    }

    private function externalSource(string $path): string
    {
        $source = file_get_contents($path);
        self::assertIsString($source);

        return $source;
    }
}
