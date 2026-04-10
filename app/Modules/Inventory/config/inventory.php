<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Valuation Method
    |--------------------------------------------------------------------------
    */
    'default_valuation_method' => env('INVENTORY_VALUATION_METHOD', 'fifo'),
    
    /*
    |--------------------------------------------------------------------------
    | Default Stock Rotation Strategy
    |--------------------------------------------------------------------------
    */
    'default_rotation_strategy' => env('INVENTORY_ROTATION_STRATEGY', 'fefo'),
    
    /*
    |--------------------------------------------------------------------------
    | Default Allocation Algorithm
    |--------------------------------------------------------------------------
    */
    'default_allocation_algorithm' => env('INVENTORY_ALLOCATION_ALGORITHM', 'nearest_expiry'),
    
    /*
    |--------------------------------------------------------------------------
    | Reservation Settings
    |--------------------------------------------------------------------------
    */
    'reservation_expiry_days' => env('INVENTORY_RESERVATION_EXPIRY_DAYS', 7),
    
    /*
    |--------------------------------------------------------------------------
    | Tracking Settings
    |--------------------------------------------------------------------------
    */
    'enable_serial_tracking' => env('INVENTORY_SERIAL_TRACKING', true),
    'enable_batch_tracking' => env('INVENTORY_BATCH_TRACKING', true),
    'enable_expiry_tracking' => env('INVENTORY_EXPIRY_TRACKING', true),
    
    /*
    |--------------------------------------------------------------------------
    | Safety Stock Settings
    |--------------------------------------------------------------------------
    */
    'safety_stock_percentage' => env('INVENTORY_SAFETY_STOCK_PERCENTAGE', 10),
    'reorder_point_days' => env('INVENTORY_REORDER_POINT_DAYS', 7),
    
    /*
    |--------------------------------------------------------------------------
    | Alert Settings
    |--------------------------------------------------------------------------
    */
    'low_stock_threshold_percentage' => env('INVENTORY_LOW_STOCK_THRESHOLD', 20),
    'expiry_alert_days' => env('INVENTORY_EXPIRY_ALERT_DAYS', 30),
];