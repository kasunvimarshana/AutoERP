<?php

declare(strict_types=1);

namespace Modules\Tenant\Services\Configuration;

use Modules\Configuration\Contracts\ConfigurationTargetPopulationInterface;
use Modules\Core\Contracts\TenantExecutionContextInterface;
use Modules\Tenant\Models\TenantModel;

final class TenantConfigurationTargetPopulation implements ConfigurationTargetPopulationInterface
{
    public function __construct(
        private readonly TenantModel $tenants,
        private readonly TenantExecutionContextInterface $executionContext,
    ) {}

    public function tenantCount(): int
    {
        return $this->executionContext->runAsControlPlane(
            fn (): int => $this->tenants->newQuery()->count(),
        );
    }
}
