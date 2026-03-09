<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Low Stock Threshold
    |--------------------------------------------------------------------------
    | Default quantity at or below which a product is considered low stock.
    | Individual products can override this with their `minimum_stock` value.
    */
    'low_stock_threshold' => (int) env('INVENTORY_LOW_STOCK_THRESHOLD', 10),

    /*
    |--------------------------------------------------------------------------
    | Reservation TTL (seconds)
    |--------------------------------------------------------------------------
    | How long a stock reservation is valid before it expires and the
    | quantity is returned to available stock.
    */
    'reservation_ttl' => (int) env('INVENTORY_RESERVATION_TTL', 3600),

    /*
    |--------------------------------------------------------------------------
    | Reorder Strategies
    |--------------------------------------------------------------------------
    | Defines how reorder quantities are calculated.
    |
    |   fixed_quantity   - Reorder a fixed amount (reorder_quantity field)
    |   eoq              - Economic Order Quantity formula
    |   days_of_supply   - Reorder enough stock for N days
    */
    'reorder_strategy' => env('INVENTORY_REORDER_STRATEGY', 'fixed_quantity'),

    'reorder_strategies' => [
        'fixed_quantity' => [
            'default_quantity' => 100,
        ],
        'eoq' => [
            'holding_cost_rate'  => 0.25,
            'ordering_cost'      => 50.0,
        ],
        'days_of_supply' => [
            'days' => 30,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Currency
    |--------------------------------------------------------------------------
    */
    'default_currency' => env('INVENTORY_DEFAULT_CURRENCY', 'USD'),

    /*
    |--------------------------------------------------------------------------
    | Stock Movement Types
    |--------------------------------------------------------------------------
    */
    'movement_types' => [
        'receipt'     => 'receipt',
        'issue'       => 'issue',
        'transfer_in' => 'transfer_in',
        'transfer_out'=> 'transfer_out',
        'adjustment'  => 'adjustment',
        'reservation' => 'reservation',
        'release'     => 'release',
        'commit'      => 'commit',
    ],

    /*
    |--------------------------------------------------------------------------
    | Warehouse Types
    |--------------------------------------------------------------------------
    */
    'warehouse_types' => [
        'standard'     => 'standard',
        'distribution' => 'distribution',
        'cold_storage' => 'cold_storage',
        'virtual'      => 'virtual',
        'bonded'       => 'bonded',
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache TTL (seconds)
    |--------------------------------------------------------------------------
    */
    'cache' => [
        'stock_level_ttl' => (int) env('INVENTORY_CACHE_STOCK_TTL', 300),
        'product_ttl'     => (int) env('INVENTORY_CACHE_PRODUCT_TTL', 600),
        'category_ttl'    => (int) env('INVENTORY_CACHE_CATEGORY_TTL', 1800),
        'warehouse_ttl'   => (int) env('INVENTORY_CACHE_WAREHOUSE_TTL', 1800),
    ],

    /*
    |--------------------------------------------------------------------------
    | Pagination Defaults
    |--------------------------------------------------------------------------
    */
    'pagination' => [
        'per_page'     => 25,
        'max_per_page' => 100,
    ],

];
