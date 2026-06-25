<?php

declare(strict_types=1);

namespace Modules\Tenant\Services\Contracts;

interface TenantAccessProvisionerInterface
{
    /** @return array{role_id:int,permission_count:int} */
    public function provision(int $tenantId): array;

    public function catalogueIsReady(int $tenantId, bool $lockForUpdate = false): bool;

    public function superAdminRoleIsReady(int $tenantId, bool $lockForUpdate = false): bool;

    public function isReady(int $tenantId, bool $lockForUpdate = false): bool;

    public function permissionCount(int $tenantId): int;

    public function hasOperationalAdministrator(
        int $tenantId,
        int $userId,
        int $rootOrganizationUnitId,
        int $superAdminRoleId,
        bool $lockForUpdate = false,
    ): bool;
}
