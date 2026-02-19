<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Pricing Engine Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains all configuration for the extensible pricing engine
    | system. Configure pricing strategies, calculation precision, currency
    | settings, and location-based pricing.
    |
    */

    'enabled' => env('PRICING_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Decimal Precision
    |--------------------------------------------------------------------------
    |
    | All financial calculations use BCMath for precision-safe arithmetic.
    | Configure the scale (decimal places) for calculations and display.
    |
    */

    'decimal_scale' => env('PRICING_DECIMAL_SCALE', 6),
    'display_decimals' => env('PRICING_DISPLAY_DECIMALS', 2),

    /*
    |--------------------------------------------------------------------------
    | Currency Configuration
    |--------------------------------------------------------------------------
    |
    | Default currency settings for the application. Multi-currency support
    | will be added as a future module.
    |
    */

    'currency' => [
        'code' => env('CURRENCY_CODE', 'USD'),
        'symbol' => env('CURRENCY_SYMBOL', '$'),
        'position' => env('CURRENCY_POSITION', 'before'), // before or after
        'decimal_separator' => env('CURRENCY_DECIMAL_SEP', '.'),
        'thousands_separator' => env('CURRENCY_THOUSANDS_SEP', ','),
    ],

    /*
    |--------------------------------------------------------------------------
    | Pricing Strategies
    |--------------------------------------------------------------------------
    |
    | Register all available pricing engines. Each engine implements a
    | specific pricing strategy. Engines can be added/removed at runtime.
    |
    */

    'strategies' => [
        'flat' => [
            'enabled' => env('PRICING_FLAT_ENABLED', true),
            'engine' => \Modules\Pricing\Services\Engines\FlatPricingEngine::class,
        ],
        'percentage' => [
            'enabled' => env('PRICING_PERCENTAGE_ENABLED', true),
            'engine' => \Modules\Pricing\Services\Engines\PercentagePricingEngine::class,
        ],
        'tiered' => [
            'enabled' => env('PRICING_TIERED_ENABLED', true),
            'engine' => \Modules\Pricing\Services\Engines\TieredPricingEngine::class,
        ],
        'volume' => [
            'enabled' => env('PRICING_VOLUME_ENABLED', true),
            'engine' => \Modules\Pricing\Services\Engines\VolumePricingEngine::class,
        ],
        'time_based' => [
            'enabled' => env('PRICING_TIME_BASED_ENABLED', true),
            'engine' => \Modules\Pricing\Services\Engines\TimeBasedPricingEngine::class,
        ],
        'rule_based' => [
            'enabled' => env('PRICING_RULE_BASED_ENABLED', true),
            'engine' => \Modules\Pricing\Services\Engines\RuleBasedPricingEngine::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Location-Based Pricing
    |--------------------------------------------------------------------------
    |
    | Enable different prices per location/region. Locations can be
    | warehouses, stores, regions, or any custom location type.
    |
    */

    'location_based' => [
        'enabled' => env('PRICING_LOCATION_BASED', true),
        'fallback_to_default' => env('PRICING_LOCATION_FALLBACK', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Time-Based Pricing
    |--------------------------------------------------------------------------
    |
    | Enable pricing that varies based on date ranges. Useful for
    | seasonal pricing, promotions, and time-limited offers.
    |
    */

    'time_based' => [
        'enabled' => env('PRICING_TIME_BASED', true),
        'overlap_strategy' => env('PRICING_TIME_OVERLAP', 'latest'), // latest, highest, lowest
    ],

    /*
    |--------------------------------------------------------------------------
    | Caching
    |--------------------------------------------------------------------------
    |
    | Cache calculated prices to improve performance. Prices are cached
    | with a key that includes product, quantity, location, and strategy.
    |
    */

    'cache' => [
        'enabled' => env('PRICING_CACHE_ENABLED', true),
        'ttl' => env('PRICING_CACHE_TTL', 3600), // 1 hour
        'prefix' => 'pricing',
    ],

    /*
    |--------------------------------------------------------------------------
    | Validation Rules
    |--------------------------------------------------------------------------
    |
    | Validation constraints for pricing data.
    |
    */

    'validation' => [
        'min_price' => env('PRICING_MIN_PRICE', '0.00'),
        'max_price' => env('PRICING_MAX_PRICE', null),
        'require_cost' => env('PRICING_REQUIRE_COST', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Events
    |--------------------------------------------------------------------------
    |
    | Enable or disable event firing for pricing events.
    |
    */

    'events' => [
        'enabled' => env('PRICING_EVENTS_ENABLED', true),
    ],
];
