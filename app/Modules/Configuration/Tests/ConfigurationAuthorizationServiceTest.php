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
    public function test_tenant_scope_permissions_are_not_interchangeable(): void
    {
        $service = $this->service(
            [ConfigurationPermission::ENTRIES_MANAGE_TENANT],
            platformOperator: false,
        );

        self::assertTrue($service->canManageScopeCurrent(ConfigurationScope::TENANT));
        self::assertFalse($service->canManageScopeCurrent(ConfigurationScope::GLOBAL));
        self::assertFalse($service->canManageScopeCurrent(ConfigurationScope::ORGANIZATION_UNIT));
    }

    public function test_platform_defaults_require_operator_status_and_platform_permission(): void
    {
        $permission = ConfigurationPermission::PLATFORM_DEFAULTS_MANAGE;

        self::assertFalse($this->service([$permission], platformOperator: false)
            ->canManageScopeCurrent(ConfigurationScope::GLOBAL));
        self::assertFalse($this->service([], platformOperator: true)
            ->canManageScopeCurrent(ConfigurationScope::GLOBAL));
        self::assertTrue($this->service([$permission], platformOperator: true)
            ->canManageScopeCurrent(ConfigurationScope::GLOBAL));
    }

    public function test_sensitive_permissions_are_scope_specific(): void
    {
        $tenantService = $this->service(
            [ConfigurationPermission::ENTRIES_MANAGE_SENSITIVE],
            platformOperator: false,
        );
        $platformService = $this->service(
            [ConfigurationPermission::PLATFORM_DEFAULTS_MANAGE_SENSITIVE],
            platformOperator: true,
        );

        self::assertTrue($tenantService->canManageSensitiveCurrent(ConfigurationScope::TENANT));
        self::assertFalse($tenantService->canManageSensitiveCurrent(ConfigurationScope::GLOBAL));
        self::assertTrue($platformService->canManageSensitiveCurrent(ConfigurationScope::GLOBAL));
        self::assertFalse($platformService->canManageSensitiveCurrent(ConfigurationScope::TENANT));
    }

    public function test_missing_trusted_context_denies_access(): void
    {
        $user = $this->createMock(CurrentUserContextAccessorInterface::class);
        $user->method('currentUserId')->willReturn(null);
        $tenant = $this->createMock(CurrentTenantContextAccessorInterface::class);
        $tenant->method('currentTenantId')->willReturn(10);
        $permissions = $this->createMock(PermissionCheckerInterface::class);
        $platformOperators = $this->createMock(PlatformOperatorCheckerInterface::class);

        $service = new ConfigurationAuthorizationService(
            $user,
            $tenant,
            $permissions,
            $platformOperators,
        );

        self::assertFalse($service->canViewCurrent());
        self::assertFalse($service->canViewPlatformDefaultsCurrent());
    }

    /** @param list<string> $allowed */
    private function service(array $allowed, bool $platformOperator): ConfigurationAuthorizationService
    {
        $user = $this->createMock(CurrentUserContextAccessorInterface::class);
        $user->method('currentUserId')->willReturn(5);
        $tenant = $this->createMock(CurrentTenantContextAccessorInterface::class);
        $tenant->method('currentTenantId')->willReturn(10);
        $permissions = $this->createMock(PermissionCheckerInterface::class);
        $permissions->method('allows')->willReturnCallback(
            static fn (int $userId, int $tenantId, string $permission): bool => $userId === 5
                && $tenantId === 10
                && in_array($permission, $allowed, true),
        );
        $platformOperators = $this->createMock(PlatformOperatorCheckerInterface::class);
        $platformOperators->method('isPlatformOperator')->willReturnCallback(
            static fn (int $userId): bool => $userId === 5 && $platformOperator,
        );

        return new ConfigurationAuthorizationService(
            $user,
            $tenant,
            $permissions,
            $platformOperators,
        );
    }
}
