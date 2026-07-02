<?php

declare(strict_types=1);

namespace Modules\User\Contracts;

interface TenantUserRegistrationInterface
{
    public function prepareProvisionedAccount(
        int $tenantId,
        int $organizationUnitId,
        int $roleId,
        string $firstName,
        ?string $lastName,
        string $email,
    ): int;

    /** @return array<string,mixed> */
    public function activateAfterCredentialSetup(int $tenantId, int $userId): array;
}
