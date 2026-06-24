<?php

declare(strict_types=1);

namespace Modules\Tenant\Services\Contracts;

interface TenantAuthenticationProvisionerInterface
{
    /**
     * @return array{
     *   provider_id:int,
     *   invitation_id:int,
     *   invitation_token:?string,
     *   invitation_expires_at:string
     * }
     */
    public function provisionInitialAdministrator(
        int $tenantId,
        int $organizationUnitId,
        int $roleId,
        string $email,
    ): array;

    public function isReady(int $tenantId): bool;
}
