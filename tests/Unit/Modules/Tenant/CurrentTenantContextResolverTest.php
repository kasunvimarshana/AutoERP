<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Tenant;

use Illuminate\Auth\GenericUser;
use Illuminate\Http\Request;
use Modules\Core\Application\Contracts\CurrentTenantContextAccessorInterface;
use Modules\Core\Application\Contracts\CurrentUserContextAccessorInterface;
use Modules\Core\Application\DTO\CurrentTenantContext;
use Modules\Core\Application\DTO\CurrentUserContext;
use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\Exceptions\CurrentTenantContextResolutionException;
use Modules\Tenant\Application\Repositories\TenantDomainRepositoryInterface;
use Modules\Tenant\Application\Repositories\TenantRepositoryInterface;
use Modules\Tenant\Infrastructure\Services\CurrentTenantContextResolver;
use Modules\User\Application\Repositories\UserTenantRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class CurrentTenantContextResolverTest extends TestCase
{
    public function testItResolvesTenantFromExplicitTenantCodeMetadata(): void
    {
        $currentUser = $this->createMock(CurrentUserContextAccessorInterface::class);
        $currentTenant = $this->createMock(CurrentTenantContextAccessorInterface::class);
        $tenants = $this->createMock(TenantRepositoryInterface::class);
        $tenantDomains = $this->createMock(TenantDomainRepositoryInterface::class);
        $userTenants = $this->createMock(UserTenantRepositoryInterface::class);

        $currentUser->method('currentApplicationId')->willReturn('erp-web');
        $currentUser->method('currentTenantId')->willReturn(null);
        $currentTenant->method('currentApplicationId')->willReturn(null);

        $tenants->expects(self::once())
            ->method('findByCode')
            ->with('TENANT-11')
            ->willReturn($this->tenantRecord(11));

        $tenantDomains->expects(self::once())
            ->method('findPrimaryByTenant')
            ->with(11)
            ->willReturn(new DataRecord(['tenant_id' => 11, 'domain' => 'tenant11.example.com']));

        $resolver = new CurrentTenantContextResolver(
            $currentUser,
            $currentTenant,
            $tenants,
            $tenantDomains,
            $userTenants,
        );

        $request = Request::create('/api/example', 'GET', ['tenant_code' => 'TENANT-11']);

        $context = $resolver->resolve($request);

        self::assertInstanceOf(CurrentTenantContext::class, $context);
        self::assertSame(11, $context->tenantId());
        self::assertSame('TENANT-11', $context->tenantCode());
        self::assertSame('tenant11.example.com', $context->domain());
        self::assertSame('erp-web', $context->applicationId());
        self::assertSame('request_metadata', $context->source());
    }

    public function testItFallsBackToAuthenticatedTenantContext(): void
    {
        $currentUser = $this->createMock(CurrentUserContextAccessorInterface::class);
        $currentTenant = $this->createMock(CurrentTenantContextAccessorInterface::class);
        $tenants = $this->createMock(TenantRepositoryInterface::class);
        $tenantDomains = $this->createMock(TenantDomainRepositoryInterface::class);
        $userTenants = $this->createMock(UserTenantRepositoryInterface::class);

        $currentUser->method('currentApplicationId')->willReturn('erp-web');
        $currentUser->method('currentTenantId')->willReturn(7);
        $currentTenant->method('currentApplicationId')->willReturn(null);

        $tenants->expects(self::once())
            ->method('findById')
            ->with(7)
            ->willReturn($this->tenantRecord(7));

        $tenantDomains->expects(self::once())
            ->method('findPrimaryByTenant')
            ->with(7)
            ->willReturn(new DataRecord(['tenant_id' => 7, 'domain' => 'tenant7.example.com']));

        $resolver = new CurrentTenantContextResolver(
            $currentUser,
            $currentTenant,
            $tenants,
            $tenantDomains,
            $userTenants,
        );

        $request = Request::create('/api/example', 'GET');

        $context = $resolver->resolve($request);

        self::assertInstanceOf(CurrentTenantContext::class, $context);
        self::assertSame(7, $context->tenantId());
        self::assertSame('authenticated_user', $context->source());
    }

    public function testItRejectsExplicitTenantWhenAuthenticatedUserHasNoAccess(): void
    {
        $currentUser = $this->createMock(CurrentUserContextAccessorInterface::class);
        $currentTenant = $this->createMock(CurrentTenantContextAccessorInterface::class);
        $tenants = $this->createMock(TenantRepositoryInterface::class);
        $tenantDomains = $this->createMock(TenantDomainRepositoryInterface::class);
        $userTenants = $this->createMock(UserTenantRepositoryInterface::class);

        $currentUser->method('currentApplicationId')->willReturn('erp-web');
        $currentUser->method('currentTenantId')->willReturn(7);
        $currentUser->method('currentUserId')->willReturn(42);
        $currentUser->method('current')->willReturn(new CurrentUserContext(
            new GenericUser(['id' => 42, 'tenant_id' => 7]),
            '42',
            'auth-api',
            'users',
            7,
            null,
            'erp-web',
            [],
        ));
        $currentTenant->method('currentApplicationId')->willReturn(null);

        $tenants->expects(self::once())
            ->method('findById')
            ->with(888)
            ->willReturn($this->tenantRecord(888));

        $tenantDomains->expects(self::once())
            ->method('findPrimaryByTenant')
            ->with(888)
            ->willReturn(new DataRecord(['tenant_id' => 888, 'domain' => 'tenant888.example.com']));

        $userTenants->expects(self::once())
            ->method('existsForTenantAndUser')
            ->with(888, 42)
            ->willReturn(false);

        $resolver = new CurrentTenantContextResolver(
            $currentUser,
            $currentTenant,
            $tenants,
            $tenantDomains,
            $userTenants,
        );

        $request = Request::create('/api/example', 'GET', ['tenant_id' => 888]);

        $context = $resolver->resolve($request);

        self::assertInstanceOf(CurrentTenantContext::class, $context);
        self::assertFalse($resolver->hasAccess($request, $context));
    }

    public function testItRejectsConflictingTenantMetadata(): void
    {
        $currentUser = $this->createMock(CurrentUserContextAccessorInterface::class);
        $currentTenant = $this->createMock(CurrentTenantContextAccessorInterface::class);
        $tenants = $this->createMock(TenantRepositoryInterface::class);
        $tenantDomains = $this->createMock(TenantDomainRepositoryInterface::class);
        $userTenants = $this->createMock(UserTenantRepositoryInterface::class);

        $currentUser->method('currentApplicationId')->willReturn(null);
        $currentUser->method('currentTenantId')->willReturn(null);
        $currentTenant->method('currentApplicationId')->willReturn(null);

        $tenants->method('findById')
            ->willReturnMap([
                [11, $this->tenantRecord(11)],
            ]);
        $tenants->method('findByCode')
            ->willReturnMap([
                ['TENANT-22', $this->tenantRecord(22)],
            ]);
        $tenantDomains->method('findPrimaryByTenant')
            ->willReturnCallback(function (int|string $tenantId): DataRecord {
                return new DataRecord([
                    'tenant_id' => (int) $tenantId,
                    'domain' => 'tenant' . $tenantId . '.example.com',
                ]);
            });

        $resolver = new CurrentTenantContextResolver(
            $currentUser,
            $currentTenant,
            $tenants,
            $tenantDomains,
            $userTenants,
        );

        $request = Request::create('/api/example', 'GET', [
            'tenant_id' => 11,
            'tenant_code' => 'TENANT-22',
        ]);

        $this->expectException(CurrentTenantContextResolutionException::class);
        $this->expectExceptionMessage('Requested tenant metadata resolved to multiple tenants.');

        $resolver->resolve($request);
    }

    private function tenantRecord(int $tenantId): DataRecord
    {
        return new DataRecord([
            'id' => $tenantId,
            'code' => 'TENANT-' . $tenantId,
            'uuid' => sprintf('00000000-0000-0000-0000-%012d', $tenantId),
            'isolation_key' => 'iso-' . $tenantId,
            'status' => 'active',
            'is_active' => true,
        ]);
    }
}
