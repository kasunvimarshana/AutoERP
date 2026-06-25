<?php

declare(strict_types=1);

namespace Modules\Tenant\Services\Contracts;

interface TenantOrganizationProvisionerInterface
{
    /** @return array{organization_unit_id:int} */
    public function provision(int $tenantId, string $tenantCode, string $tenantName): array;

    public function isReady(int $tenantId, int $organizationUnitId, bool $lockForUpdate = false): bool;

    public function protectedRootId(int $tenantId, bool $lockForUpdate = false): ?int;
}
