<?php
declare(strict_types=1);
namespace Tests\Unit;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Modules\Audit\Application\Services\QueryAuditLogService;
use Modules\Audit\Application\Services\RecordAuditLogService;
use Modules\Audit\Domain\Entities\AuditLog;
use Modules\Audit\Domain\Exceptions\AuditLogNotFoundException;
use Modules\Audit\Domain\RepositoryInterfaces\AuditLogRepositoryInterface;

class AuditModuleTest extends TestCase
{
    // ──────────────────────────────────────────────────────────────────────
    // Factory helpers
    // ──────────────────────────────────────────────────────────────────────

    private function makeAuditLog(
        int $id = 1,
        string $event = 'created',
        ?array $old = null,
        ?array $new = null
    ): AuditLog {
        return new AuditLog(
            $id, 1, 2,
            $event, 'Product', '42',
            $old, $new,
            '127.0.0.1', 'Mozilla/5.0', '/api/products/42',
            new \DateTimeImmutable('2026-01-01 12:00:00'),
        );
    }

    private function mockRepo(): MockObject&AuditLogRepositoryInterface
    {
        return $this->createMock(AuditLogRepositoryInterface::class);
    }

    // ──────────────────────────────────────────────────────────────────────
    // AuditLog entity tests
    // ──────────────────────────────────────────────────────────────────────

    public function test_audit_log_creation(): void
    {
        $log = $this->makeAuditLog(1, 'created');
        $this->assertEquals(1, $log->getId());
        $this->assertEquals(1, $log->getTenantId());
        $this->assertEquals(2, $log->getUserId());
        $this->assertEquals('created', $log->getEvent());
        $this->assertEquals('Product', $log->getEntityType());
        $this->assertEquals('42', $log->getEntityId());
        $this->assertEquals('127.0.0.1', $log->getIpAddress());
        $this->assertNotNull($log->getCreatedAt());
    }

    public function test_audit_log_diff_returns_changed_fields(): void
    {
        $log = $this->makeAuditLog(
            old: ['name' => 'Old Name', 'price' => 10.0],
            new: ['name' => 'New Name', 'price' => 10.0],
        );
        $diff = $log->getDiff();
        $this->assertArrayHasKey('name', $diff);
        $this->assertArrayNotHasKey('price', $diff);
        $this->assertEquals('Old Name', $diff['name']['old']);
        $this->assertEquals('New Name', $diff['name']['new']);
    }

    public function test_audit_log_diff_empty_when_no_old_values(): void
    {
        $log = $this->makeAuditLog(1, 'created', null, ['name' => 'Product']);
        $this->assertEmpty($log->getDiff());
    }

    public function test_audit_log_diff_all_changed(): void
    {
        $log = $this->makeAuditLog(
            old: ['a' => 1, 'b' => 2],
            new: ['a' => 9, 'b' => 8],
        );
        $diff = $log->getDiff();
        $this->assertCount(2, $diff);
    }

    public function test_audit_log_url_stored(): void
    {
        $log = $this->makeAuditLog();
        $this->assertEquals('/api/products/42', $log->getUrl());
    }

    public function test_audit_log_null_user_allowed(): void
    {
        $log = new AuditLog(1, 1, null, 'login', 'Session', null, null, null, null, null, null, new \DateTimeImmutable());
        $this->assertNull($log->getUserId());
    }

    // ──────────────────────────────────────────────────────────────────────
    // RecordAuditLogService tests
    // ──────────────────────────────────────────────────────────────────────

    public function test_record_service_creates_log(): void
    {
        $repo = $this->mockRepo();
        $log  = $this->makeAuditLog(1, 'created');
        $repo->expects($this->once())
            ->method('create')
            ->willReturn($log);

        $service = new RecordAuditLogService($repo);
        $result  = $service->record(1, 2, 'created', 'Product', '42');
        $this->assertEquals('created', $result->getEvent());
    }

    public function test_record_service_passes_all_fields(): void
    {
        $repo = $this->mockRepo();
        $log  = $this->makeAuditLog(1, 'updated', ['name' => 'Old'], ['name' => 'New']);
        $repo->method('create')->willReturnCallback(function ($data) use ($log) {
            $this->assertEquals('updated', $data['event']);
            $this->assertEquals(['name' => 'Old'], $data['old_values']);
            $this->assertEquals(['name' => 'New'], $data['new_values']);
            $this->assertEquals('192.168.1.1', $data['ip_address']);
            return $log;
        });

        $service = new RecordAuditLogService($repo);
        $service->record(
            tenantId:   1,
            userId:     2,
            event:      'updated',
            entityType: 'Product',
            entityId:   '42',
            oldValues:  ['name' => 'Old'],
            newValues:  ['name' => 'New'],
            ipAddress:  '192.168.1.1',
        );
    }

    // ──────────────────────────────────────────────────────────────────────
    // QueryAuditLogService tests
    // ──────────────────────────────────────────────────────────────────────

    public function test_query_service_find_by_id(): void
    {
        $repo = $this->mockRepo();
        $log  = $this->makeAuditLog(7);
        $repo->method('findById')->with(7)->willReturn($log);

        $result = (new QueryAuditLogService($repo))->findById(7);
        $this->assertEquals(7, $result->getId());
    }

    public function test_query_service_find_not_found_throws(): void
    {
        $repo = $this->mockRepo();
        $repo->method('findById')->willReturn(null);

        $this->expectException(AuditLogNotFoundException::class);
        (new QueryAuditLogService($repo))->findById(999);
    }

    public function test_query_service_find_by_tenant(): void
    {
        $repo = $this->mockRepo();
        $repo->method('findByTenant')->willReturn([
            'data'  => [$this->makeAuditLog(1), $this->makeAuditLog(2)],
            'total' => 2,
        ]);

        $result = (new QueryAuditLogService($repo))->findByTenant(1);
        $this->assertCount(2, $result['data']);
        $this->assertEquals(2, $result['total']);
    }

    public function test_query_service_find_by_entity(): void
    {
        $repo = $this->mockRepo();
        $repo->method('findByEntity')->willReturn([$this->makeAuditLog(1), $this->makeAuditLog(2)]);

        $result = (new QueryAuditLogService($repo))->findByEntity(1, 'Product', '42');
        $this->assertCount(2, $result);
    }

    public function test_query_service_purge_older_than(): void
    {
        $repo = $this->mockRepo();
        $repo->expects($this->once())
            ->method('deleteOlderThan')
            ->willReturn(15);

        $count = (new QueryAuditLogService($repo))->purgeOlderThan(90);
        $this->assertEquals(15, $count);
    }

    public function test_audit_log_not_found_exception_message(): void
    {
        $ex = new AuditLogNotFoundException(55);
        $this->assertStringContainsString('55', $ex->getMessage());
    }
}
