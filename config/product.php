<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Product Module Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains all configuration for the product catalog system.
    | Configure product types, categories, units, and code generation.
    |
    */

    'enabled' => env('PRODUCT_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Product Types
    |--------------------------------------------------------------------------
    |
    | Supported product types in the system. Each type has different
    | behavior and validation rules.
    |
    */

    'types' => [
        'good' => [
            'enabled' => env('PRODUCT_TYPE_GOOD_ENABLED', true),
            'requires_inventory' => true,
            'requires_unit' => true,
        ],
        'service' => [
            'enabled' => env('PRODUCT_TYPE_SERVICE_ENABLED', true),
            'requires_inventory' => false,
            'requires_unit' => false,
        ],
        'bundle' => [
            'enabled' => env('PRODUCT_TYPE_BUNDLE_ENABLED', true),
            'requires_inventory' => false,
            'requires_unit' => true,
            'min_items' => env('PRODUCT_BUNDLE_MIN_ITEMS', 2),
        ],
        'composite' => [
            'enabled' => env('PRODUCT_TYPE_COMPOSITE_ENABLED', true),
            'requires_inventory' => false,
            'requires_unit' => true,
            'min_items' => env('PRODUCT_COMPOSITE_MIN_ITEMS', 2),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Product Code Generation
    |--------------------------------------------------------------------------
    |
    | Configure automatic code generation for products. When enabled,
    | product codes are automatically generated using the specified
    | prefix and length.
    |
    */

    'code_generation' => [
        'enabled' => env('PRODUCT_CODE_AUTO_GENERATE', true),
        'prefix' => env('PRODUCT_CODE_PREFIX', 'PRD'),
        'length' => env('PRODUCT_CODE_LENGTH', 8),
        'separator' => env('PRODUCT_CODE_SEPARATOR', '-'),
        'strategy' => env('PRODUCT_CODE_STRATEGY', 'sequential'), // sequential, random, uuid
    ],

    /*
    |--------------------------------------------------------------------------
    | Category Configuration
    |--------------------------------------------------------------------------
    |
    | Configure hierarchical category system for product organization.
    |
    */

    'categories' => [
        'enabled' => env('PRODUCT_CATEGORIES_ENABLED', true),
        'max_depth' => env('PRODUCT_CATEGORY_MAX_DEPTH', 5),
        'require_category' => env('PRODUCT_REQUIRE_CATEGORY', false),
        'code_generation' => [
            'enabled' => env('CATEGORY_CODE_AUTO_GENERATE', true),
            'prefix' => env('CATEGORY_CODE_PREFIX', 'CAT'),
            'length' => env('CATEGORY_CODE_LENGTH', 6),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Unit System
    |--------------------------------------------------------------------------
    |
    | Configure measurement units and unit conversions. Products can have
    | different buying and selling units with automatic conversions.
    |
    */

    'units' => [
        'enabled' => env('PRODUCT_UNITS_ENABLED', true),
        'allow_conversion' => env('PRODUCT_UNIT_CONVERSION_ENABLED', true),
        'default_unit' => env('PRODUCT_DEFAULT_UNIT', 'piece'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Validation Rules
    |--------------------------------------------------------------------------
    |
    | Validation constraints for product data.
    |
    */

    'validation' => [
        'require_description' => env('PRODUCT_REQUIRE_DESCRIPTION', false),
        'require_image' => env('PRODUCT_REQUIRE_IMAGE', false),
        'max_name_length' => env('PRODUCT_MAX_NAME_LENGTH', 255),
        'max_description_length' => env('PRODUCT_MAX_DESC_LENGTH', 65535),
    ],

    /*
    |--------------------------------------------------------------------------
    | Inventory Integration
    |--------------------------------------------------------------------------
    |
    | Enable integration with inventory module (when available).
    |
    */

    'inventory' => [
        'enabled' => env('PRODUCT_INVENTORY_ENABLED', false), // Future module
        'track_stock' => env('PRODUCT_TRACK_STOCK', false),
        'allow_negative_stock' => env('PRODUCT_ALLOW_NEGATIVE_STOCK', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Events
    |--------------------------------------------------------------------------
    |
    | Enable or disable event firing for product events.
    |
    */

    'events' => [
        'enabled' => env('PRODUCT_EVENTS_ENABLED', true),
    ],
];
