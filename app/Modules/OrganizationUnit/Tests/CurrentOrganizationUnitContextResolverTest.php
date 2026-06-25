<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Tests;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Modules\Core\Contracts\CurrentTenantContextAccessorInterface;
use Modules\Core\Contracts\CurrentUserContextAccessorInterface;
use Modules\Core\Contracts\OrganizationUnitUserAccessCheckerInterface;
use Modules\Core\DTOs\CurrentUserContext;
use Modules\Core\DTOs\DataRecord;
use Modules\Core\Exceptions\CurrentOrganizationUnitContextResolutionException;
use Modules\OrganizationUnit\Repositories\OrganizationUnitRepositoryInterface;
use Modules\OrganizationUnit\Services\CurrentOrganizationUnitContextResolver;
use PHPUnit\Framework\TestCase;

final class CurrentOrganizationUnitContextResolverTest extends TestCase
{
    public function test_authenticated_token_scope_wins_and_request_payload_is_not_a_scope_source(): void
    {
        $repository = $this->createMock(OrganizationUnitRepositoryInterface::class);
        $repository->expects(self::once())
            ->method('findById')
            ->with(20)
            ->willReturn($this->organizationUnit(20));

        $resolver = $this->resolver($repository, ['organization_unit_id' => 20], [30]);
        $request = Request::create('/example', 'POST', ['organization_unit_id' => 99]);
        $request->headers->set('X-Organization-Unit-Id', '88');

        $context = $resolver->resolve($request);

        self::assertNotNull($context);
        self::assertSame(20, $context->organizationUnitId());
        self::assertSame('authenticated_session', $context->source());
    }

    public function test_single_default_membership_is_used_when_the_token_is_unscoped(): void
    {
        $repository = $this->createMock(OrganizationUnitRepositoryInterface::class);
        $repository->expects(self::once())
            ->method('findById')
            ->with(30)
            ->willReturn($this->organizationUnit(30));

        $context = $this->resolver($repository, [], [30])->resolve(Request::create('/example'));

        self::assertNotNull($context);
        self::assertSame(30, $context->organizationUnitId());
        self::assertSame('default_membership', $context->source());
    }

    public function test_multiple_default_memberships_fail_closed(): void
    {
        $repository = $this->createMock(OrganizationUnitRepositoryInterface::class);

        $this->expectException(CurrentOrganizationUnitContextResolutionException::class);
        $this->expectExceptionMessage('Multiple default');

        $this->resolver($repository, [], [30, 40])->resolve(Request::create('/example'));
    }

    private function resolver(
        OrganizationUnitRepositoryInterface $repository,
        array $tokenPayload,
        array $defaultIds,
    ): CurrentOrganizationUnitContextResolver {
        $authenticatable = $this->createMock(Authenticatable::class);
        $authenticatable->method('getAuthIdentifier')->willReturn(5);
        $userContext = new CurrentUserContext(
            user: $authenticatable,
            userId: 5,
            guard: 'auth-api',
            provider: 'users',
            applicationId: 'workspace',
            tokenPayload: $tokenPayload,
        );

        $currentUser = $this->createMock(CurrentUserContextAccessorInterface::class);
        $currentUser->method('current')->willReturn($userContext);

        $currentTenant = $this->createMock(CurrentTenantContextAccessorInterface::class);
        $currentTenant->method('currentTenantId')->willReturn(10);

        $access = $this->createMock(OrganizationUnitUserAccessCheckerInterface::class);
        $access->method('defaultOrganizationUnitIds')->willReturn($defaultIds);
        $access->method('canAccessOrganizationUnit')->willReturn(true);

        return new CurrentOrganizationUnitContextResolver(
            $currentUser,
            $currentTenant,
            $repository,
            $access,
        );
    }

    private function organizationUnit(int $id): DataRecord
    {
        return new DataRecord([
            'id' => $id,
            'tenant_id' => 10,
            'code' => 'OU-'.$id,
            'path' => '/ou-'.$id,
            'name' => 'Organization '.$id,
            'is_active' => true,
            'retired_at' => null,
        ]);
    }
}
