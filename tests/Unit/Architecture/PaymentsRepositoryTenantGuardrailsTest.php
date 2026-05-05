<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use PHPUnit\Framework\TestCase;

class PaymentsRepositoryTenantGuardrailsTest extends TestCase
{
    private string $repoPath;

    protected function setUp(): void
    {
        parent::setUp();

        $repoRoot = dirname(__DIR__, 3);
        $relativePath =
            'app/Modules/Payments/Infrastructure/Persistence/Eloquent/Repositories/EloquentPaymentRepository.php';

        $this->repoPath = $repoRoot . DIRECTORY_SEPARATOR . $relativePath;
    }

    public function testPaymentRepositoryFileExists(): void
    {
        $this->assertFileExists($this->repoPath, 'EloquentPaymentRepository source file not found.');
    }

    public function testFindByIdRequiresTenantIdParameter(): void
    {
        $source = $this->readSource();

        $this->assertStringContainsString(
            'public function findById(string $tenantId, string $id)',
            $source,
            'findById must declare a $tenantId parameter.'
        );
    }

    public function testFindByIdScopesQueryToTenant(): void
    {
        $source = $this->readSource();

        $this->assertStringContainsString(
            "->where('tenant_id', \$tenantId)",
            $source,
            'findById must filter by tenant_id.'
        );
    }

    public function testUpdateStatusRequiresTenantIdParameter(): void
    {
        $source = $this->readSource();

        $this->assertStringContainsString(
            'public function updateStatus(string $tenantId, string $id, string $status)',
            $source,
            'updateStatus must declare a $tenantId parameter.'
        );
    }

    public function testDeleteRequiresTenantIdParameter(): void
    {
        $source = $this->readSource();

        $this->assertStringContainsString(
            'public function delete(string $tenantId, string $id)',
            $source,
            'delete must declare a $tenantId parameter.'
        );
    }

    public function testDeleteScopesQueryToTenant(): void
    {
        $source = $this->readSource();

        // Verify delete method itself scopes to tenant (not just the other methods)
        $deleteBlock = $this->extractMethodBody($source, 'delete');
        $this->assertStringContainsString(
            "->where('tenant_id', \$tenantId)",
            $deleteBlock,
            'delete must filter by tenant_id.'
        );
        $this->assertStringContainsString(
            "->where('id', \$id)",
            $deleteBlock,
            'delete must filter by id.'
        );
    }

    public function testNoUnscopedFindByIdPattern(): void
    {
        $source = $this->readSource();

        $this->assertStringNotContainsString(
            '->find($id)',
            $source,
            'Direct unscoped ->find($id) is forbidden; use where(tenant_id) + where(id) pattern.'
        );

        $this->assertStringNotContainsString(
            '->findOrFail($id)',
            $source,
            'Direct unscoped ->findOrFail($id) is forbidden; use where(tenant_id) + where(id) pattern.'
        );
    }

    public function testIdBasedOperationsUseWithoutGlobalScopeWithExplicitTenantFilter(): void
    {
        $source = $this->readSource();

        // Count how many times withoutGlobalScope('tenant') appears
        $occurrences = substr_count($source, "withoutGlobalScope('tenant')");

        // findById, findByTenant, findByInvoice, save, updateStatus, delete all use it
        $this->assertGreaterThanOrEqual(
            5,
            $occurrences,
            "Expected at least 5 uses of withoutGlobalScope('tenant') — one per public query method."
        );
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function readSource(): string
    {
        $this->assertFileExists($this->repoPath);
        $source = file_get_contents($this->repoPath);
        $this->assertNotFalse($source, 'Unable to read EloquentPaymentRepository source.');

        return $source;
    }

    /**
     * Rough extraction of a method body by name — grabs text from `function $name` until the
     * next top-level closing brace at depth 0.
     */
    private function extractMethodBody(string $source, string $methodName): string
    {
        $start = strpos($source, 'function ' . $methodName . '(');
        if ($start === false) {
            return '';
        }

        $depth  = 0;
        $length = strlen($source);
        $body   = '';
        $inside = false;

        for ($i = $start; $i < $length; $i++) {
            $ch = $source[$i];
            $body .= $ch;

            if ($ch === '{') {
                $depth++;
                $inside = true;
            } elseif ($ch === '}') {
                $depth--;
                if ($inside && $depth === 0) {
                    break;
                }
            }
        }

        return $body;
    }
}
