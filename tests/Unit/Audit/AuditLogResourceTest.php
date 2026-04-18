<?php

declare(strict_types=1);

namespace Tests\Unit\Audit;

use Illuminate\Http\Request;
use Modules\Audit\Domain\Entities\AuditLog;
use Modules\Audit\Domain\ValueObjects\AuditAction;
use Modules\Audit\Infrastructure\Http\Resources\AuditLogResource;
use PHPUnit\Framework\TestCase;

class AuditLogResourceTest extends TestCase
{
    public function test_resource_exposes_expected_contract_keys_and_values(): void
    {
        $resource = new AuditLogResource($this->buildAuditLog());

        $payload = $resource->toArray(Request::create('/api/audit-logs/77', 'GET'));

        $this->assertSame([
            'id',
            'tenant_id',
            'user_id',
            'event',
            'auditable_type',
            'auditable_id',
            'old_values',
            'new_values',
            'diff',
            'url',
            'ip_address',
            'user_agent',
            'tags',
            'metadata',
            'occurred_at',
            'created_at',
        ], array_keys($payload));

        $this->assertSame(77, $payload['id']);
        $this->assertSame(4, $payload['tenant_id']);
        $this->assertSame(9, $payload['user_id']);
        $this->assertSame('updated', $payload['event']);
        $this->assertSame('Modules\\User\\Domain\\Entities\\User', $payload['auditable_type']);
        $this->assertSame('u-42', $payload['auditable_id']);
        $this->assertSame(['name' => 'Before'], $payload['old_values']);
        $this->assertSame(['name' => 'After'], $payload['new_values']);
        $this->assertSame([
            'name' => ['old' => 'Before', 'new' => 'After'],
        ], $payload['diff']);
        $this->assertSame('https://example.test/api/users/u-42', $payload['url']);
        $this->assertSame('127.0.0.1', $payload['ip_address']);
        $this->assertSame('phpunit', $payload['user_agent']);
        $this->assertSame(['resource-test'], $payload['tags']);
        $this->assertSame(['source' => 'unit'], $payload['metadata']);
        $this->assertSame('2025-03-01T11:22:33+00:00', $payload['occurred_at']);
        $this->assertSame('2025-03-01T11:22:33+00:00', $payload['created_at']);
    }

    private function buildAuditLog(): AuditLog
    {
        return new AuditLog(
            id: 77,
            tenantId: 4,
            userId: 9,
            event: AuditAction::fromDatabase('updated'),
            auditableType: 'Modules\\User\\Domain\\Entities\\User',
            auditableId: 'u-42',
            oldValues: ['name' => 'Before'],
            newValues: ['name' => 'After'],
            url: 'https://example.test/api/users/u-42',
            ipAddress: '127.0.0.1',
            userAgent: 'phpunit',
            tags: ['resource-test'],
            metadata: ['source' => 'unit'],
            occurredAt: new \DateTimeImmutable('2025-03-01 11:22:33+00:00'),
        );
    }
}
