<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Auth;

use Illuminate\Auth\GenericUser;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Modules\Auth\Infrastructure\Services\CurrentUserContextResolver;
use Modules\Core\Application\DTO\CurrentUserContext;
use Modules\User\Application\Repositories\UserTenantRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class CurrentUserContextResolverTest extends TestCase
{
    public function testItResolvesCurrentUserFromGuardDeclaredByRouteMiddleware(): void
    {
        $authFactory = $this->createMock(AuthFactory::class);
        $guard = $this->createMock(Guard::class);
        $userTenants = $this->createMock(UserTenantRepositoryInterface::class);

        $authFactory->expects(self::once())
            ->method('guard')
            ->with('auth-api')
            ->willReturn($guard);

        $guard->expects(self::once())
            ->method('user')
            ->willReturn(new GenericUser([
                'id' => 42,
                'tenant_id' => 7,
                'organization_unit_id' => 3,
            ]));

        $resolver = new CurrentUserContextResolver($authFactory, $userTenants);

        $request = Request::create('/api/auth/token', 'POST');
        $request->setRouteResolver(
            static fn (): Route => (new Route('POST', '/api/auth/token', static fn (): array => []))
                ->middleware('auth:auth-api'),
        );

        $context = $resolver->resolve($request);

        self::assertInstanceOf(CurrentUserContext::class, $context);
        self::assertSame(42, $context->userIdAsInt());
        self::assertSame(7, $context->tenantId());
        self::assertSame(3, $context->organizationUnitId());
        self::assertSame('auth-api', $context->guard());
    }

    public function testItChecksTenantAccessUsingUserTenantMappings(): void
    {
        $authFactory = $this->createMock(AuthFactory::class);
        $userTenants = $this->createMock(UserTenantRepositoryInterface::class);

        $resolver = new CurrentUserContextResolver($authFactory, $userTenants);

        $context = new CurrentUserContext(
            new GenericUser(['id' => 101]),
            '101',
            'auth-api',
            'users',
            null,
            null,
            null,
            [],
        );

        $userTenants->expects(self::once())
            ->method('existsForTenantAndUser')
            ->with(77, 101)
            ->willReturn(true);

        self::assertTrue($resolver->hasTenantAccess($context, 77));
    }
}
