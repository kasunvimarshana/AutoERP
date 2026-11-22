<?php

declare(strict_types=1);

/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

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
