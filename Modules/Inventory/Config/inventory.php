<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Inventory Module Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration settings for the Inventory module including warehouse
    | management, stock tracking, valuation methods, and reorder automation.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Code Prefixes
    |--------------------------------------------------------------------------
    |
    | Prefixes used for auto-generating codes for various inventory documents.
    |
    */
    'warehouse_code_prefix' => env('INVENTORY_WAREHOUSE_CODE_PREFIX', 'WH-'),
    'stock_movement_code_prefix' => env('INVENTORY_STOCK_MOVEMENT_CODE_PREFIX', 'SM-'),
    'stock_count_code_prefix' => env('INVENTORY_STOCK_COUNT_CODE_PREFIX', 'CNT-'),
    'transfer_code_prefix' => env('INVENTORY_TRANSFER_CODE_PREFIX', 'TRF-'),

    /*
    |--------------------------------------------------------------------------
    | Valuation Method
    |--------------------------------------------------------------------------
    |
    | Default inventory valuation method.
    | Supported: FIFO, LIFO, WeightedAverage, StandardCost
    |
    */
    'default_valuation_method' => env('INVENTORY_VALUATION_METHOD', 'FIFO'),

    /*
    |--------------------------------------------------------------------------
    | Stock Level Settings
    |--------------------------------------------------------------------------
    |
    | Settings for stock level management and reorder automation.
    |
    */
    'enable_reorder_alerts' => env('INVENTORY_ENABLE_REORDER_ALERTS', true),
    'reorder_notification_channel' => env('INVENTORY_REORDER_NOTIFICATION_CHANNEL', 'email'),
    'enable_negative_stock' => env('INVENTORY_ENABLE_NEGATIVE_STOCK', false),
    'auto_reserve_stock' => env('INVENTORY_AUTO_RESERVE_STOCK', true),

    /*
    |--------------------------------------------------------------------------
    | Stock Movement Settings
    |--------------------------------------------------------------------------
    |
    | Settings for stock movement processing and validation.
    |
    */
    'require_approval_for_adjustments' => env('INVENTORY_REQUIRE_APPROVAL_FOR_ADJUSTMENTS', true),
    'adjustment_approval_threshold' => env('INVENTORY_ADJUSTMENT_APPROVAL_THRESHOLD', 1000),
    'enable_batch_tracking' => env('INVENTORY_ENABLE_BATCH_TRACKING', true),
    'enable_serial_tracking' => env('INVENTORY_ENABLE_SERIAL_TRACKING', true),

    /*
    |--------------------------------------------------------------------------
    | Stock Count Settings
    |--------------------------------------------------------------------------
    |
    | Settings for physical stock counting and reconciliation.
    |
    */
    'count_tolerance_percent' => (float) env('INVENTORY_COUNT_TOLERANCE_PERCENT', 2.0),
    'auto_adjust_on_count' => env('INVENTORY_AUTO_ADJUST_ON_COUNT', false),
    'require_count_approval' => env('INVENTORY_REQUIRE_COUNT_APPROVAL', true),

    /*
    |--------------------------------------------------------------------------
    | Warehouse Settings
    |--------------------------------------------------------------------------
    |
    | Settings for warehouse and location management.
    |
    */
    'enable_multi_warehouse' => env('INVENTORY_ENABLE_MULTI_WAREHOUSE', true),
    'enable_bin_locations' => env('INVENTORY_ENABLE_BIN_LOCATIONS', true),
    'require_location_for_movements' => env('INVENTORY_REQUIRE_LOCATION_FOR_MOVEMENTS', true),

    /*
    |--------------------------------------------------------------------------
    | Integration Settings
    |--------------------------------------------------------------------------
    |
    | Settings for integration with other modules.
    |
    */
    'auto_receive_from_purchase' => env('INVENTORY_AUTO_RECEIVE_FROM_PURCHASE', false),
    'auto_issue_for_sales' => env('INVENTORY_AUTO_ISSUE_FOR_SALES', false),
    'sync_with_accounting' => env('INVENTORY_SYNC_WITH_ACCOUNTING', true),

    /*
    |--------------------------------------------------------------------------
    | Audit Settings
    |--------------------------------------------------------------------------
    |
    | Enable audit logging for inventory transactions.
    |
    */
    'audit_enabled' => env('INVENTORY_AUDIT_ENABLED', true),
    'audit_async' => env('INVENTORY_AUDIT_ASYNC', true),

    /*
    |--------------------------------------------------------------------------
    | Decimal Precision
    |--------------------------------------------------------------------------
    |
    | Precision for quantity calculations using BCMath.
    |
    */
    'decimal_scale' => (int) env('INVENTORY_DECIMAL_SCALE', 6),
    'display_decimals' => (int) env('INVENTORY_DISPLAY_DECIMALS', 2),
];
