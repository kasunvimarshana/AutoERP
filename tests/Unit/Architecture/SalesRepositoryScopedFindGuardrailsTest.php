<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use PHPUnit\Framework\TestCase;

class SalesRepositoryScopedFindGuardrailsTest extends TestCase
{
    public function test_sales_repositories_use_scoped_query_for_find_methods(): void
    {
        $repoRoot = dirname(__DIR__, 3);

        $files = [
            'app/Modules/Sales/Infrastructure/Persistence/Eloquent/Repositories/EloquentSalesInvoiceRepository.php',
            'app/Modules/Sales/Infrastructure/Persistence/Eloquent/Repositories/EloquentShipmentRepository.php',
            'app/Modules/Sales/Infrastructure/Persistence/Eloquent/Repositories/EloquentSalesReturnRepository.php',
        ];

        foreach ($files as $relativePath) {
            $absolutePath = $repoRoot.DIRECTORY_SEPARATOR.$relativePath;
            $this->assertFileExists($absolutePath, "Expected source file not found: {$relativePath}");

            $source = file_get_contents($absolutePath);
            $this->assertNotFalse($source, "Unable to read source file: {$relativePath}");

            $this->assertStringContainsString(
                '$this->newScopedQuery()->with(\'lines\')->find(',
                $source,
                "{$relativePath} must use newScopedQuery() for find()."
            );
        }
    }
}
