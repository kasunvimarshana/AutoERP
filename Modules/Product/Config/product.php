<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Product Configuration
    |--------------------------------------------------------------------------
    */

    'types' => [
        'good' => 'Physical Good',
        'service' => 'Service',
        'bundle' => 'Bundle',
        'composite' => 'Composite Product',
    ],

    /*
    |--------------------------------------------------------------------------
    | Code Generation
    |--------------------------------------------------------------------------
    */

    'code' => [
        'auto_generate' => env('PRODUCT_CODE_AUTO_GENERATE', true),
        'prefix' => env('PRODUCT_CODE_PREFIX', 'PRD'),
        'length' => env('PRODUCT_CODE_LENGTH', 8),
    ],
];
