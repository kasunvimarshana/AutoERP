<?php

declare(strict_types=1);

namespace Tests\Unit\User;

use Illuminate\Container\Container;
use Illuminate\Http\Request;
use Modules\Core\Contracts\CurrentTenantContextAccessorInterface;
use Modules\Core\Contracts\CurrentUserContextAccessorInterface;
use Modules\Core\Contracts\PermissionCheckerInterface;
use Modules\Core\Http\Responses\ApiErrorResponseFactory;
use Modules\User\Http\Middleware\RequireTenantPermissionMiddleware;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

final class RequireTenantPermissionMiddlewareTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Container::setInstance(new Container());
    }

    protected function tearDown(): void
    {
        Container::setInstance(null);
        parent::tearDown();
    }

    public function test_it_allows_a_user_with_the_required_tenant_permission(): void
    {
        $user = $this->createMock(CurrentUserContextAccessorInterface::class);
        $tenant = $this->createMock(CurrentTenantContextAccessorInterface::class);
        $permissions = $this->createMock(PermissionCheckerInterface::class);

        $user->method('currentUserId')->willReturn(7);
        $tenant->method('currentTenantId')->willReturn(11);
        $permissions->expects(self::once())
            ->method('allows')
            ->with(7, 11, 'finance.accounts.view')
            ->willReturn(true);

        $response = $this->middleware($user, $tenant, $permissions)->handle(
            Request::create('/api/v1/finance/accounts', 'GET'),
            static fn (): Response => new Response('', Response::HTTP_NO_CONTENT),
            'finance.accounts.view',
        );

        self::assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
    }

    public function test_it_fails_closed_when_context_or_permission_is_missing(): void
    {
        $user = $this->createMock(CurrentUserContextAccessorInterface::class);
        $tenant = $this->createMock(CurrentTenantContextAccessorInterface::class);
        $permissions = $this->createMock(PermissionCheckerInterface::class);

        $user->method('currentUserId')->willReturn(7);
        $tenant->method('currentTenantId')->willReturn(11);
        $permissions->expects(self::once())
            ->method('allows')
            ->with(7, 11, 'finance.journals.post')
            ->willReturn(false);

        $response = $this->middleware($user, $tenant, $permissions)->handle(
            Request::create('/api/v1/finance/journals/1/post', 'POST'),
            static fn (): Response => new Response('', Response::HTTP_NO_CONTENT),
            'finance.journals.post',
        );
        $payload = json_decode((string) $response->getContent(), true, flags: JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        self::assertSame('TENANT_PERMISSION_REQUIRED', $payload['error']['code']);
        self::assertSame('finance.journals.post', $payload['error']['details']['permission']);
    }

    private function middleware(
        CurrentUserContextAccessorInterface&MockObject $user,
        CurrentTenantContextAccessorInterface&MockObject $tenant,
        PermissionCheckerInterface&MockObject $permissions,
    ): RequireTenantPermissionMiddleware {
        return new RequireTenantPermissionMiddleware(
            $user,
            $tenant,
            $permissions,
            new ApiErrorResponseFactory(),
        );
    }
}
