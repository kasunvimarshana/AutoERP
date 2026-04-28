<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use PHPUnit\Framework\TestCase;

/**
 * Guardrails to keep Sales aggregate persistence id-aware and tenant-scoped.
 */
class SalesAggregateLineSyncGuardrailsTest extends TestCase
{
    public function test_sales_repositories_use_id_aware_line_sync_and_avoid_full_delete_recreate(): void
    {
        $repoRoot = dirname(__DIR__, 3);

        $files = [
            'app/Modules/Sales/Infrastructure/Persistence/Eloquent/Repositories/EloquentSalesOrderRepository.php',
            'app/Modules/Sales/Infrastructure/Persistence/Eloquent/Repositories/EloquentSalesInvoiceRepository.php',
            'app/Modules/Sales/Infrastructure/Persistence/Eloquent/Repositories/EloquentShipmentRepository.php',
            'app/Modules/Sales/Infrastructure/Persistence/Eloquent/Repositories/EloquentSalesReturnRepository.php',
        ];

        foreach ($files as $relativePath) {
            $absolutePath = $repoRoot.DIRECTORY_SEPARATOR.$relativePath;
            $this->assertFileExists($absolutePath, "Expected source file not found: {$relativePath}");

            $source = file_get_contents($absolutePath);
            $this->assertNotFalse($source, "Unable to read source file: {$relativePath}");

            $this->assertStringNotContainsString(
                '->lines()->delete();',
                $source,
                "{$relativePath} must not do full line delete/recreate writes."
            );

            $this->assertStringContainsString(
                '$keptLineIds = [];',
                $source,
                "{$relativePath} must track kept line IDs for id-aware synchronization."
            );

            $this->assertStringContainsString(
                'where(\'tenant_id\', (int) $model->tenant_id)',
                $source,
                "{$relativePath} must scope line updates/cleanup by tenant_id."
            );

            $this->assertStringContainsString(
                'whereNotIn(\'id\', $keptLineIds)->delete();',
                $source,
                "{$relativePath} must prune only removed lines after sync."
            );
        }
    }
}
