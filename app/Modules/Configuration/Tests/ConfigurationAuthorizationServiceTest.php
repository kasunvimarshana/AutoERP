<?php

declare(strict_types=1);

namespace Modules\Configuration\Tests;

use Modules\Configuration\Constants\ConfigurationPermission;
use Modules\Configuration\Constants\ConfigurationScope;
use Modules\Configuration\Services\ConfigurationAuthorizationService;
use Modules\Core\Contracts\CurrentTenantContextAccessorInterface;
use Modules\Core\Contracts\CurrentUserContextAccessorInterface;
use Modules\Core\Contracts\PermissionCheckerInterface;
use Modules\Core\Contracts\PlatformOperatorCheckerInterface;
use PHPUnit\Framework\TestCase;

final class ConfigurationAuthorizationServiceTest extends TestCase
{
    public function test_tenant_scope_permissions_do_not_authorize_global_or_organization_scope(): void
    {
        $service = $this->service(
            allowedTenantPermissions: [ConfigurationPermission::ENTRIES_MANAGE_TENANT],
        );

        self::assertTrue($service->canManageScopeCurrent(ConfigurationScope::TENANT));
        self::assertFalse($service->canManageScopeCurrent(ConfigurationScope::GLOBAL));
        self::assertFalse($service->canManageScopeCurrent(ConfigurationScope::ORGANIZATION_UNIT));
    }

    public function test_platform_operator_can_manage_global_but_not_tenant_scope_without_tenant_permission(): void
    {
        $service = $this->service(isPlatformOperator: true);

        self::assertTrue($service->canViewScopeCurrent(ConfigurationScope::GLOBAL));
        self::assertTrue($service->canManageScopeCurrent(ConfigurationScope::GLOBAL));
        self::assertTrue($service->canManageSensitiveCurrent(ConfigurationScope::GLOBAL));
        self::assertFalse($service->canManageScopeCurrent(ConfigurationScope::TENANT));
    }

    public function test_missing_trusted_context_denies_all_scopes(): void
    {
        $user = $this->createMock(CurrentUserContextAccessorInterface::class);
        $user->method('currentUserId')->willReturn(null);
        $tenant = $this->createMock(CurrentTenantContextAccessorInterface::class);
        $tenant->method('currentTenantId')->willReturn(null);
        $permissions = $this->createMock(PermissionCheckerInterface::class);
        $platformOperators = $this->createMock(PlatformOperatorCheckerInterface::class);

        $service = new ConfigurationAuthorizationService(
            $user,
            $tenant,
            $permissions,
            $platformOperators,
        );

        self::assertFalse($service->canViewScopeCurrent(ConfigurationScope::GLOBAL));
        self::assertFalse($service->canViewScopeCurrent(ConfigurationScope::TENANT));
    }

    /** @param list<string> $allowedTenantPermissions */
    private function service(
        array $allowedTenantPermissions = [],
        bool $isPlatformOperator = false,
    ): ConfigurationAuthorizationService {
        $user = $this->createMock(CurrentUserContextAccessorInterface::class);
        $user->method('currentUserId')->willReturn(5);
        $tenant = $this->createMock(CurrentTenantContextAccessorInterface::class);
        $tenant->method('currentTenantId')->willReturn(10);
        $permissions = $this->createMock(PermissionCheckerInterface::class);
        $permissions->method('allows')->willReturnCallback(
            static fn (int $userId, int $tenantId, string $permission): bool => $userId === 5
                && $tenantId === 10
                && in_array($permission, $allowedTenantPermissions, true),
        );
        $platformOperators = $this->createMock(PlatformOperatorCheckerInterface::class);
        $platformOperators->method('isPlatformOperator')->willReturn($isPlatformOperator);

        return new ConfigurationAuthorizationService(
            $user,
            $tenant,
            $permissions,
            $platformOperators,
        );
    }
}
