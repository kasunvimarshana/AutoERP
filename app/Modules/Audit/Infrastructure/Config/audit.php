<?php

declare(strict_types=1);

return [
    'pagination' => [
        'default_per_page' => 20,
        'max_per_page' => 200,
    ],

    'fail_safe' => [
        'swallow_exceptions' => false,
    ],

    'events' => [
        'capture_wildcard' => false,
        'listen' => [
            'auth.lifecycle',
            'document.lifecycle',
            'finance.posting',
            'inventory.movement',
            'payment.lifecycle',
            'configuration.changed',
            'user.lifecycle',
        ],
        'ignore_prefixes' => [
            'Modules\\Audit\\',
        ],
    ],
];
