<?php

declare(strict_types=1);

namespace Modules\Audit\Tests;

use Illuminate\Auth\Access\AuthorizationException;
use Modules\Audit\Constants\AuditPermission;
use Modules\Audit\Services\AuditAuthorizationService;
use Modules\Core\Contracts\CurrentOrganizationUnitContextAccessorInterface;
use Modules\Core\Contracts\CurrentTenantContextAccessorInterface;
use Modules\Core\Contracts\CurrentUserContextAccessorInterface;
use Modules\Core\Contracts\PermissionCheckerInterface;
use PHPUnit\Framework\TestCase;

final class AuditAuthorizationServiceTest extends TestCase
{
    public function test_it_resolves_an_organization_scoped_reader_without_tenant_wide_permission(): void
    {
        $service = $this->service(
            organizationUnitId: 7,
            allowedPermissions: [AuditPermission::LOGS_VIEW],
        );

        $scope = $service->resolveReadScope();

        self::assertSame(10, $scope->tenantId);
        self::assertSame(7, $scope->organizationUnitId);
        self::assertFalse($scope->tenantWide);
    }

    public function test_tenant_wide_permission_allows_reading_without_an_organization_context(): void
    {
        $service = $this->service(
            organizationUnitId: null,
            allowedPermissions: [AuditPermission::LOGS_VIEW, AuditPermission::LOGS_VIEW_TENANT],
        );

        $scope = $service->resolveReadScope();

        self::assertSame(10, $scope->tenantId);
        self::assertNull($scope->organizationUnitId);
        self::assertTrue($scope->tenantWide);
    }

    public function test_an_organization_context_is_required_without_tenant_wide_permission(): void
    {
        $service = $this->service(
            organizationUnitId: null,
            allowedPermissions: [AuditPermission::LOGS_VIEW],
        );

        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('Tenant-wide audit permission');

        $service->resolveReadScope();
    }

    public function test_sensitive_details_require_the_base_and_sensitive_permissions(): void
    {
        self::assertFalse($this->service(7, [AuditPermission::LOGS_VIEW_SENSITIVE])->canViewSensitiveCurrent());
        self::assertTrue($this->service(7, [
            AuditPermission::LOGS_VIEW,
            AuditPermission::LOGS_VIEW_SENSITIVE,
        ])->canViewSensitiveCurrent());
    }

    /** @param list<string> $allowedPermissions */
    private function service(?int $organizationUnitId, array $allowedPermissions): AuditAuthorizationService
    {
        $user = $this->createMock(CurrentUserContextAccessorInterface::class);
        $user->method('currentUserId')->willReturn(5);

        $tenant = $this->createMock(CurrentTenantContextAccessorInterface::class);
        $tenant->method('currentTenantId')->willReturn(10);

        $organization = $this->createMock(CurrentOrganizationUnitContextAccessorInterface::class);
        $organization->method('currentOrganizationUnitId')->willReturn($organizationUnitId);

        $permissions = $this->createMock(PermissionCheckerInterface::class);
        $permissions->method('allows')->willReturnCallback(
            static fn (int $userId, int $tenantId, string $permission): bool => $userId === 5
                && $tenantId === 10
                && in_array($permission, $allowedPermissions, true),
        );

        return new AuditAuthorizationService($user, $tenant, $organization, $permissions);
    }
}
