<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Pagination\LengthAwarePaginator;
use Laravel\Passport\Passport;
use Modules\Audit\Application\Contracts\AuditServiceInterface;
use Modules\Audit\Domain\Entities\AuditLog;
use Modules\Audit\Domain\ValueObjects\AuditAction;
use Modules\Auth\Application\Contracts\AuthorizationServiceInterface;
use Modules\Tenant\Application\Contracts\TenantConfigClientInterface;
use Modules\Tenant\Application\Contracts\TenantConfigManagerInterface;
use Modules\User\Infrastructure\Persistence\Eloquent\Models\UserModel;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Tests\TestCase;

class AuditEndpointsAuthenticatedTest extends TestCase
{
    /** @var AuditServiceInterface&MockObject */
    private AuditServiceInterface $auditService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->auditService = $this->createMock(AuditServiceInterface::class);
        $this->app->instance(AuditServiceInterface::class, $this->auditService);

        $authorizationService = $this->createMock(AuthorizationServiceInterface::class);
        $authorizationService
            ->method('can')
            ->willReturn(true);
        $this->app->instance(AuthorizationServiceInterface::class, $authorizationService);

        $tenantConfigClient = $this->createMock(TenantConfigClientInterface::class);
        $tenantConfigClient
            ->method('getConfig')
            ->willReturn(null);
        $this->app->instance(TenantConfigClientInterface::class, $tenantConfigClient);

        $tenantConfigManager = $this->createMock(TenantConfigManagerInterface::class);
        $this->app->instance(TenantConfigManagerInterface::class, $tenantConfigManager);

        $user = new UserModel([
            'id' => 101,
            'tenant_id' => 3,
            'email' => 'audit.test@example.com',
            'password' => 'secret',
            'first_name' => 'Audit',
            'last_name' => 'Tester',
        ]);
        $user->setAttribute('id', 101);
        $user->setAttribute('tenant_id', 3);

        $this->actingAs($user, 'api');
    }

    public function test_authenticated_index_returns_success_payload(): void
    {
        $paginator = new LengthAwarePaginator(
            items: [$this->buildAuditLog(id: 31)],
            total: 1,
            perPage: 15,
            currentPage: 1,
        );

        $this->auditService
            ->expects($this->once())
            ->method('list')
            ->with(
                [
                    'tenant_id' => 3,
                    'event' => 'updated',
                ],
                15,
                1,
                '-occurred_at'
            )
            ->willReturn($paginator);

        $response = $this->withHeader('X-Tenant-ID', '3')
            ->getJson('/api/audit-logs?tenant_id=3&event=updated&sort=-occurred_at');

        $response->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.0.id', 31)
            ->assertJsonPath('data.0.event', 'updated')
            ->assertJsonStructure([
                'data' => [[
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
                ]],
            ]);
    }

    public function test_authenticated_show_returns_success_payload(): void
    {
        $this->auditService
            ->expects($this->once())
            ->method('find')
            ->with(32)
            ->willReturn($this->buildAuditLog(id: 32));

        $response = $this->withHeader('X-Tenant-ID', '3')
            ->getJson('/api/audit-logs/32');

        $response->assertStatus(HttpResponse::HTTP_OK)
            ->assertJsonPath('data.id', 32)
            ->assertJsonPath('data.event', 'updated')
            ->assertJsonPath('data.auditable_id', '42')
            ->assertJsonStructure([
                'data' => [
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
                ],
            ]);
    }

    public function test_authenticated_index_returns_forbidden_when_authorization_fails(): void
    {
        $authorizationService = $this->createMock(AuthorizationServiceInterface::class);
        $authorizationService
            ->method('can')
            ->willReturn(false);
        $this->app->instance(AuthorizationServiceInterface::class, $authorizationService);

        $this->auditService
            ->expects($this->never())
            ->method('list');

        $response = $this->withHeader('X-Tenant-ID', '3')
            ->getJson('/api/audit-logs');

        $response->assertStatus(HttpResponse::HTTP_FORBIDDEN)
            ->assertJsonPath('message', 'This action is unauthorized.');
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
            tags: ['feature-test'],
            metadata: ['source' => 'feature'],
            occurredAt: new \DateTimeImmutable('2025-02-01 12:30:00+00:00'),
        );
    }
}
