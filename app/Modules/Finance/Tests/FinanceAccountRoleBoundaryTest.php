<?php

declare(strict_types=1);

namespace Modules\Finance\Tests;

use Modules\Finance\DTOs\FinancePostingLine;
use Modules\Finance\DTOs\PostingLine;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

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
        self::assertStringContainsString('semantic posting profile mapping key', $service);
    }

    public function test_posting_line_dtos_have_no_direct_account_selector_surface(): void
    {
        foreach ([PostingLine::class, FinancePostingLine::class] as $dto) {
            $reflection = new ReflectionClass($dto);
            $constructorParameters = array_map(
                static fn ($parameter): string => $parameter->getName(),
                $reflection->getConstructor()?->getParameters() ?? [],
            );
            $properties = array_map(
                static fn ($property): string => $property->getName(),
                $reflection->getProperties(),
            );

            self::assertNotContains('accountCode', $constructorParameters);
            self::assertNotContains('account', $constructorParameters);
            self::assertNotContains('accountCode', $properties);
            self::assertNotContains('account', $properties);
            self::assertContains('profileKey', $constructorParameters);
            self::assertContains('profileKey', $properties);
        }
    }

    public function test_account_resolution_is_effective_dated_and_scope_aware(): void
    {
        $service = $this->source('../Services/AccountRoleAssignmentService.php');
        $posting = $this->source('../Services/PostingProfileService.php');

        self::assertStringContainsString("whereDate('effective_from', '<=', \$date)", $service);
        self::assertStringContainsString("whereNull('effective_to')->orWhereDate('effective_to', '>=', \$date)", $service);
        self::assertStringContainsString("orderByRaw('organization_unit_id IS NULL ASC')", $service);
        self::assertStringContainsString('ambiguous effective assignments', $service);
        self::assertStringContainsString('effective period overlaps', $service);
        self::assertStringContainsString('$request->postingDate', $posting);
    }

    public function test_posting_profile_rules_are_validity_dated_and_not_recreated(): void
    {
        $migration = $this->source('../Database/Migrations/2026_06_12_070019_create_finance_posting_profile_rules_table.php');
        $model = $this->source('../Models/FinancePostingProfileRule.php');
        $request = $this->source('../Http/Requests/UpsertPostingProfileRequest.php');
        $resource = $this->source('../Http/Resources/PostingProfileRuleResource.php');
        $service = $this->source('../Services/PostingProfileService.php');

        self::assertStringContainsString('effective_from', $migration.$model.$request.$resource.$service);
        self::assertStringContainsString('effective_to', $migration.$model.$request.$resource.$service);
        self::assertStringContainsString('is_active', $migration.$model.$request.$resource.$service);
        self::assertStringContainsString("whereDate('effective_from', '<=', \$request->postingDate)", $service);
        self::assertStringContainsString("orWhereDate('effective_to', '>=', \$request->postingDate)", $service);
        self::assertStringContainsString('assertNoOverlappingRule', $service);
        self::assertStringNotContainsString('rules()->delete()', $service);
    }

    public function test_schema_migrates_direct_rules_without_silent_conflict_resolution(): void
    {
        $roles = $this->source('../Database/Migrations/2026_06_12_070017_create_finance_account_roles_table.php');
        $assignments = $this->source('../Database/Migrations/2026_06_12_070018_create_finance_account_assignments_table.php');
        $rules = $this->source('../Database/Migrations/2026_06_12_070019_create_finance_posting_profile_rules_table.php');
        $schema = $roles.$assignments.$rules;

        self::assertStringContainsString("Schema::create('finance_account_roles'", $roles);
        self::assertStringContainsString("Schema::create('finance_account_assignments'", $assignments);
        self::assertStringContainsString("Schema::create('finance_posting_profile_rules'", $rules);
        self::assertStringContainsString('account_role_id', $schema);
        self::assertStringContainsString('effective_from', $assignments);
        self::assertStringContainsString('effective_from', $rules);
        self::assertStringNotContainsString('account_id', $rules);
        self::assertStringNotContainsString('suspense', strtolower($schema));
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
        self::assertStringContainsString('effective_from', $frontendTypes);
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
