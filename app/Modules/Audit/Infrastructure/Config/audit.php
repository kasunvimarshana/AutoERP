<?php

declare(strict_types=1);

return [
    'pagination' => [
        'default_per_page' => 20,
        'max_per_page' => 200,
    ],

    'fail_safe' => [
        'swallow_exceptions' => true,
    ],

    'events' => [
        'capture_wildcard' => false,
        'listen' => [
            'auth.lifecycle',
            'SalesOrderCreated',
            'InvoiceGenerated',
            'StockUpdated',
            'UserLoggedIn',
            'ConfigurationChanged',
        ],
        'ignore_prefixes' => [
            'Modules\\Audit\\',
        ],
    ],
];