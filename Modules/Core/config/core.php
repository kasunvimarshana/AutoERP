<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Core Module Configuration
    |--------------------------------------------------------------------------
    |
    | Shared infrastructure configuration used by the Core module.
    |
    */

    'version' => '1.0.0',

    /*
    |--------------------------------------------------------------------------
    | API Response
    |--------------------------------------------------------------------------
    */
    'api' => [
        'version' => 'v1',
        'prefix' => 'api/v1',
    ],

    /*
    |--------------------------------------------------------------------------
    | Decimal Precision
    |--------------------------------------------------------------------------
    |
    | BCMath scale settings — do NOT reduce these values.
    |
    */
    'decimal' => [
        'scale_standard' => 4,
        'scale_intermediate' => 8,
        'scale_monetary' => 2,
    ],
];
