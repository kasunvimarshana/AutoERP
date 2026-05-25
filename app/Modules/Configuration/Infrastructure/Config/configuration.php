<?php

declare(strict_types=1);

use Modules\Configuration\Domain\Constants\ConfigurationDefaults;

return [
    'cache' => [
        'store' => env('CONFIGURATION_CACHE_STORE', ''),
        'prefix' => env('CONFIGURATION_CACHE_PREFIX', 'configuration.module'),
        'ttl_seconds' => (int) env(
            'CONFIGURATION_CACHE_TTL_SECONDS',
            ConfigurationDefaults::DEFAULT_CACHE_TTL_SECONDS,
        ),
    ],
];
