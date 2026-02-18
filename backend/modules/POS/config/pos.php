<?php

return [
    /*
    |--------------------------------------------------------------------------
    | POS Module Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration options for the Point of Sale module
    |
    */

    'default_location' => env('POS_DEFAULT_LOCATION', null),

    'cash_register' => [
        'auto_open' => env('POS_AUTO_OPEN_CASH_REGISTER', false),
        'require_opening_balance' => env('POS_REQUIRE_OPENING_BALANCE', true),
    ],

    'transaction' => [
        'default_prefix' => env('POS_TRANSACTION_PREFIX', 'TXN'),
        'number_padding' => env('POS_TRANSACTION_PADDING', 6),
    ],

    'invoice' => [
        'default_scheme' => env('POS_DEFAULT_INVOICE_SCHEME', null),
        'default_layout' => env('POS_DEFAULT_INVOICE_LAYOUT', null),
    ],

    'payment' => [
        'allow_partial' => env('POS_ALLOW_PARTIAL_PAYMENT', true),
        'allow_credit' => env('POS_ALLOW_CREDIT', true),
    ],

    'stock' => [
        'auto_adjust' => env('POS_AUTO_ADJUST_STOCK', true),
        'default_accounting_method' => env('POS_ACCOUNTING_METHOD', 'fifo'),
    ],

    'barcode' => [
        'default_format' => env('POS_BARCODE_FORMAT', 'CODE128'),
    ],

    'restaurant' => [
        'enabled' => env('POS_RESTAURANT_ENABLED', false),
        'table_booking' => env('POS_TABLE_BOOKING_ENABLED', false),
    ],
];
