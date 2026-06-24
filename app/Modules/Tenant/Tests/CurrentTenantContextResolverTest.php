<?php

declare(strict_types=1);

namespace Modules\Tenant\Tests;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Modules\Core\Contracts\CurrentUserContextAccessorInterface;
use Modules\Core\Contracts\TenantUserAccessCheckerInterface;
use Modules\Core\DTOs\CurrentUserContext;
use Modules\Core\DTOs\DataRecord;
use Modules\Core\Exceptions\CurrentTenantContextResolutionException;
use Modules\Tenant\Repositories\TenantDomainRepositoryInterface;
use Modules\Tenant\Repositories\TenantRepositoryInterface;
use Modules\Tenant\Services\CurrentTenantContextResolver;
use Modules\Core\Support\SystemClock;
use Modules\Core\Support\TenantExecutionContext;
use Modules\Tenant\Services\Hosts\PlatformHostPolicy;
use Tests\TestCase;

final class CurrentTenantContextResolverTest extends TestCase
{
    public function test_request_body_tenant_id_is_never_a_context_selector(): void
    {
        config()->set('tenant.resolution.central_hosts', ['platform.example.test']);
        config()->set('tenant.resolution.local_fallback_enabled', false);

        $resolver = $this->resolver();
        $request = Request::create(
            'https://platform.example.test/api/v1/orders',
            'POST',
            ['tenant_id' => 999],
        );

        self::assertNull($resolver->resolve($request));
    }

    public function test_selection_header_resolves_tenant_and_access_requires_membership(): void
    {
        config()->set('tenant.resolution.central_hosts', ['platform.example.test']);
        config()->set('tenant.resolution.local_fallback_enabled', false);

        $tenant = $this->activeTenant(10);
        $tenants = $this->createMock(TenantRepositoryInterface::class);
        $tenants->expects(self::once())->method('findById')->with(10)->willReturn($tenant);

        $domains = $this->createMock(TenantDomainRepositoryInterface::class);
        $domains->method('findPrimaryByTenant')->with(10)->willReturn(new DataRecord([
            'id' => 1,
            'tenant_id' => 10,
            'domain' => 'tenant.example.test',
        ]));

        $userAccess = $this->createMock(TenantUserAccessCheckerInterface::class);
        $userAccess->expects(self::once())
            ->method('isActiveTenantUser')
            ->with(7, 10)
            ->willReturn(true);

        $resolver = $this->resolver($tenants, $domains, $userAccess, 7, 10);
        $request = Request::create('https://platform.example.test/api/v1/orders');
        $request->headers->set('X-Tenant-Id', '10');

        $context = $resolver->resolve($request);

        self::assertNotNull($context);
        self::assertSame(10, $context->tenantId());
        self::assertSame('selection_header', $context->source());
        self::assertTrue($resolver->hasAccess($request, $context));
    }

    public function test_unknown_public_host_is_rejected_before_any_fallback(): void
    {
        config()->set('tenant.resolution.central_hosts', ['platform.example.test']);
        config()->set('tenant.resolution.local_fallback_enabled', false);

        $this->expectException(CurrentTenantContextResolutionException::class);
        $this->expectExceptionMessage('request host is not assigned');

        $this->resolver()->resolve(
            Request::create('https://unassigned.example.test/api/v1/orders'),
        );
    }

    public function test_localhost_uses_the_configured_local_tenant_fallback(): void
    {
        config()->set('tenant.resolution.central_hosts', []);
        config()->set('tenant.resolution.local_fallback_enabled', true);
        config()->set('tenant.resolution.local_fallback_domain', null);
        config()->set('tenant.resolution.local_fallback_tenant_code', 'AUTOERP');

        $tenant = new DataRecord([
            'id' => 10,
            'uuid' => '00000000-0000-4000-8000-000000000010',
            'code' => 'AUTOERP',
            'name' => 'AutoERP',
            'slug' => 'autoerp',
            'status' => 'active',
            'current_subscription' => [
                'status' => 'active',
                'starts_at' => '2020-01-01 00:00:00',
                'ends_at' => null,
                'trial_ends_at' => null,
            ],
        ]);
        $tenants = $this->createMock(TenantRepositoryInterface::class);
        $tenants->expects(self::once())->method('findByCode')->with('AUTOERP')->willReturn($tenant);

        $domains = $this->createMock(TenantDomainRepositoryInterface::class);
        $domains->expects(self::once())->method('findPrimaryByTenant')->with(10)->willReturn(new DataRecord([
            'id' => 1,
            'tenant_id' => 10,
            'domain' => 'localhost',
        ]));

        $context = $this->resolver($tenants, $domains)->resolve(
            Request::create('http://localhost/api/v1/auth/login', 'POST'),
        );

        self::assertNotNull($context);
        self::assertSame(10, $context->tenantId());
        self::assertSame('local_fallback', $context->source());
    }

    public function test_central_host_accepts_a_human_readable_tenant_code_header(): void
    {
        config()->set('tenant.resolution.central_hosts', ['platform.example.test']);
        config()->set('tenant.resolution.local_fallback_enabled', false);

        $tenant = $this->activeTenant(10);
        $tenants = $this->createMock(TenantRepositoryInterface::class);
        $tenants->expects(self::once())->method('findByCode')->with('TENANT-10')->willReturn($tenant);

        $domains = $this->createMock(TenantDomainRepositoryInterface::class);
        $domains->method('findPrimaryByTenant')->with(10)->willReturn(new DataRecord([
            'id' => 1,
            'tenant_id' => 10,
            'domain' => 'tenant.example.test',
        ]));

        $request = Request::create('https://platform.example.test/api/v1/auth/login', 'POST');
        $request->headers->set('X-Tenant-Code', 'TENANT-10');

        $context = $this->resolver($tenants, $domains)->resolve($request);

        self::assertNotNull($context);
        self::assertSame(10, $context->tenantId());
        self::assertSame('selection_header', $context->source());
    }

    private function resolver(
        ?TenantRepositoryInterface $tenants = null,
        ?TenantDomainRepositoryInterface $domains = null,
        ?TenantUserAccessCheckerInterface $userAccess = null,
        ?int $userId = null,
        ?int $tokenTenantId = null,
    ): CurrentTenantContextResolver {
        $currentUser = $this->createMock(CurrentUserContextAccessorInterface::class);
        $currentUser->method('currentApplicationId')->willReturn('web');
        $currentUser->method('currentUserId')->willReturn($userId);
        $currentUser->method('current')->willReturn(
            $userId === null || $tokenTenantId === null
                ? null
                : $this->currentUserContext($userId, $tokenTenantId),
        );

        return new CurrentTenantContextResolver(
            $currentUser,
            $tenants ?? $this->createMock(TenantRepositoryInterface::class),
            $domains ?? $this->createMock(TenantDomainRepositoryInterface::class),
            $userAccess ?? $this->createMock(TenantUserAccessCheckerInterface::class),
            new SystemClock(),
            new PlatformHostPolicy($this->app),
            new TenantExecutionContext(),
        );
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

    private function activeTenant(int $id): DataRecord
    {
        return new DataRecord([
            'id' => $id,
            'uuid' => '00000000-0000-4000-8000-000000000010',
            'code' => 'TENANT-10',
            'name' => 'Tenant 10',
            'slug' => 'tenant-10',
            'status' => 'active',
            'current_subscription' => [
                'status' => 'active',
                'starts_at' => '2020-01-01 00:00:00',
                'ends_at' => null,
                'trial_ends_at' => null,
            ],
        ]);
    }
}
