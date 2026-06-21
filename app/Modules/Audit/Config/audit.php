<?php

declare(strict_types=1);

return [
    'pagination' => [
        'default_per_page' => 25,
        'max_per_page' => 100,
    ],

    'payload' => [
        'max_json_bytes' => 64 * 1024,
        'max_depth' => 8,
        'max_items_per_level' => 200,
        'max_string_length' => 4_000,
        'max_tags' => 20,
        'max_tag_length' => 100,
        'redacted_value' => '[REDACTED]',
        'sensitive_keys' => [
            'password',
            'password_confirmation',
            'password_hash',
            'passphrase',
            'secret',
            'client_secret',
            'token',
            'access_token',
            'refresh_token',
            'id_token',
            'csrf_token',
            'authorization',
            'bearer',
            'cookie',
            'session',
            'session_id',
            'api_key',
            'private_key',
            'card_number',
            'cvv',
            'cvc',
            'pin',
            'otp',
            'one_time_password',
            'recovery_code',
        ],
    ],

    'display_timezone' => env('AUDIT_DISPLAY_TIMEZONE', config('app.timezone', 'UTC')),
    'request_id_header' => 'X-Request-ID',
];
