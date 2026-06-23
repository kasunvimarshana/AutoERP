<?php

declare(strict_types=1);

namespace Tests\Unit\Core;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Modules\Core\Contracts\CurrentTenantContextAccessorInterface;
use Modules\Core\Contracts\CurrentUserContextAccessorInterface;
use Modules\Core\Contracts\OrganizationUnitUserAccessCheckerInterface;
use Modules\Core\Contracts\TenantUserAccessCheckerInterface;
use Modules\Core\DTOs\CurrentTenantContext;
use Modules\Core\DTOs\CurrentUserContext;
use Modules\Core\DTOs\DataRecord;
use Modules\Core\Exceptions\CurrentOrganizationUnitContextResolutionException;
use Modules\Core\Exceptions\CurrentTenantContextResolutionException;
use Modules\OrganizationUnit\Repositories\OrganizationUnitRepositoryInterface;
use Modules\OrganizationUnit\Services\CurrentOrganizationUnitContextResolver;
use Modules\Tenant\Repositories\TenantDomainRepositoryInterface;
use Modules\Tenant\Repositories\TenantRepositoryInterface;
use Modules\Tenant\Services\CurrentTenantContextResolver;
use Modules\Core\Support\SystemClock;
use Tests\TestCase;

final class ContextResolutionTest extends TestCase
{
    public function test_tenant_resolution_does_not_fall_back_to_a_user_record_tenant_column(): void
    {
        config()->set('tenant.resolution.local_fallback_enabled', false);

        $currentUser = $this->createMock(CurrentUserContextAccessorInterface::class);
        $currentUser->method('currentApplicationId')->willReturn(null);

        $tenantDomains = $this->createMock(TenantDomainRepositoryInterface::class);
        $tenantDomains->expects(self::once())
            ->method('findByDomain')
            ->with('unknown.test')
            ->willReturn(null);

        $resolver = new CurrentTenantContextResolver(
            $currentUser,
            $this->createMock(TenantRepositoryInterface::class),
            $tenantDomains,
            $this->createMock(TenantUserAccessCheckerInterface::class),
            new SystemClock(),
        );

        $request = Request::create('https://unknown.test/api/v1/test', 'GET');
        $request->setUserResolver(static fn (): object => (object) ['tenant_id' => 99]);

        $this->expectException(CurrentTenantContextResolutionException::class);
        $resolver->resolve($request);
    }

    public function test_tenant_access_requires_an_explicit_membership(): void
    {
        $currentUser = $this->createMock(CurrentUserContextAccessorInterface::class);
        $currentUser->method('currentUserId')->willReturn(7);

        $currentUser->method('current')->willReturn($this->currentUserContext(7, 10));

        $userAccess = $this->createMock(TenantUserAccessCheckerInterface::class);
        $userAccess->expects(self::once())
            ->method('isActiveTenantUser')
            ->with(7, 10)
            ->willReturn(false);

        $resolver = new CurrentTenantContextResolver(
            $currentUser,
            $this->createMock(TenantRepositoryInterface::class),
            $this->createMock(TenantDomainRepositoryInterface::class),
            $userAccess,
            new SystemClock(),
        );

        $context = new CurrentTenantContext(
            new DataRecord(['id' => 10]),
            10,
            'TENANT',
            '2bdccf93-b5aa-4c59-b71c-bff3aa1e0eb1',
            'tenant.test',
            'active',
            null,
            'request_host',
        );

        self::assertFalse($resolver->hasAccess(Request::create('/'), $context));
    }

    public function test_organization_resolution_rejects_multiple_default_memberships(): void
    {
        $currentUser = $this->createMock(CurrentUserContextAccessorInterface::class);
        $currentUser->method('currentUserId')->willReturn(7);
        $currentUser->method('currentApplicationId')->willReturn(null);

        $currentTenant = $this->createMock(CurrentTenantContextAccessorInterface::class);
        $currentTenant->method('currentTenantId')->willReturn(10);
        $currentTenant->method('currentApplicationId')->willReturn(null);

        $userAccess = $this->createMock(OrganizationUnitUserAccessCheckerInterface::class);
        $userAccess->expects(self::once())
            ->method('defaultOrganizationUnitIds')
            ->with(7, 10)
            ->willReturn([20, 30]);

        $resolver = new CurrentOrganizationUnitContextResolver(
            $currentUser,
            $currentTenant,
            $this->createMock(OrganizationUnitRepositoryInterface::class),
            $userAccess,
        );

        $this->expectException(CurrentOrganizationUnitContextResolutionException::class);
        $resolver->resolve(Request::create('/'));
    }
    private function currentUserContext(int $userId, int $tenantId): CurrentUserContext
    {
        $user = $this->createMock(Authenticatable::class);
        $user->method('getAuthIdentifier')->willReturn($userId);

        return new CurrentUserContext(
            $user,
            $userId,
            'auth-api',
            'internal',
            'web',
            ['tenant_id' => $tenantId],
        );
    }

}
