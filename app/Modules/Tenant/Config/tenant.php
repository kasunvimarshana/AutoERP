<?php

declare(strict_types=1);

return [
    'pagination' => [
        'default_per_page' => 20,
        'max_per_page' => 100,
    ],
    'resolution' => [
        'required' => (bool) env('TENANT_RESOLUTION_REQUIRED', true),
        'enforce_authenticated_tenant_match' => (bool) env('TENANT_ENFORCE_AUTHENTICATED_TENANT_MATCH', true),
    ],
    'defaults' => [
        'is_isolated' => true,
    ],
];
