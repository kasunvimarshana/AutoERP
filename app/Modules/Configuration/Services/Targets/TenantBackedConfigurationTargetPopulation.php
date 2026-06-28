<?php

declare(strict_types=1);

namespace Modules\Configuration\Services\Targets;

use Modules\Configuration\Contracts\ConfigurationTargetPopulationInterface;
use Modules\Tenant\Services\Contracts\TenantConfigurationTargetReaderInterface;

final class TenantBackedConfigurationTargetPopulation implements ConfigurationTargetPopulationInterface
{
    public function __construct(
        private readonly TenantConfigurationTargetReaderInterface $targets,
    ) {}

    public function tenantCount(): int
    {
        return $this->targets->count();
    }
}
