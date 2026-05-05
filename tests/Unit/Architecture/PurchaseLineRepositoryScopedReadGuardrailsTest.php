<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use PHPUnit\Framework\TestCase;

class PurchaseLineRepositoryScopedReadGuardrailsTest extends TestCase
{
    public function testPurchaseLineRepositoriesUseScopedQueryForTenantLookups(): void
    {
        $repoRoot = dirname(__DIR__, 3);
        $basePath = 'app/Modules/Purchase/Infrastructure/Persistence/Eloquent/Repositories/';

        $expectations = [
            $basePath . 'EloquentGrnLineRepository.php' => 'findByGrnHeaderId',
            $basePath . 'EloquentPurchaseInvoiceLineRepository.php' => 'findByInvoiceId',
            $basePath . 'EloquentPurchaseOrderLineRepository.php' => 'findByPurchaseOrderId',
            $basePath . 'EloquentPurchaseReturnLineRepository.php' => 'findByPurchaseReturnId',
        ];

        foreach ($expectations as $relativePath => $methodName) {
            $absolutePath = $repoRoot . DIRECTORY_SEPARATOR . $relativePath;
            $this->assertFileExists($absolutePath, "Expected source file not found: {$relativePath}");

            $source = file_get_contents($absolutePath);
            $this->assertNotFalse($source, "Unable to read source file: {$relativePath}");

            $pattern = '/public function ' . preg_quote($methodName, '/') . '\\b[^{]*\\{/';
            $this->assertMatchesRegularExpression(
                $pattern,
                $source,
                "Method {$methodName} not found in {$relativePath}"
            );
            preg_match($pattern, $source, $matches, PREG_OFFSET_CAPTURE);

            $start = $matches[0][1] + strlen($matches[0][0]);
            $depth = 1;
            $index = $start;
            $sourceLength = strlen($source);

            while ($index < $sourceLength && $depth > 0) {
                if ($source[$index] === '{') {
                    $depth++;
                } elseif ($source[$index] === '}') {
                    $depth--;
                }

                $index++;
            }

            $methodBody = substr($source, $start, $index - $start - 1);

            $this->assertStringContainsString(
                '$this->newScopedQuery()',
                $methodBody,
                "{$relativePath}::{$methodName} must use newScopedQuery()."
            );

            $this->assertStringContainsString(
                'where(\'tenant_id\', $tenantId)',
                $methodBody,
                "{$relativePath}::{$methodName} must filter by tenant_id argument."
            );
        }
    }
}
