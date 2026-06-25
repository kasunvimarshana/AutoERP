<?php

declare(strict_types=1);

namespace Tests\Unit\Database;

use FilesystemIterator;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

final class TenantIsolationArchitectureTest extends TestCase
{
    public function test_tenant_schema_uses_composite_identity_and_same_tenant_foreign_keys(): void
    {
        $tables = $this->tableMetadata();
        $violations = [];

        foreach ($tables as $table => $metadata) {
            if ($metadata['tenant_owned'] && ! $metadata['has_identity_candidate_key']) {
                $violations[] = "{$table}: missing unique [id, tenant_id] candidate key";
            }

            foreach ($this->compositeTenantForeignKeys($metadata['source']) as $foreignKey) {
                $parent = $foreignKey['parent'];
                $column = $foreignKey['column'];

                if (! isset($tables[$parent])) {
                    $violations[] = "{$table}.{$column}: references unknown table {$parent}";
                    continue;
                }

                if (! $tables[$parent]['has_tenant_id']) {
                    $violations[] = "{$table}.{$column}: composite tenant key points to non-tenant table {$parent}";
                }

                if (! $tables[$parent]['has_identity_candidate_key']) {
                    $violations[] = "{$table}.{$column}: parent {$parent} lacks unique [id, tenant_id]";
                }

                if (str_contains($foreignKey['tail'], 'nullOnDelete')) {
                    $violations[] = "{$table}.{$column}: same-tenant relationship cannot null only the foreign id";
                }
            }

            if (! $metadata['tenant_owned']) {
                continue;
            }

            foreach ($this->simpleForeignKeys($metadata['source']) as $foreignKey) {
                $parent = $foreignKey['parent'];
                if (
                    $foreignKey['column'] !== 'tenant_id'
                    && isset($tables[$parent])
                    && $tables[$parent]['has_tenant_id']
                ) {
                    $violations[] = sprintf(
                        '%s.%s: tenant-bearing parent %s must use one composite [id, tenant_id] foreign key',
                        $table,
                        $foreignKey['column'],
                        $parent,
                    );
                }
            }
        }

        self::assertSame([], $violations, implode(PHP_EOL, $violations));
    }

    public function test_models_for_required_tenant_tables_use_the_mandatory_scope_boundary(): void
    {
        $tenantTables = [];
        foreach ($this->tableMetadata() as $table => $metadata) {
            if ($metadata['tenant_owned']) {
                $tenantTables[$table] = true;
            }
        }

        $root = dirname(__DIR__, 3).'/app/Modules';
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
        );
        $violations = [];

        foreach ($iterator as $file) {
            $path = str_replace('\\', '/', $file->getPathname());
            if (
                ! $file->isFile()
                || $file->getExtension() !== 'php'
                || ! str_contains($path, '/Models/')
            ) {
                continue;
            }

            $source = (string) file_get_contents($file->getPathname());
            if (preg_match("/protected \\$table = '([^']+)'/", $source, $tableMatch) !== 1) {
                continue;
            }

            $table = $tableMatch[1];
            if (! isset($tenantTables[$table])) {
                continue;
            }

            $isScoped = str_contains($source, 'extends TenantOwnedModel')
                || str_contains($source, 'extends HrMasterModel')
                || str_contains($source, 'use HasTenantScope;');
            if (! $isScoped) {
                $violations[] = "{$table}: {$path} does not use the mandatory tenant scope";
            }
        }

        self::assertSame([], $violations, implode(PHP_EOL, $violations));
    }

    public function test_requests_with_tenant_fields_use_the_context_owned_request_boundary(): void
    {
        $inheritance = $this->requestInheritance();
        $root = dirname(__DIR__, 3).'/app/Modules';
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
        );
        $violations = [];

        foreach ($iterator as $file) {
            $path = str_replace('\\', '/', $file->getPathname());
            if (
                ! $file->isFile()
                || $file->getExtension() !== 'php'
                || ! str_contains($path, '/Http/Requests/')
            ) {
                continue;
            }

            $source = (string) file_get_contents($file->getPathname());
            if (! str_contains($source, "'tenant_id' =>")) {
                continue;
            }

            if (preg_match('/(?:abstract\s+|final\s+)?class\s+([A-Za-z0-9_]+)/', $source, $match) !== 1) {
                $violations[] = "{$path}: request class could not be resolved";
                continue;
            }

            if (! $this->isTenantScopedRequest($match[1], $inheritance)) {
                $violations[] = "{$path}: tenant_id is not owned by TenantScopedRequest";
            }
        }

        self::assertSame([], $violations, implode(PHP_EOL, $violations));
    }

    public function test_request_validation_has_no_unscoped_exists_or_unique_rules_for_tenant_tables(): void
    {
        $tenantTables = [];
        foreach ($this->tableMetadata() as $table => $metadata) {
            if ($metadata['has_tenant_id']) {
                $tenantTables[$table] = true;
            }
        }

        $root = dirname(__DIR__, 3).'/app/Modules';
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
        );
        $violations = [];

        foreach ($iterator as $file) {
            $path = str_replace('\\', '/', $file->getPathname());
            if (
                ! $file->isFile()
                || $file->getExtension() !== 'php'
                || ! str_contains($path, '/Http/Requests/')
            ) {
                continue;
            }

            $source = (string) file_get_contents($file->getPathname());
            preg_match_all(
                '/(?:exists|unique):([A-Za-z0-9_]+),[A-Za-z0-9_]+/',
                $source,
                $matches,
            );

            foreach ($matches[1] as $table) {
                if (isset($tenantTables[$table])) {
                    $violations[] = "{$path}: unscoped validation rule references {$table}";
                }
            }

            preg_match_all(
                "/Rule::(?:exists|unique)\(\s*'([A-Za-z0-9_]+)'/",
                $source,
                $ruleMatches,
            );
            foreach ($ruleMatches[1] as $table) {
                if (isset($tenantTables[$table])) {
                    $violations[] = "{$path}: tenant table {$table} must use tenantExists/tenantUnique";
                }
            }
        }

        self::assertSame([], $violations, implode(PHP_EOL, $violations));
    }

    public function test_application_code_has_no_global_scope_escape_hatch(): void
    {
        $root = dirname(__DIR__, 3).'/app/Modules';
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
        );
        $violations = [];

        foreach ($iterator as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $source = (string) file_get_contents($file->getPathname());
            foreach (['withoutGlobalScope(', 'withoutGlobalScopes(', 'newQueryWithoutScopes('] as $escape) {
                if (str_contains($source, $escape)) {
                    $violations[] = str_replace('\\', '/', $file->getPathname()).": contains {$escape}";
                }
            }
        }

        self::assertSame([], $violations, implode(PHP_EOL, $violations));
    }

    public function test_tenant_context_is_established_before_route_model_binding(): void
    {
        $source = (string) file_get_contents(dirname(__DIR__, 3).'/bootstrap/app.php');
        $priorityStart = strpos($source, '$middleware->priority([');
        self::assertNotFalse($priorityStart, 'Middleware priority list is missing.');
        $priorityEnd = strpos($source, ']);', $priorityStart);
        self::assertNotFalse($priorityEnd, 'Middleware priority list is incomplete.');
        $priorityBlock = substr($source, $priorityStart, $priorityEnd - $priorityStart);

        $orderedMiddleware = [
            'AuthenticatesRequests::class',
            'CurrentUserMiddleware::class',
            'CurrentTenantMiddleware::class',
            'CurrentOrganizationUnitMiddleware::class',
            'SubstituteBindings::class',
        ];
        $previousPosition = -1;

        foreach ($orderedMiddleware as $middleware) {
            $position = strpos($priorityBlock, $middleware);
            self::assertNotFalse($position, "Missing middleware priority entry: {$middleware}");
            self::assertGreaterThan(
                $previousPosition,
                $position,
                "Middleware priority is invalid around {$middleware}",
            );
            $previousPosition = $position;
        }
    }

    public function test_tenant_scope_does_not_treat_a_query_predicate_as_authorization(): void
    {
        $source = (string) file_get_contents(
            dirname(__DIR__, 3).'/app/Modules/Core/Database/Scopes/TenantScope.php',
        );

        self::assertStringNotContainsString('getQuery()->wheres', $source);
        self::assertStringNotContainsString('hasExplicitTenantEquality', $source);
        self::assertStringContainsString("whereRaw('1 = 0')", $source);
        self::assertStringContainsString('isControlPlane()', $source);
    }

    /** @return array<string,string> */
    private function requestInheritance(): array
    {
        $root = dirname(__DIR__, 3).'/app/Modules';
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
        );
        $inheritance = ['TenantScopedRequest' => 'QueryRequest'];

        foreach ($iterator as $file) {
            $path = str_replace('\\', '/', $file->getPathname());
            if (
                ! $file->isFile()
                || $file->getExtension() !== 'php'
                || ! str_contains($path, '/Http/Requests/')
            ) {
                continue;
            }

            $source = (string) file_get_contents($file->getPathname());
            if (preg_match(
                '/(?:abstract\s+|final\s+)?class\s+([A-Za-z0-9_]+)\s+extends\s+([A-Za-z0-9_]+)/',
                $source,
                $match,
            ) === 1) {
                $inheritance[$match[1]] = $match[2];
            }
        }

        return $inheritance;
    }

    /** @param array<string,string> $inheritance */
    private function isTenantScopedRequest(string $class, array $inheritance): bool
    {
        $visited = [];

        while ($class !== '') {
            if ($class === 'TenantScopedRequest') {
                return true;
            }
            if (isset($visited[$class]) || ! isset($inheritance[$class])) {
                return false;
            }

            $visited[$class] = true;
            $class = $inheritance[$class];
        }

        return false;
    }

    /**
     * @return array<string,array{
     *     path:string,
     *     source:string,
     *     tenant_owned:bool,
     *     has_tenant_id:bool,
     *     has_identity_candidate_key:bool
     * }>
     */
    private function tableMetadata(): array
    {
        $tables = [];

        foreach ($this->migrationSources() as $path => $source) {
            if (preg_match("/Schema::create\\('([^']+)'/", $source, $match) !== 1) {
                continue;
            }

            $tables[$match[1]] = [
                'path' => $path,
                'source' => $source,
                'tenant_owned' => $this->hasRequiredTenantId($source),
                'has_tenant_id' => str_contains($source, "('tenant_id')"),
                'has_identity_candidate_key' => preg_match(
                    "/\\$table->unique\\(\\s*\\[\\s*'id'\\s*,\\s*'tenant_id'\\s*\\]/s",
                    $source,
                ) === 1,
            ];
        }

        return $tables;
    }

    /** @return list<array{column:string,parent:string,tail:string}> */
    private function compositeTenantForeignKeys(string $source): array
    {
        preg_match_all(
            "/\\$table->foreign\\(\\s*\\[\\s*'([^']+)'\\s*,\\s*'tenant_id'\\s*\\](?:\\s*,\\s*'[^']+')?\\s*\\)"
            ."\\s*->references\\(\\s*\\[\\s*'id'\\s*,\\s*'tenant_id'\\s*\\]\\s*\\)"
            ."\\s*->on\\('([^']+)'\\)([^;]*);/s",
            $source,
            $matches,
            PREG_SET_ORDER,
        );

        return array_map(
            static fn (array $match): array => [
                'column' => $match[1],
                'parent' => $match[2],
                'tail' => $match[3],
            ],
            $matches,
        );
    }

    /** @return list<array{column:string,parent:string}> */
    private function simpleForeignKeys(string $source): array
    {
        preg_match_all(
            "/\\$table->foreignId\\('([^']+)'\\)([^;]*?)"
            ."->constrained\\('([^']+)'(?:\\s*,\\s*'id')?(?:\\s*,\\s*'[^']+')?\\)([^;]*);/s",
            $source,
            $constrainedMatches,
            PREG_SET_ORDER,
        );
        preg_match_all(
            "/\\$table->foreign\\(\\s*'([^']+)'[^)]*\\)"
            ."\\s*->references\\(\\s*'[^']+'\\s*\\)"
            ."\\s*->on\\(\\s*'([^']+)'\\s*\\)([^;]*);/s",
            $source,
            $explicitMatches,
            PREG_SET_ORDER,
        );

        return [
            ...array_map(
                static fn (array $match): array => [
                    'column' => $match[1],
                    'parent' => $match[3],
                ],
                $constrainedMatches,
            ),
            ...array_map(
                static fn (array $match): array => [
                    'column' => $match[1],
                    'parent' => $match[2],
                ],
                $explicitMatches,
            ),
        ];
    }

    /** @return array<string,string> */
    private function migrationSources(): array
    {
        $root = dirname(__DIR__, 3).'/app/Modules';
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
        );
        $sources = [];

        foreach ($iterator as $file) {
            $path = str_replace('\\', '/', $file->getPathname());
            if ($file->isFile() && $file->getExtension() === 'php' && str_contains($path, '/Database/Migrations/')) {
                $sources[$path] = (string) file_get_contents($file->getPathname());
            }
        }

        return $sources;
    }

    private function hasRequiredTenantId(string $source): bool
    {
        if (preg_match("/\\$table->(?:foreignId|unsignedBigInteger|bigInteger|uuid|unsignedInteger|integer)\\('tenant_id'\\)([^;]*);/s", $source, $match) !== 1) {
            return false;
        }

        return ! str_contains($match[1], '->nullable()');
    }
}
