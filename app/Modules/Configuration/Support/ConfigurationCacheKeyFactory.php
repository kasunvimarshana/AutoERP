<?php

declare(strict_types=1);

namespace Modules\Configuration\Support;

use Modules\Configuration\Contracts\ConfigurationCacheKeyFactoryInterface;

final class ConfigurationCacheKeyFactory implements ConfigurationCacheKeyFactoryInterface
{
    private const CACHE_PREFIX = 'configuration';

    private const CACHE_KEY_SEPARATOR = ':';

    public function keyForConfiguration(string $key, ?int $tenantId = null): string
    {
        $scope = $tenantId === null ? 'global' : 'tenant:'.(string) $tenantId;

        return self::CACHE_PREFIX
            .self::CACHE_KEY_SEPARATOR
            .$scope
            .self::CACHE_KEY_SEPARATOR
            .$key;
    }
}
