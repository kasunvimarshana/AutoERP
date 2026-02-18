<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Inventory Module Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration settings for the inventory management module.
    |
    */

    /**
     * Default cost method for inventory valuation
     *
     * Options: fifo, lifo, wac, standard, specific
     */
    'default_cost_method' => env('INVENTORY_COST_METHOD', 'fifo'),

    /**
     * Allow negative stock levels
     */
    'negative_stock_allowed' => env('INVENTORY_NEGATIVE_STOCK', false),

    /**
     * Auto-generate SKU for new products
     */
    'auto_create_sku' => env('INVENTORY_AUTO_SKU', true),

    /**
     * SKU prefix for auto-generated SKUs
     */
    'sku_prefix' => env('INVENTORY_SKU_PREFIX', 'PRD'),

    /**
     * Enable batch tracking by default
     */
    'enable_batch_tracking' => env('INVENTORY_BATCH_TRACKING', true),

    /**
     * Enable serial number tracking by default
     */
    'enable_serial_tracking' => env('INVENTORY_SERIAL_TRACKING', true),

    /**
     * Days before expiry to trigger alert
     */
    'expiry_alert_days' => env('INVENTORY_EXPIRY_ALERT_DAYS', 30),

    /**
     * Percentage below which to trigger low stock alert
     */
    'low_stock_alert_percentage' => env('INVENTORY_LOW_STOCK_PCT', 20),

    /**
     * Barcode format
     *
     * Options: code128, code39, ean13, upca, etc.
     */
    'barcode_format' => env('INVENTORY_BARCODE_FORMAT', 'code128'),

    /**
     * Enable QR codes for products
     */
    'enable_qr_codes' => env('INVENTORY_QR_CODES', true),

    /**
     * Default warehouse ID (if applicable)
     */
    'default_warehouse_id' => env('INVENTORY_DEFAULT_WAREHOUSE', null),

    /**
     * Enable multi-location tracking
     */
    'multi_location_enabled' => env('INVENTORY_MULTI_LOCATION', true),

    /**
     * Stock reservation timeout (in hours)
     */
    'reservation_timeout_hours' => env('INVENTORY_RESERVATION_TIMEOUT', 24),

    /**
     * Enable automatic reorder suggestions
     */
    'auto_reorder_suggestions' => env('INVENTORY_AUTO_REORDER', true),

    /**
     * Product image storage disk
     */
    'image_disk' => env('INVENTORY_IMAGE_DISK', 'public'),

    /**
     * Maximum product images per product
     */
    'max_images_per_product' => env('INVENTORY_MAX_IMAGES', 10),

    /**
     * Enable product variants
     */
    'enable_variants' => env('INVENTORY_VARIANTS', true),

    /**
     * Enable product bundles
     */
    'enable_bundles' => env('INVENTORY_BUNDLES', true),

    /**
     * Enable composite products
     */
    'enable_composites' => env('INVENTORY_COMPOSITES', true),
];
