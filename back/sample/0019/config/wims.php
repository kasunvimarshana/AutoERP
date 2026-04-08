<?php

/**
 * WIMS — Warehouse & Inventory Management System Configuration
 *
 * All settings here represent system-wide defaults.
 * Per-tenant / per-warehouse overrides are stored in the DB
 * via `inventory_settings`, `allocation_settings`, and
 * `costing_method_assignments` tables.
 *
 * File location: config/wims.php
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Module Registration
    |--------------------------------------------------------------------------
    | List of all active WIMS modules. Disable a module by removing it here.
    */
    'modules' => [
        'Catalog'        => true,
        'UnitOfMeasure'  => true,
        'Warehouse'      => true,
        'Inventory'      => true,
        'StockMovement'  => true,
        'Procurement'    => true,
        'Sales'          => true,
        'Returns'        => true,
        'Allocation'     => true,
        'CycleCounting'  => true,
        'Valuation'      => true,
        'Audit'          => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Inventory Valuation — Default Costing Method
    |--------------------------------------------------------------------------
    | Supported: 'fifo' | 'lifo' | 'avco' | 'standard' | 'specific_identification' | 'fefo'
    |
    | This is the system-wide fallback. Override per warehouse/product in DB.
    */
    'default_costing_method' => env('WIMS_COSTING_METHOD', 'avco'),

    /*
    |--------------------------------------------------------------------------
    | Inventory Management Method
    |--------------------------------------------------------------------------
    | 'perpetual' — Stock levels updated in real-time on each transaction
    | 'periodic'  — Stock levels updated on period-end (manual count)
    */
    'inventory_management_method' => env('WIMS_INVENTORY_METHOD', 'perpetual'),

    /*
    |--------------------------------------------------------------------------
    | Stock Rotation Strategy
    |--------------------------------------------------------------------------
    | Determines lot/location selection order during picking.
    | Supported: 'fifo' | 'lifo' | 'fefo' | 'lefo' | 'fmfo' | 'sled' | 'fefo_fifo' | 'manual'
    */
    'default_rotation_strategy' => env('WIMS_ROTATION_STRATEGY', 'fifo'),

    /*
    |--------------------------------------------------------------------------
    | Allocation Algorithm
    |--------------------------------------------------------------------------
    | How available stock is assigned to orders.
    | Supported: 'standard' | 'priority' | 'fair_share' | 'manual' | 'wave' | 'zone' | 'cluster'
    */
    'default_allocation_algorithm' => env('WIMS_ALLOCATION_ALGORITHM', 'standard'),

    /*
    |--------------------------------------------------------------------------
    | Inventory Cycle Counting Method
    |--------------------------------------------------------------------------
    | Supported: 'abc' | 'periodic' | 'continuous' | 'location_based' |
    |            'zero_balance' | 'random' | 'discrepancy_triggered'
    */
    'default_cycle_count_method' => env('WIMS_CYCLE_COUNT_METHOD', 'abc'),

    /*
    |--------------------------------------------------------------------------
    | Negative Stock
    |--------------------------------------------------------------------------
    */
    'allow_negative_stock'    => env('WIMS_ALLOW_NEGATIVE_STOCK', false),
    'warn_on_negative_stock'  => env('WIMS_WARN_NEGATIVE_STOCK', true),

    /*
    |--------------------------------------------------------------------------
    | Tracking Defaults
    |--------------------------------------------------------------------------
    */
    'tracking' => [
        'serial_numbers'     => env('WIMS_TRACK_SERIALS', false),
        'batches'            => env('WIMS_TRACK_BATCHES', false),
        'lots'               => env('WIMS_TRACK_LOTS', false),
        'expiry_date'        => env('WIMS_TRACK_EXPIRY', false),
        'manufacture_date'   => env('WIMS_TRACK_MFG_DATE', false),
        'best_before_date'   => env('WIMS_TRACK_BEST_BEFORE', false),
        'lot_number_format'  => env('WIMS_LOT_FORMAT', 'LOT-{YYYY}{MM}-{SEQ:6}'),
        'serial_number_format' => env('WIMS_SERIAL_FORMAT', 'SN-{YYYY}-{SEQ:8}'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Expiry Management
    |--------------------------------------------------------------------------
    */
    'expiry' => [
        'alert_days'               => env('WIMS_EXPIRY_ALERT_DAYS', 30),
        'near_expiry_threshold'    => env('WIMS_NEAR_EXPIRY_DAYS', 30),
        'auto_quarantine_expired'  => env('WIMS_AUTO_QUARANTINE_EXPIRED', false),
        'block_expired_movement'   => env('WIMS_BLOCK_EXPIRED_MOVEMENT', true),
        'pick_margin_days'         => env('WIMS_EXPIRY_PICK_MARGIN', 0),
    ],

    /*
    |--------------------------------------------------------------------------
    | Reservation Settings
    |--------------------------------------------------------------------------
    */
    'reservation' => [
        'expiry_hours'          => env('WIMS_RESERVATION_EXPIRY_HOURS', null),
        'reserve_safety_stock'  => env('WIMS_RESERVE_SAFETY_STOCK', true),
        'allow_partial'         => env('WIMS_ALLOW_PARTIAL_ALLOCATION', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cycle Counting Settings
    |--------------------------------------------------------------------------
    */
    'cycle_counting' => [
        'blind_count'               => env('WIMS_BLIND_COUNT', true),
        'variance_threshold_pct'    => env('WIMS_VARIANCE_THRESHOLD', 2),
        'require_recount'           => env('WIMS_REQUIRE_RECOUNT', true),
        'require_approval'          => env('WIMS_COUNT_REQUIRE_APPROVAL', true),
        'abc_class_a_frequency'     => 30,    // days
        'abc_class_b_frequency'     => 90,
        'abc_class_c_frequency'     => 180,
        'abc_thresholds'            => ['A' => 80, 'B' => 95, 'C' => 100],
    ],

    /*
    |--------------------------------------------------------------------------
    | GS1 Settings
    |--------------------------------------------------------------------------
    */
    'gs1' => [
        'enabled'              => env('WIMS_GS1_ENABLED', false),
        'company_prefix'       => env('WIMS_GS1_COMPANY_PREFIX', null),
        'sscc_extension_digit' => env('WIMS_SSCC_EXTENSION', 0),
        'validate_gtin_check'  => env('WIMS_VALIDATE_GTIN', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Multi-step Operations
    |--------------------------------------------------------------------------
    | Configure receiving and shipping workflows per business type.
    */
    'operations' => [
        'receiving_steps'  => env('WIMS_RECEIVING_STEPS', 1), // 1=direct | 2=input+stock | 3=input+quality+stock
        'shipping_steps'   => env('WIMS_SHIPPING_STEPS', 1),  // 1=direct | 2=pick+ship | 3=pick+pack+ship
        'require_qc_receipt'  => env('WIMS_QC_ON_RECEIPT', false),
        'require_qc_return'   => env('WIMS_QC_ON_RETURN', false),
        'auto_putaway'        => env('WIMS_AUTO_PUTAWAY', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | UOM Settings
    |--------------------------------------------------------------------------
    */
    'uom' => [
        'default_quantity_precision' => env('WIMS_QTY_PRECISION', 6),
        'rounding_mode'              => env('WIMS_ROUNDING_MODE', 'round'),
        'allow_fractional_purchase'  => env('WIMS_FRACTIONAL_PURCHASE', true),
        'allow_fractional_sales'     => env('WIMS_FRACTIONAL_SALES', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Audit Settings
    |--------------------------------------------------------------------------
    */
    'audit' => [
        'enabled'              => env('WIMS_AUDIT_ENABLED', true),
        'retention_days'       => env('WIMS_AUDIT_RETENTION_DAYS', 3650), // 10 years
        'log_sensitive_access' => env('WIMS_LOG_ACCESS', true),
        'compliance_mode'      => env('WIMS_COMPLIANCE_MODE', false), // GxP / FDA 21 CFR Part 11
    ],

    /*
    |--------------------------------------------------------------------------
    | Industry Profiles (pre-configured bundles)
    |--------------------------------------------------------------------------
    | Setting an industry profile auto-sets sensible defaults.
    | Individual settings still override these.
    */
    'industry_profile' => env('WIMS_INDUSTRY', 'general'),
    // general | pharmacy | manufacturing | retail | wholesale | 3pl | hospital
    // | ecommerce | supermarket | rental | service_center | cold_chain | food_beverage

    'industry_profiles' => [
        'pharmacy' => [
            'costing_method'            => 'fefo',
            'rotation_strategy'         => 'fefo',
            'track_batches'             => true,
            'track_expiry_date'         => true,
            'track_manufacture_date'    => true,
            'require_qc_receipt'        => true,
            'require_qc_return'         => true,
            'compliance_mode'           => true,
            'block_expired_movement'    => true,
            'auto_quarantine_expired'   => true,
            'gs1_enabled'               => true,
            'cycle_count_method'        => 'abc',
        ],
        'cold_chain' => [
            'rotation_strategy'      => 'fefo',
            'track_batches'          => true,
            'track_expiry_date'      => true,
            'block_expired_movement' => true,
            'costing_method'         => 'fifo',
        ],
        'manufacturing' => [
            'costing_method'      => 'standard',
            'track_lots'          => true,
            'track_manufacture_date' => true,
            'receiving_steps'     => 2,
        ],
        'rental' => [
            'track_serial_numbers' => true,
            'costing_method'       => 'specific_identification',
            'allocation_algorithm' => 'standard',
        ],
        'ecommerce' => [
            'shipping_steps'       => 3, // pick + pack + ship
            'allocation_algorithm' => 'wave',
        ],
    ],

];
