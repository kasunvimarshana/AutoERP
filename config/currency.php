<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Default Currency
    |--------------------------------------------------------------------------
    |
    | This is the system-wide default ISO 4217 currency code. It is used when
    | no tenant-specific currency has been configured. Override this value via
    | the DEFAULT_CURRENCY environment variable without redeploying.
    |
    */

    'default' => env('DEFAULT_CURRENCY', 'LKR'),

    /*
    |--------------------------------------------------------------------------
    | Supported Currencies
    |--------------------------------------------------------------------------
    |
    | The list of ISO 4217 currency codes the platform accepts. This list is
    | used for validation when a tenant or user submits a currency value.
    | Extend this list as additional currencies are required.
    |
    */

    'supported' => [
        'LKR', // Sri Lankan Rupee (system default)
        'USD', // United States Dollar
        'EUR', // Euro
        'GBP', // British Pound Sterling
        'AUD', // Australian Dollar
        'CAD', // Canadian Dollar
        'SGD', // Singapore Dollar
        'INR', // Indian Rupee
        'JPY', // Japanese Yen
        'CNY', // Chinese Yuan
    ],

    /*
    |--------------------------------------------------------------------------
    | Decimal Precision
    |--------------------------------------------------------------------------
    |
    | Number of decimal places used in all BCMath monetary calculations.
    | This value is intentionally separate from display precision.
    |
    */

    'precision' => 4,

];
