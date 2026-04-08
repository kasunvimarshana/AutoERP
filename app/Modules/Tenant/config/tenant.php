<?php

return [
    'tenant_service' => [
        'url' => env('TENANT_SERVICE_URL', 'http://tenant-service'),
    ],
    'tenant_config_cache_ttl' => env('TENANT_CONFIG_CACHE_TTL', 300), // seconds
];
