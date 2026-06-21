<?php

declare(strict_types=1);

namespace Modules\Configuration\Contracts;

use Modules\Configuration\Data\ResolvedConfigurationValue;

interface ConfigurationResolverInterface
{
    public function resolve(string $key, int $tenantId, ?int $organizationUnitId = null): ResolvedConfigurationValue;
    public function value(string $key, int $tenantId, ?int $organizationUnitId = null): mixed;
}
