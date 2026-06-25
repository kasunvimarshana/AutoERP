<?php

declare(strict_types=1);

namespace Tests\Unit\Core;

use Illuminate\Http\Request;
use Modules\Core\Contracts\CurrentTenantContextAccessorInterface;
use Modules\Core\Contracts\CurrentUserContextAccessorInterface;
use Modules\Core\DTOs\CurrentTenantContext;
use Modules\Core\DTOs\DataRecord;
use Modules\Core\Exceptions\CurrentOrganizationUnitContextResolutionException;
use Modules\Core\Support\SystemClock;
use Modules\OrganizationUnit\Repositories\OrganizationUnitRepositoryInterface;
use Modules\OrganizationUnit\Services\CurrentOrganizationUnitContextResolver;
use Modules\Tenant\Repositories\TenantDomainRepositoryInterface;
use Modules\Tenant\Repositories\TenantRepositoryInterface;
use Modules\Tenant\Services\CurrentTenantContextResolver;
use Modules\Tenant\Services\TenantSubscriptionWindowPolicy;
use Modules\User\Repositories\UserTenantRepositoryInterface;
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
            $this->createMock(UserTenantRepositoryInterface::class),
            new TenantSubscriptionWindowPolicy(new SystemClock()),
        );

        $request = Request::create('https://unknown.test/api/v1/test', 'GET');
        $request->setUserResolver(static fn (): object => (object) ['tenant_id' => 99]);

        self::assertNull($resolver->resolve($request));
    }

    public function test_tenant_access_requires_an_explicit_membership(): void
    {
        $currentUser = $this->createMock(CurrentUserContextAccessorInterface::class);
        $currentUser->method('currentUserId')->willReturn(7);

        $memberships = $this->createMock(UserTenantRepositoryInterface::class);
        $memberships->expects(self::once())
            ->method('existsForTenantAndUser')
            ->with(10, 7)
            ->willReturn(false);

        $resolver = new CurrentTenantContextResolver(
            $currentUser,
            $this->createMock(TenantRepositoryInterface::class),
            $this->createMock(TenantDomainRepositoryInterface::class),
            $memberships,
            new TenantSubscriptionWindowPolicy(new SystemClock()),
        );

        $context = new CurrentTenantContext(
            new DataRecord(['id' => 10]),
            10,
            'TENANT',
            '2bdccf93-b5aa-4c59-b71c-bff3aa1e0eb1',
            'tenant-10',
            'tenant.test',
            'active',
            true,
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

        $memberships = $this->createMock(UserTenantRepositoryInterface::class);
        $memberships->expects(self::once())
            ->method('listDefaultsForTenantAndUser')
            ->with(10, 7)
            ->willReturn([
                new DataRecord(['id' => 1, 'organization_unit_id' => 20]),
                new DataRecord(['id' => 2, 'organization_unit_id' => 30]),
            ]);

        $resolver = new CurrentOrganizationUnitContextResolver(
            $currentUser,
            $currentTenant,
            $this->createMock(OrganizationUnitRepositoryInterface::class),
            $memberships,
        );

        $this->expectException(CurrentOrganizationUnitContextResolutionException::class);
        $resolver->resolve(Request::create('/'));
    }
}
