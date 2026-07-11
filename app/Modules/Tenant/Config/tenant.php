<?php

declare(strict_types=1);

return [
    'release' => [
        'id' => env('APP_RELEASE'),
        'commit' => env('APP_COMMIT_SHA'),
    ],
    'onboarding' => [
        'operation_lease_minutes' => (int) env('TENANT_ONBOARDING_OPERATION_LEASE_MINUTES', 15),
    ],
    'platform' => [
        'public_url' => env('PLATFORM_PUBLIC_URL'),
        'host_middleware_alias' => 'platform.host',
        'operator_middleware_alias