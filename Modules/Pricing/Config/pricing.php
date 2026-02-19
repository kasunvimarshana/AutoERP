<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Pricing Configuration
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | Decimal Scale
    |--------------------------------------------------------------------------
    |
    | Number of decimal places for price calculations (BCMath precision)
    |
    */

    'decimal_scale' => env('PRICING_DECIMAL_SCALE', 6),

    /*
    |--------------------------------------------------------------------------
    | Display Decimal Places
    |--------------------------------------------------------------------------
    |
    | Number of decimal places to display in UI
    |
    */

    'display_decimals' => env('PRICING_DISPLAY_DECIMALS', 2),

    /*
    |--------------------------------------------------------------------------
    | Currency
    |--------------------------------------------------------------------------
    */

    'currency' => [
        'code' => env('CURRENCY_CODE', 'USD'),
        'symbol' => env('CURRENCY_SYMBOL', '$'),
        'position' => env('CURRENCY_POSITION', 'before'), // before or after
    ],

    /*
    |--------------------------------------------------------------------------
    | Pricing Strategies
    |--------------------------------------------------------------------------
    */

    'strategies' => [
        'flat' => \Modules\Pricing\Services\FlatPricingEngine::class,
        'percentage' => \Modules\Pricing\Services\PercentagePricingEngine::class,
        'tiered' => \Modules\Pricing\Services\TieredPricingEngine::class,
    ],
];
