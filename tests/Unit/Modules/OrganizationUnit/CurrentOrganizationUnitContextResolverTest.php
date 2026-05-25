<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\OrganizationUnit;

use Illuminate\Auth\GenericUser;
use Illuminate\Http\Request;
use Modules\Core\Application\Contracts\CurrentOrganizationUnitContextAccessorInterface;
use Modules\Core\Application\Contracts\CurrentTenantContextAccessorInterface;
use Modules\Core\Application\Contracts\CurrentUserContextAccessorInterface;
use Modules\Core\Application\DTO\CurrentOrganizationUnitContext;
use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\Exceptions\CurrentOrganizationUnitContextResolutionException;
use Modules\OrganizationUnit\Application\Repositories\OrganizationUnitRepositoryInterface;
use Modules\OrganizationUnit\Infrastructure\Services\CurrentOrganizationUnitContextResolver;
use Modules\User\Application\Repositories\UserTenantRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class CurrentOrganizationUnitContextResolverTest extends TestCase
{
    public function testItResolvesFromAuthenticatedOrganizationUnitContext(): void
    {
        $currentUser = $this->createMock(CurrentUserContextAccessorInterface::class);
        $currentTenant = $this->createMock(CurrentTenantContextAccessorInterface::class);
        $currentOrganizationUnit = $this->createMock(CurrentOrganizationUnitContextAccessorInterface::class);
        $organizationUnits = $this->createMock(OrganizationUnitRepositoryInterface::class);
        $userTenants = $this->createMock(UserTenantRepositoryInterface::class);

        $currentTenant->method('currentTenantId')->willReturn(7);
        $currentTenant->method('currentApplicationId')->willReturn('erp-web');
        $currentUser->method('currentOrganizationUnitId')->willReturn(3);
        $currentOrganizationUnit->method('currentApplicationId')->willReturn(null);
        $currentUser->method('currentApplicationId')->willReturn(null);

        $organizationUnits->expects(self::once())
            ->method('findById')
            ->with(3)
            ->willReturn($this->organizationUnitRecord(3, 7, 'HO'));

        $resolver = new CurrentOrganizationUnitContextResolver(
            $currentUser,
            $currentTenant,
            $currentOrganizationUnit,
            $organizationUnits,
            $userTenants,
        );

        $request = Request::create('/api/example', 'GET');

        $context = $resolver->resolve($request);

        self::assertInstanceOf(CurrentOrganizationUnitContext::class, $context);
        self::assertSame(3, $context->organizationUnitId());
        self::assertSame(7, $context->tenantId());
        self::assertSame('HO', $context->code());
        self::assertSame('erp-web', $context->applicationId());
        self::assertSame('authenticated_user', $context->source());
    }

    public function testItResolvesExplicitOrganizationUnitCodeFromRequestMetadata(): void
    {
        $currentUser = $this->createMock(CurrentUserContextAccessorInterface::class);
        $currentTenant = $this->createMock(CurrentTenantContextAccessorInterface::class);
        $currentOrganizationUnit = $this->createMock(CurrentOrganizationUnitContextAccessorInterface::class);
        $organizationUnits = $this->createMock(OrganizationUnitRepositoryInterface::class);
        $userTenants = $this->createMock(UserTenantRepositoryInterface::class);

        $currentTenant->method('currentTenantId')->willReturn(7);
        $currentTenant->method('currentApplicationId')->willReturn('erp-web');
        $currentUser->method('currentOrganizationUnitId')->willReturn(3);
        $currentUser->method('currentUserId')->willReturn(42);
        $currentOrganizationUnit->method('currentApplicationId')->willReturn(null);
        $currentUser->method('currentApplicationId')->willReturn(null);

        $organizationUnits->expects(self::once())
            ->method('findByTenantAndCode')
            ->with(7, 'BRANCH-88')
            ->willReturn($this->organizationUnitRecord(88, 7, 'BRANCH-88'));

        $userTenants->expects(self::once())
            ->method('existsForTenantUserAndOrganizationUnit')
            ->with(7, 42, 88)
            ->willReturn(true);

        $resolver = new CurrentOrganizationUnitContextResolver(
            $currentUser,
            $currentTenant,
            $currentOrganizationUnit,
            $organizationUnits,
            $userTenants,
        );

        $request = Request::create('/api/example', 'GET', ['organization_unit_code' => 'BRANCH-88']);
        $request->setUserResolver(static fn (): GenericUser => new GenericUser(['id' => 42]));

        $context = $resolver->resolve($request);

        self::assertInstanceOf(CurrentOrganizationUnitContext::class, $context);
        self::assertSame(88, $context->organizationUnitId());
        self::assertTrue($resolver->hasAccess($request, $context));
    }

    public function testItRejectsWhenResolvedOrganizationUnitBelongsToAnotherTenant(): void
    {
        $currentUser = $this->createMock(CurrentUserContextAccessorInterface::class);
        $currentTenant = $this->createMock(CurrentTenantContextAccessorInterface::class);
        $currentOrganizationUnit = $this->createMock(CurrentOrganizationUnitContextAccessorInterface::class);
        $organizationUnits = $this->createMock(OrganizationUnitRepositoryInterface::class);
        $userTenants = $this->createMock(UserTenantRepositoryInterface::class);

        $currentTenant->method('currentTenantId')->willReturn(7);
        $currentTenant->method('currentApplicationId')->willReturn(null);
        $currentUser->method('currentOrganizationUnitId')->willReturn(3);
        $currentUser->method('currentApplicationId')->willReturn(null);
        $currentOrganizationUnit->method('currentApplicationId')->willReturn(null);

        $organizationUnits->expects(self::once())
            ->method('findById')
            ->with(3)
            ->willReturn($this->organizationUnitRecord(3, 999, 'HO'));

        $resolver = new CurrentOrganizationUnitContextResolver(
            $currentUser,
            $currentTenant,
            $currentOrganizationUnit,
            $organizationUnits,
            $userTenants,
        );

        $request = Request::create('/api/example', 'GET');

        $this->expectException(CurrentOrganizationUnitContextResolutionException::class);
        $this->expectExceptionMessage('Resolved organization unit does not belong to the active tenant.');

        $resolver->resolve($request);
    }

    private function organizationUnitRecord(int $organizationUnitId, int $tenantId, string $code): DataRecord
    {
        return new DataRecord([
            'id' => $organizationUnitId,
            'tenant_id' => $tenantId,
            'name' => 'Organization Unit ' . $organizationUnitId,
            'code' => $code,
            'path' => '/root/' . strtolower($code),
            'is_active' => true,
        ]);
    }
}
