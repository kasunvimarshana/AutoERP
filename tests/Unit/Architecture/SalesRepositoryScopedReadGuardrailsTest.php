<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use PHPUnit\Framework\TestCase;

/**
 * Guardrails for tenant-scoped Sales repository reads.
 *
 * Sales aggregate repositories must use newScopedQuery() for generic reads
 * so current tenant binding is always applied.
 */
class SalesRepositoryScopedReadGuardrailsTest extends TestCase
{
    public function testSalesRepositoriesUseScopedQueryForFindAndLookupReads(): void
    {
        $repoRoot = dirname(__DIR__, 3);

        $expectations = [
            'app/Modules/Sales/Infrastructure/Persistence/Eloquent/Repositories/EloquentSalesOrderRepository.php' => [
                "newScopedQuery()->with('lines')->find",
                "newScopedQuery()->with('lines')",
                'where(\'tenant_id\', $tenantId)',
            ],
            'app/Modules/Sales/Infrastructure/Persistence/Eloquent/Repositories/EloquentSalesInvoiceRepository.php' => [
                "newScopedQuery()->with('lines')->find",
                "newScopedQuery()->with('lines')",
                'where(\'tenant_id\', $tenantId)',
            ],
            'app/Modules/Sales/Infrastructure/Persistence/Eloquent/Repositories/EloquentShipmentRepository.php' => [
                "newScopedQuery()->with('lines')->find",
                "newScopedQuery()->with('lines')",
                'where(\'tenant_id\', $tenantId)',
            ],
            'app/Modules/Sales/Infrastructure/Persistence/Eloquent/Repositories/EloquentSalesReturnRepository.php' => [
                "newScopedQuery()->with('lines')->find",
                "newScopedQuery()->with('lines')",
                'where(\'tenant_id\', $tenantId)',
            ],
        ];

        foreach ($expectations as $relativePath => $requiredSnippets) {
            $absolutePath = $repoRoot . DIRECTORY_SEPARATOR . $relativePath;
            $this->assertFileExists($absolutePath, "Expected source file not found: {$relativePath}");

            $source = file_get_contents($absolutePath);
            $this->assertNotFalse($source, "Unable to read source file: {$relativePath}");

            foreach ($requiredSnippets as $snippet) {
                $this->assertStringContainsString(
                    $snippet,
                    $source,
                    "{$relativePath} must contain snippet: {$snippet}"
                );
            }
        }
    }
}
