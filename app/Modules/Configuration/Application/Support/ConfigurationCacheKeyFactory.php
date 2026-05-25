<?php

declare(strict_types=1);

namespace Modules\Configuration\Application\Support;

use Modules\Configuration\Application\Contracts\ConfigurationCacheKeyFactoryInterface;

final class ConfigurationCacheKeyFactory implements ConfigurationCacheKeyFactoryInterface
{
    private const CACHE_PREFIX = 'configuration';

    private const CACHE_KEY_SEPARATOR = ':';

    public function keyForConfiguration(string $key): string
    {
        return self::CACHE_PREFIX . self::CACHE_KEY_SEPARATOR . $key;
    }
}
