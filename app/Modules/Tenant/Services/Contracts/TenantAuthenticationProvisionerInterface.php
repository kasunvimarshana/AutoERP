<?php

declare(strict_types=1);

namespace Modules\Tenant\Services\Contracts;

interface TenantAuthenticationProvisionerInterface
{
    /** @return array{provider_id:int} */
    public function provisionProvider(int $tenantId): array;

    /** @return array{user_id:int,email:string,status:string} */
    public function provisionInitialAdministratorAccount(
        int $tenantId,
        int $organizationUnitId,
        int $roleId,
        string $firstName,
        ?string $lastName,
        string $email,
        string $password,
    ): array;

    public function providerIsReady(int $tenantId, bool $lockForUpdate = false): bool;
}
