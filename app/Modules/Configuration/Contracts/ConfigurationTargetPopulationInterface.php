<?php

declare(strict_types=1);

namespace Modules\Configuration\Contracts;

interface ConfigurationTargetPopulationInterface
{
    public function tenantCount(): int;
}
