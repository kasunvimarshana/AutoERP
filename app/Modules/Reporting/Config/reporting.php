<?php

declare(strict_types=1);

return [
    'export_row_limit' => (int) env('REPORT_EXPORT_ROW_LIMIT', 5000),

    'browsershot' => [
        'node_binary' => env('BROWSERSHOT_NODE_BINARY'),
        'npm_binary' => env('BROWSERSHOT_NPM_BINARY'),
        'chrome_path' => env('BROWSERSHOT_CHROME_PATH'),
        'node_modules_path' => env('BROWSERSHOT_NODE_MODULES_PATH', base_path('node_modules')),
        'timeout' => (int) env('BROWSERSHOT_TIMEOUT', 120),
    ],

    'pdf' => [
        'margins' => [
            'top' => 24,
            'right' => 10,
            'bottom' => 18,
            'left' => 10,
        ],
    ],
];
