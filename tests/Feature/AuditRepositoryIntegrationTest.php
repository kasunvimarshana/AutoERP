<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Audit\Domain\Entities\AuditLog;
use Modules\Audit\Domain\RepositoryInterfaces\AuditRepositoryInterface;
use Tests\TestCase;

class AuditRepositoryIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_list_applies_filters_and_sorts_by_occurred_at_desc(): void
    {
        $this->seedAuditLogRows();

        /** @var AuditRepositoryInterface $repository */
        $repository = app(AuditRepositoryInterface::class);

        $paginator = $repository->list(
            filters: [
                'tenant_id' => 1,
                'event' => 'updated',
            ],
            perPage: 15,
            page: 1,
            sortField: 'occurred_at',
            sortDirection: 'desc',
        );

        $items = $paginator->items();

        $this->assertCount(2, $items);
        $this->assertInstanceOf(AuditLog::class, $items[0]);
        $this->assertSame(3, $items[0]->getId());
        $this->assertSame(2, $items[1]->getId());
    }

    public function test_for_auditable_returns_entries_in_descending_occurred_at_order(): void
    {
        $this->seedAuditLogRows();

        /** @var AuditRepositoryInterface $repository */
        $repository = app(AuditRepositoryInterface::class);

        $logs = $repository->forAuditable('Modules\\User\\Domain\\Entities\\User', '42');

        $this->assertCount(2, $logs);
        $this->assertSame([3, 1], $logs->map(static fn (AuditLog $log): ?int => $log->getId())->all());
    }

    public function test_prune_older_than_uses_occurred_at_column(): void
    {
        $this->seedAuditLogRows();

        /** @var AuditRepositoryInterface $repository */
        $repository = app(AuditRepositoryInterface::class);

        $deleted = $repository->pruneOlderThan(new \DateTimeImmutable('2025-01-15 00:00:00'));

        $this->assertSame(2, $deleted);
        $this->assertSame(1, DB::table('audit_logs')->count());
        $this->assertSame([3], DB::table('audit_logs')->pluck('id')->all());
    }

    private function seedAuditLogRows(): void
    {
        DB::table('audit_logs')->insert([
            [
                'id' => 1,
                'tenant_id' => 1,
                'user_id' => 10,
                'event' => 'created',
                'auditable_type' => 'Modules\\User\\Domain\\Entities\\User',
                'auditable_id' => '42',
                'old_values' => null,
                'new_values' => json_encode(['name' => 'Alice'], JSON_THROW_ON_ERROR),
                'url' => 'https://example.test/api/users/42',
                'ip_address' => '127.0.0.1',
                'user_agent' => 'phpunit',
                'tags' => json_encode(['seed'], JSON_THROW_ON_ERROR),
                'metadata' => json_encode(['source' => 'integration-test'], JSON_THROW_ON_ERROR),
                'occurred_at' => '2025-01-01 10:00:00',
            ],
            [
                'id' => 2,
                'tenant_id' => 1,
                'user_id' => 11,
                'event' => 'updated',
                'auditable_type' => 'Modules\\Tenant\\Domain\\Entities\\Tenant',
                'auditable_id' => '5',
                'old_values' => json_encode(['name' => 'Old Tenant'], JSON_THROW_ON_ERROR),
                'new_values' => json_encode(['name' => 'New Tenant'], JSON_THROW_ON_ERROR),
                'url' => 'https://example.test/api/tenants/5',
                'ip_address' => '127.0.0.1',
                'user_agent' => 'phpunit',
                'tags' => json_encode(['seed'], JSON_THROW_ON_ERROR),
                'metadata' => json_encode(['source' => 'integration-test'], JSON_THROW_ON_ERROR),
                'occurred_at' => '2025-01-10 09:00:00',
            ],
            [
                'id' => 3,
                'tenant_id' => 1,
                'user_id' => 12,
                'event' => 'updated',
                'auditable_type' => 'Modules\\User\\Domain\\Entities\\User',
                'auditable_id' => '42',
                'old_values' => json_encode(['name' => 'Alice'], JSON_THROW_ON_ERROR),
                'new_values' => json_encode(['name' => 'Alice A.'], JSON_THROW_ON_ERROR),
                'url' => 'https://example.test/api/users/42',
                'ip_address' => '127.0.0.1',
                'user_agent' => 'phpunit',
                'tags' => json_encode(['seed'], JSON_THROW_ON_ERROR),
                'metadata' => json_encode(['source' => 'integration-test'], JSON_THROW_ON_ERROR),
                'occurred_at' => '2025-02-01 12:30:00',
            ],
        ]);
    }
}
