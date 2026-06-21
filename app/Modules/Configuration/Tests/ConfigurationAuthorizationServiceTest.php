<?php

declare(strict_types=1);

namespace Modules\Configuration\Tests;

use Modules\Configuration\Constants\ConfigurationPermission;
use Modules\Configuration\Constants\ConfigurationScope;
use Modules\Configuration\Services\ConfigurationAuthorizationService;
use Modules\Core\Contracts\CurrentTenantContextAccessorInterface;
use Modules\Core\Contracts\CurrentUserContextAccessorInterface;
use Modules\Core\Contracts\PermissionCheckerInterface;
use PHPUnit\Framework\TestCase;

final class ConfigurationAuthorizationServiceTest extends TestCase
{
    public function test_scope_permissions_are_not_interchangeable(): void
    {
        $service = $this->service([ConfigurationPermission::ENTRIES_MANAGE_TENANT]);

        self::assertTrue($service->canManageScopeCurrent(ConfigurationScope::TENANT));
        self::assertFalse($service->canManageScopeCurrent(ConfigurationScope::GLOBAL));
        self::assertFalse($service->canManageScopeCurrent(ConfigurationScope::ORGANIZATION_UNIT));
    }

    public function test_missing_trusted_context_denies_access(): void
    {
        $user = $this->createMock(CurrentUserContextAccessorInterface::class);
        $user->method('currentUserId')->willReturn(null);
        $tenant = $this->createMock(CurrentTenantContextAccessorInterface::class);
        $tenant->method('currentTenantId')->willReturn(10);
        $permissions = $this->createMock(PermissionCheckerInterface::class);

        $service = new ConfigurationAuthorizationService($user, $tenant, $permissions);

        self::assertFalse($service->canViewCurrent());
    }

    /** @param list<string> $allowed */
    private function service(array $allowed): ConfigurationAuthorizationService
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

        return new ConfigurationAuthorizationService($user, $tenant, $permissions);
    }
}
