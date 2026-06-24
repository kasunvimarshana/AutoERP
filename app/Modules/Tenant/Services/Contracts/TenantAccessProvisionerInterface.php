<?php

declare(strict_types=1);

namespace Modules\Tenant\Services\Contracts;

interface TenantAccessProvisionerInterface
{
    /** @return array{role_id:int,permission_count:int} */
    public function provision(int $tenantId): array;

    public function isReady(int $tenantId): bool;
}
