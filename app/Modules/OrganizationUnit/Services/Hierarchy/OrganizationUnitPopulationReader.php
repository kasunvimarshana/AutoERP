<?php

declare(strict_types=1);

namespace Modules\OrganizationUnit\Services\Hierarchy;

use Modules\Core\Contracts\TenantExecutionContextInterface;
use Modules\OrganizationUnit\Contracts\OrganizationUnitPopulationReaderInterface;
use Modules\OrganizationUnit\Models\OrganizationUnitModel;

final class OrganizationUnitPopulationReader implements OrganizationUnitPopulationReaderInterface
{
    public function __construct(
        private readonly OrganizationUnitModel $organizationUnits,
        private readonly TenantExecutionContextInterface $executionContext,
    ) {}

    public function activeCount(): int
    {
        return $this->executionContext->runAsControlPlane(
            fn (): int => $this->organizationUnits->newQuery()
                ->where('is_active', true)
                ->whereNull('retired_at')
                ->count(),
        );
    }
}
