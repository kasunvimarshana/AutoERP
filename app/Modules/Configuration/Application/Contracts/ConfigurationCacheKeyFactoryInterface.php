<?php

declare(strict_types=1);

namespace Modules\Configuration\Application\Contracts;

interface ConfigurationCacheKeyFactoryInterface
{
    public function keyForConfiguration(string $key): string;
}
