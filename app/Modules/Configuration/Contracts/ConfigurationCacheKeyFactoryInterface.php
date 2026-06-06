<?php

declare(strict_types=1);

namespace Modules\Configuration\Contracts;

interface ConfigurationCacheKeyFactoryInterface
{
    public function keyForConfiguration(string $key, ?int $tenantId = null): string;
}
