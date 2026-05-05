<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use PHPUnit\Framework\TestCase;

class ReceiptRepositoryTenantIsolationGuardrailsTest extends TestCase
{
    public function testReceiptRepositoryScopesIdBasedOperationsToCurrentTenant(): void
    {
        $repoRoot = dirname(__DIR__, 3);
        $relativePath =
            'app/Modules/Receipts/Infrastructure/Persistence/Eloquent/Repositories/EloquentReceiptRepository.php';
        $absolutePath = $repoRoot . DIRECTORY_SEPARATOR . $relativePath;

        $this->assertFileExists($absolutePath, "Expected source file not found: {$relativePath}");

        $source = file_get_contents($absolutePath);
        $this->assertNotFalse($source, "Unable to read source file: {$relativePath}");

        $this->assertStringContainsString('private function tenantScopedQuery(): Builder', $source);
        $this->assertStringContainsString('private function resolveCurrentTenantId(): ?string', $source);

        $this->assertStringContainsString(
            '$this->tenantScopedQuery()->where(\'id\', $id)->first();',
            $source,
            'findById must use tenantScopedQuery().'
        );

        $this->assertStringContainsString(
            '$this->tenantScopedQuery()->where(\'id\', $id)->firstOrFail();',
            $source,
            'updateStatus must use tenantScopedQuery().'
        );

        $this->assertStringContainsString(
            '$this->tenantScopedQuery()->where(\'id\', $id)->delete();',
            $source,
            'delete must use tenantScopedQuery().'
        );

        $this->assertStringNotContainsString(
            'withoutGlobalScope(\'tenant\')->find($id)',
            $source,
            'Direct unscoped find by id is forbidden.'
        );
    }
}
