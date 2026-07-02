<?php

declare(strict_types=1);

namespace Modules\User\Contracts;

interface TenantUserCredentialProvisionerInterface
{
    /** @return array{minimum_length:int,mixed_case:bool,numbers:bool,symbols:bool} */
    public function passwordRequirements(): array;

    public function provisionTenantUser(int $tenantId, int $userId, string $email, string $plainPassword): void;

    public function revokeTenantUser(int $tenantId, int $userId): void;
}
