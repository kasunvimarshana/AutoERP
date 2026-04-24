<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use PHPUnit\Framework\TestCase;

class AuditTimestampGuardrailsTest extends TestCase
{
    private string $repoRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repoRoot = dirname(__DIR__, 3);
    }

    public function test_audit_model_uses_occurred_at_as_created_timestamp(): void
    {
        $contents = $this->readSource('app/Modules/Audit/Infrastructure/Persistence/Eloquent/Models/AuditLogModel.php');

        $this->assertStringContainsString("public const CREATED_AT = 'occurred_at';", $contents);
        $this->assertStringContainsString("'occurred_at' => 'datetime'", $contents);
    }

    public function test_audit_repository_queries_occurred_at_for_time_based_operations(): void
    {
        $contents = $this->readSource('app/Modules/Audit/Infrastructure/Persistence/Eloquent/Repositories/EloquentAuditRepository.php');

        $this->assertStringContainsString("->orderByDesc('occurred_at')", $contents);
        $this->assertStringContainsString('->where(\'occurred_at\', \'<\', $before)', $contents);
        $this->assertStringNotContainsString("->orderByDesc('created_at')", $contents);
        $this->assertStringNotContainsString("->where('created_at', '<', \$before)", $contents);
    }

    public function test_audit_resource_exposes_occurred_at(): void
    {
        $contents = $this->readSource('app/Modules/Audit/Infrastructure/Http/Resources/AuditLogResource.php');

        $this->assertStringContainsString('\'occurred_at\' => $log->getOccurredAt()->format', $contents);
    }

    private function readSource(string $relativePath): string
    {
        $fullPath = $this->repoRoot.'/'.$relativePath;
        $contents = file_get_contents($fullPath);

        if ($contents === false) {
            $this->fail('Unable to read source file: '.$relativePath);
        }

        return $contents;
    }
}
