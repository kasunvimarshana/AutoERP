<?php

return [
    'guards' => [
        // Default API guard used by protected module routes.
        'api' => env('AUTH_API_GUARD', env('AUTH_GUARD', 'api')),

        // Guest-check guard(s). Supports comma-separated values.
        'guest' => env('AUTH_GUEST_GUARDS', env('AUTH_API_GUARD', env('AUTH_GUARD', 'api'))),
    ],

    'token' => [
        // Personal access token name used when none is provided explicitly.
        'name' => env('AUTH_TOKEN_NAME', 'api'),
    ],
];
