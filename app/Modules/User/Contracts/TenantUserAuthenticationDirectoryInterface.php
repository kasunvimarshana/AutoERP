<?php

declare(strict_types=1);

namespace Modules\User\Contracts;

interface TenantUserAuthenticationDirectoryInterface
{
    /** @return array{id:int,tenant_id:int,first_name:string,last_name:?string,email:string,username:?string,status:string,credentials_ready:bool}|null */
    public function findTenantForLogin(int $tenantId, string $identifier): ?array;

    /** @return array{id:int,tenant_id:int,first_name:string,last_name:?string,email:string,username:?string,status:string,credentials_ready:bool}|null */
    public function findActiveTenantById(int $tenantId, int $userId): ?array;

    /** @return list<int> */
    public function defaultOrganizationUnitIds(int $tenantId, int $userId): array;

    public function canAccessOrganizationUnit(int $tenantId, int $userId, int $organizationUnitId, bool $lockForUpdate = false): bool;

    /** @return list<string> */
    public function roleNames(int $tenantId, int $userId): array;

    /** @return list<string> */
    public function permissionNames(int $tenantId, int $userId): array;
}
