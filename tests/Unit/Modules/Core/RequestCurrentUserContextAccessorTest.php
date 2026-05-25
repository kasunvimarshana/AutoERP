<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Core;

use Illuminate\Auth\GenericUser;
use Illuminate\Http\Request;
use LogicException;
use Modules\Core\Application\DTO\CurrentUserContext;
use Modules\Core\Infrastructure\Support\RequestCurrentUserContextAccessor;
use PHPUnit\Framework\TestCase;

final class RequestCurrentUserContextAccessorTest extends TestCase
{
    public function testItRebuildsCurrentUserContextFromRequestAttributes(): void
    {
        $request = Request::create('/api/example', 'GET');
        $request->setUserResolver(static fn (): GenericUser => new GenericUser(['id' => 55]));
        $request->attributes->set('current_user', [
            'user_id' => '55',
            'guard' => 'auth-api',
            'provider' => 'users',
            'tenant_id' => 11,
            'organization_unit_id' => 9,
            'application_id' => 'erp-web',
        ]);
        $request->attributes->set('auth_access_token', ['client_id' => 'erp-web']);

        $accessor = new RequestCurrentUserContextAccessor(
            $request,
            'current_user',
            'current_user_guard',
            'current_user_provider',
            'current_tenant_id',
            'current_organization_unit_id',
            'current_application_id',
            'auth_access_token',
        );

        $context = $accessor->current();

        self::assertInstanceOf(CurrentUserContext::class, $context);
        self::assertSame(55, $context->userIdAsInt());
        self::assertSame('auth-api', $context->guard());
        self::assertSame('users', $context->provider());
        self::assertSame(11, $context->tenantId());
        self::assertSame(9, $context->organizationUnitId());
        self::assertSame('erp-web', $context->applicationId());
        self::assertSame(['client_id' => 'erp-web'], $context->tokenPayload());
        self::assertSame(55, $accessor->currentUserId());
        self::assertSame(11, $accessor->currentTenantId());
        self::assertSame(9, $accessor->currentOrganizationUnitId());
        self::assertSame('erp-web', $accessor->currentApplicationId());
    }

    public function testItFallsBackToIndividualRequestAttributesWhenSerializedContextIsMissing(): void
    {
        $request = Request::create('/api/example', 'GET');
        $request->setUserResolver(static fn (): GenericUser => new GenericUser(['id' => 80]));
        $request->attributes->set('current_user_guard', 'web');
        $request->attributes->set('current_user_provider', 'users');
        $request->attributes->set('current_tenant_id', 13);
        $request->attributes->set('current_organization_unit_id', 17);
        $request->attributes->set('current_application_id', 'backoffice');

        $accessor = new RequestCurrentUserContextAccessor(
            $request,
            'current_user',
            'current_user_guard',
            'current_user_provider',
            'current_tenant_id',
            'current_organization_unit_id',
            'current_application_id',
            'auth_access_token',
        );

        $context = $accessor->requireCurrent();

        self::assertSame(80, $context->userIdAsInt());
        self::assertSame('web', $context->guard());
        self::assertSame('users', $context->provider());
        self::assertSame(13, $context->tenantId());
        self::assertSame(17, $context->organizationUnitId());
        self::assertSame('backoffice', $context->applicationId());
    }

    public function testItThrowsWhenCurrentUserContextIsUnavailable(): void
    {
        $request = Request::create('/api/example', 'GET');
        $request->setUserResolver(static fn (): null => null);

        $accessor = new RequestCurrentUserContextAccessor(
            $request,
            'current_user',
            'current_user_guard',
            'current_user_provider',
            'current_tenant_id',
            'current_organization_unit_id',
            'current_application_id',
            'auth_access_token',
        );

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Current user context is not available on the active request.');

        $accessor->requireCurrent();
    }
}
