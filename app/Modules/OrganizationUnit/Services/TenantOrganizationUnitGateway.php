<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Services;

use Modules\Core\DTOs\DataRecord;
use Modules\OrganizationUnit\Repositories\OrganizationUnitRepositoryInterface;
use Modules\OrganizationUnit\Services\OrganizationUnits\OrganizationUnitService;
use Modules\Tenant\Services\Contracts\TenantOrganizationUnitGatewayInterface;

final class TenantOrganizationUnitGateway implements TenantOrganizationUnitGatewayInterface
{
    public function __construct(
        private readonly OrganizationUnitService $organizationUnits,
        private readonly OrganizationUnitRepositoryInterface $repository,
    ) {}

    public function provisionRoot(int $tenantId, array $payload): DataRecord
    {
        return $this->organizationUnits->provisionRootForTenant($tenantId, $payload);
    }

    public function findRoot(int $tenantId): ?DataRecord
    {
        return $this->repository->findRootByTenant($tenantId);
    }
}
