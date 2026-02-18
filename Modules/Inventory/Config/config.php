<?php

return [
    'name' => 'Inventory',

    /**
     * Default valuation method for stock
     */
    'default_valuation_method' => env('INVENTORY_VALUATION_METHOD', 'fifo'),

    /**
     * Default currency for inventory items
     */
    'default_currency' => env('INVENTORY_CURRENCY', 'USD'),

    /**
     * Enable low stock alerts
     */
    'enable_low_stock_alerts' => env('INVENTORY_LOW_STOCK_ALERTS', true),

    /**
     * Movement types
     */
    'movement_types' => [
        'receipt' => 'Stock Receipt',
        'shipment' => 'Stock Shipment',
        'transfer_in' => 'Transfer In',
        'transfer_out' => 'Transfer Out',
        'adjustment_in' => 'Adjustment In',
        'adjustment_out' => 'Adjustment Out',
        'return' => 'Return',
        'consumption' => 'Consumption',
    ],

    /**
     * Product types
     */
    'product_types' => [
        'stockable' => 'Stockable Product',
        'consumable' => 'Consumable',
        'service' => 'Service',
    ],

    /**
     * Warehouse types
     */
    'warehouse_types' => [
        'main' => 'Main Warehouse',
        'secondary' => 'Secondary Warehouse',
        'transit' => 'Transit Warehouse',
        'virtual' => 'Virtual Warehouse',
    ],
];
