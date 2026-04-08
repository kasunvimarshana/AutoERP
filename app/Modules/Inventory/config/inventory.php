<?php

declare(strict_types=1);

return [
    'valuation_method' => env('INVENTORY_VALUATION_METHOD', 'fifo'),

    'movement_types' => [
        'receipt',
        'issue',
        'transfer',
        'adjustment',
        'return_in',
        'return_out',
        'scrap',
    ],

    'stock_statuses' => [
        'available',
        'reserved',
        'in_transit',
        'quarantine',
        'scrapped',
    ],

    'serial_statuses' => [
        'available',
        'reserved',
        'sold',
        'scrapped',
    ],
];
