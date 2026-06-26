<?php

declare(strict_types=1);

namespace Modules\Audit\Services;

use InvalidArgumentException;
use Modules\Core\Contracts\OrganizationUnitDirectoryInterface;
use Modules\Core\Contracts\TenantDirectoryInterface;

final readonly class AuditOwnershipValidator
{
    public function __construct(
        private TenantDirectoryInterface $tenants,
        private OrganizationUnitDirectoryInterface $organizationUnits,
    ) {}

    /** @return array{tenant_name:string|null,organization_unit_name:string|null} */
    public function validatePlatformTarget(?int $tenantId): array
    {
        if ($tenantId === null) {
            return ['tenant_name' => null, 'organization_unit_name' => null];
        }

        return $this->validateSystemScope($tenantId, null);
    }

    /** @return array{tenant_name:string|null,organization_unit_name:string|null} */
    public function validateSystemScope(int $tenantId, ?int $organizationUnitId): array
    {
        $tenant = $this->tenants->summary($tenantId);
        if ($tenant === null) {
            throw new InvalidArgumentException('Audit tenant scope does not exist.');
        }

        $organizationName = null;
        if ($organizationUnitId !== null) {
            $organization = $this->organizationUnits->ownershipSummary($tenantId, $organizationUnitId);
            if ($organization === null) {
                throw new InvalidArgumentException('Audit organization unit does not belong to the tenant scope.');
            }

            $organizationName = $organization['name'];
        }

        return [
            'tenant_name' => $tenant['name'],
            'organization_unit_name' => $organizationName,
        ];
    }
}
