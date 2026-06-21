<?php

declare(strict_types=1);

namespace Modules\Audit\Tests;

use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Modules\Audit\Data\AuditQueryData;
use Modules\Audit\Data\AuditReadScope;
use Modules\Audit\Models\AuditLog;
use Modules\Audit\Repositories\EloquentAuditLogReader;
use Tests\TestCase;

final class AuditPersistenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_id_reads_are_scoped_in_the_repository_query(): void
    {
        $visible = $this->auditLog(tenantId: 10, organizationUnitId: 100, subjectId: 'visible');
        $otherOrganization = $this->auditLog(tenantId: 10, organizationUnitId: 200, subjectId: 'other-org');
        $otherTenant = $this->auditLog(tenantId: 20, organizationUnitId: 100, subjectId: 'other-tenant');
        $reader = new EloquentAuditLogReader(new AuditLog());
        $scope = new AuditReadScope(10, 100, false);

        self::assertSame($visible->getKey(), $reader->findVisibleById($scope, (int) $visible->getKey())?->get('id'));
        self::assertNull($reader->findVisibleById($scope, (int) $otherOrganization->getKey()));
        self::assertNull($reader->findVisibleById($scope, (int) $otherTenant->getKey()));
    }

    public function test_tenant_wide_cursor_reads_remain_deterministic_and_tenant_scoped(): void
    {
        $first = $this->auditLog(10, 100, 'first', '2026-06-22 10:00:00');
        $second = $this->auditLog(10, 200, 'second', '2026-06-22 10:00:00');
        $this->auditLog(20, 100, 'other-tenant', '2026-06-22 11:00:00');
        $reader = new EloquentAuditLogReader(new AuditLog());
        $query = new AuditQueryData(null, null, null, null, null, null, null, null, null, 1, null);

        $page = $reader->cursorPage(new AuditReadScope(10, null, true), $query);

        self::assertCount(1, $page->items);
        self::assertSame($second->getKey(), $page->items[0]->get('id'));
        self::assertNotNull($page->nextCursor);

        $next = $reader->cursorPage(
            new AuditReadScope(10, null, true),
            new AuditQueryData(null, null, null, null, null, null, null, null, null, 1, $page->nextCursor),
        );

        self::assertCount(1, $next->items);
        self::assertSame($first->getKey(), $next->items[0]->get('id'));
    }

    public function test_model_instances_reject_updates_and_deletes(): void
    {
        $log = $this->auditLog(10, 100, 'immutable');
        $log->event_name = 'audit.changed';

        try {
            $log->save();
            self::fail('Expected audit update to be rejected.');
        } catch (LogicException $exception) {
            self::assertSame('Audit logs are immutable.', $exception->getMessage());
        }

        $log->refresh();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Audit logs cannot be deleted');

        $log->delete();
    }

    private function auditLog(
        int $tenantId,
        ?int $organizationUnitId,
        string $subjectId,
        string $occurredAt = '2026-06-22 09:00:00',
    ): AuditLog {
        return AuditLog::query()->create([
            'event_uuid' => sprintf('00000000-0000-4000-8000-%012d', AuditLog::query()->count() + 1),
            'tenant_id' => $tenantId,
            'tenant_name' => 'Tenant '.$tenantId,
            'organization_unit_id' => $organizationUnitId,
            'organization_unit_name' => $organizationUnitId !== null ? 'Organization '.$organizationUnitId : null,
            'event_category' => 'workflow',
            'event_name' => 'audit.test.created',
            'actor_type' => 'system',
            'actor_id' => 'test-suite',
            'actor_name' => 'Test Suite',
            'subject_type' => 'test_record',
            'subject_id' => $subjectId,
            'source_module' => 'audit',
            'changes' => null,
            'metadata' => null,
            'tags' => ['test'],
            'occurred_at' => new DateTimeImmutable($occurredAt),
            'recorded_at' => new DateTimeImmutable($occurredAt),
        ]);
    }
}
