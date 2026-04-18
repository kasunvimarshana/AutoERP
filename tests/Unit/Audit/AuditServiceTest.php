<?php

declare(strict_types=1);

namespace Tests\Unit\Audit;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Audit\Application\Services\AuditService;
use Modules\Audit\Domain\Entities\AuditLog;
use Modules\Audit\Domain\RepositoryInterfaces\AuditRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AuditServiceTest extends TestCase
{
    /** @var AuditRepositoryInterface&MockObject */
    private AuditRepositoryInterface $repository;

    private AuditService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->createMock(AuditRepositoryInterface::class);
        $this->service = new AuditService($this->repository);
    }

    public function test_list_passes_only_allowed_non_empty_filters_and_default_sort(): void
    {
        $paginator = $this->createMock(LengthAwarePaginator::class);

        $this->repository
            ->expects($this->once())
            ->method('list')
            ->with(
                [
                    'tenant_id' => 10,
                    'event' => 'updated',
                    'auditable_type' => 'Modules\\User\\Domain\\Entities\\User',
                ],
                25,
                2,
                'occurred_at',
                'desc'
            )
            ->willReturn($paginator);

        $result = $this->service->list([
            'tenant_id' => 10,
            'event' => 'updated',
            'auditable_type' => 'Modules\\User\\Domain\\Entities\\User',
            'auditable_id' => '',
            'unexpected' => 'ignored',
            'user_id' => null,
        ], 25, 2, null);

        $this->assertSame($paginator, $result);
    }

    public function test_list_supports_desc_sort_prefix(): void
    {
        $paginator = $this->createMock(LengthAwarePaginator::class);

        $this->repository
            ->expects($this->once())
            ->method('list')
            ->with([], 15, 1, 'user_id', 'desc')
            ->willReturn($paginator);

        $result = $this->service->list([], 15, 1, '-user_id');

        $this->assertSame($paginator, $result);
    }

    public function test_list_falls_back_to_default_sort_for_unsupported_field(): void
    {
        $paginator = $this->createMock(LengthAwarePaginator::class);

        $this->repository
            ->expects($this->once())
            ->method('list')
            ->with([], 15, 1, 'occurred_at', 'desc')
            ->willReturn($paginator);

        $result = $this->service->list([], 15, 1, '-not_allowed');

        $this->assertSame($paginator, $result);
    }

    public function test_record_maps_payload_into_audit_entity(): void
    {
        $captured = null;

        $this->repository
            ->expects($this->once())
            ->method('record')
            ->willReturnCallback(function (AuditLog $log) use (&$captured): AuditLog {
                $captured = $log;

                return $log;
            });

        $result = $this->service->record([
            'tenant_id' => 5,
            'user_id' => 17,
            'event' => 'updated',
            'auditable_type' => 'Modules\\User\\Domain\\Entities\\User',
            'auditable_id' => 'abc-123',
            'old_values' => ['name' => 'before'],
            'new_values' => ['name' => 'after'],
            'url' => 'https://example.test/api/users/abc-123',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'phpunit',
            'tags' => ['unit-test'],
            'metadata' => ['source' => 'test'],
        ]);

        $this->assertInstanceOf(AuditLog::class, $captured);
        $this->assertInstanceOf(AuditLog::class, $result);
        $this->assertSame(5, $captured->getTenantId());
        $this->assertSame(17, $captured->getUserId());
        $this->assertSame('updated', $captured->getEvent()->value());
        $this->assertSame('Modules\\User\\Domain\\Entities\\User', $captured->getAuditableType());
        $this->assertSame('abc-123', $captured->getAuditableId());
        $this->assertSame(['name' => 'before'], $captured->getOldValues());
        $this->assertSame(['name' => 'after'], $captured->getNewValues());
        $this->assertSame('https://example.test/api/users/abc-123', $captured->getUrl());
        $this->assertSame('127.0.0.1', $captured->getIpAddress());
        $this->assertSame('phpunit', $captured->getUserAgent());
        $this->assertSame(['unit-test'], $captured->getTags());
        $this->assertSame(['source' => 'test'], $captured->getMetadata());
        $this->assertInstanceOf(\DateTimeInterface::class, $captured->getOccurredAt());
    }
}
