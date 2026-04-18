<?php

declare(strict_types=1);

namespace Tests\Unit\Audit;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\GenericUser;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Modules\Audit\Application\Contracts\AuditServiceInterface;
use Modules\Audit\Domain\Entities\AuditLog;
use Modules\Audit\Domain\ValueObjects\AuditAction;
use Modules\Audit\Infrastructure\Http\Controllers\AuditLogController;
use Modules\Audit\Infrastructure\Http\Requests\ListAuditLogRequest;
use Modules\Audit\Infrastructure\Http\Resources\AuditLogResource;
use Modules\Auth\Application\Contracts\AuthorizationServiceInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

class AuditLogControllerTest extends TestCase
{
    /** @var AuditServiceInterface&MockObject */
    private AuditServiceInterface $auditService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->auditService = $this->createMock(AuditServiceInterface::class);

        $authorizationService = $this->createMock(AuthorizationServiceInterface::class);
        $authorizationService
            ->method('can')
            ->willReturn(true);

        $this->app->instance(AuthorizationServiceInterface::class, $authorizationService);

        Auth::setUser(new GenericUser(['id' => 101]));
    }

    public function test_index_passes_normalized_filters_and_returns_json_response(): void
    {
        $entity = $this->buildAuditLog(id: 7);
        $paginator = new LengthAwarePaginator(
            items: [$entity],
            total: 1,
            perPage: 20,
            currentPage: 2,
        );

        $this->auditService
            ->expects($this->once())
            ->method('list')
            ->with(
                [
                    'tenant_id' => 3,
                    'event' => 'updated',
                    'auditable_type' => 'Modules\\User\\Domain\\Entities\\User',
                ],
                20,
                2,
                '-user_id'
            )
            ->willReturn($paginator);

        $request = new class extends ListAuditLogRequest
        {
            /** @var array<string, mixed> */
            public array $payload = [];

            public function validated($key = null, $default = null): array
            {
                return $this->payload;
            }
        };

        $request->payload = [
            'tenant_id' => 3,
            'user_id' => null,
            'event' => 'updated',
            'auditable_type' => 'Modules\\User\\Domain\\Entities\\User',
            'auditable_id' => '',
            'per_page' => 20,
            'page' => 2,
            'sort' => '-user_id',
        ];

        $controller = new AuditLogController($this->auditService);
        $response = $controller->index($request);

        $this->assertSame(200, $response->getStatusCode());

        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertArrayHasKey('data', $payload);
        $this->assertCount(1, $payload['data']);
        $this->assertSame(7, $payload['data'][0]['id']);
        $this->assertArrayHasKey('occurred_at', $payload['data'][0]);
    }

    public function test_show_throws_not_found_when_log_does_not_exist(): void
    {
        $this->auditService
            ->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        $controller = new AuditLogController($this->auditService);

        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Audit log not found.');

        $controller->show(999);
    }

    public function test_show_returns_resource_when_log_exists(): void
    {
        $entity = $this->buildAuditLog(id: 8);

        $this->auditService
            ->expects($this->once())
            ->method('find')
            ->with(8)
            ->willReturn($entity);

        $controller = new AuditLogController($this->auditService);
        $resource = $controller->show(8);

        $this->assertInstanceOf(AuditLogResource::class, $resource);

        $payload = $resource->toArray(Request::create('/api/audit-logs/8', 'GET'));

        $this->assertSame(8, $payload['id']);
        $this->assertSame('updated', $payload['event']);
        $this->assertArrayHasKey('occurred_at', $payload);
    }

    public function test_index_throws_authorization_exception_when_user_cannot_view_audit_logs(): void
    {
        $authorizationService = $this->createMock(AuthorizationServiceInterface::class);
        $authorizationService
            ->method('can')
            ->willReturn(false);

        $this->app->instance(AuthorizationServiceInterface::class, $authorizationService);

        $request = new class extends ListAuditLogRequest
        {
            /** @var array<string, mixed> */
            public array $payload = [];

            public function validated($key = null, $default = null): array
            {
                return $this->payload;
            }
        };

        $request->payload = [];

        $this->auditService
            ->expects($this->never())
            ->method('list');

        $controller = new AuditLogController($this->auditService);

        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('This action is unauthorized.');

        $controller->index($request);
    }

    private function buildAuditLog(int $id): AuditLog
    {
        return new AuditLog(
            id: $id,
            tenantId: 3,
            userId: 11,
            event: AuditAction::fromDatabase('updated'),
            auditableType: 'Modules\\User\\Domain\\Entities\\User',
            auditableId: '42',
            oldValues: ['name' => 'Before'],
            newValues: ['name' => 'After'],
            url: 'https://example.test/api/users/42',
            ipAddress: '127.0.0.1',
            userAgent: 'phpunit',
            tags: ['controller-test'],
            metadata: ['source' => 'unit'],
            occurredAt: new \DateTimeImmutable('2025-02-01 12:30:00'),
        );
    }
}
