<?php

return [
    /*
    |--------------------------------------------------------------------------
    | CRM Module Configuration
    |--------------------------------------------------------------------------
    */

    'enabled' => env('MODULE_CRM_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Customer Configuration
    |--------------------------------------------------------------------------
    */

    'customer' => [
        'code_prefix' => env('CRM_CUSTOMER_CODE_PREFIX', 'CUST-'),
        'default_type' => env('CRM_CUSTOMER_DEFAULT_TYPE', 'individual'),
        'default_status' => env('CRM_CUSTOMER_DEFAULT_STATUS', 'active'),
        'default_credit_limit' => env('CRM_CUSTOMER_DEFAULT_CREDIT_LIMIT', 0),
        'default_payment_terms' => env('CRM_CUSTOMER_DEFAULT_PAYMENT_TERMS', 30), // days
    ],

    /*
    |--------------------------------------------------------------------------
    | Lead Configuration
    |--------------------------------------------------------------------------
    */

    'lead' => [
        'default_status' => env('CRM_LEAD_DEFAULT_STATUS', 'new'),
        'auto_assign' => env('CRM_LEAD_AUTO_ASSIGN', false),
        'conversion_statuses' => ['qualified', 'won'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Opportunity Configuration
    |--------------------------------------------------------------------------
    */

    'opportunity' => [
        'code_prefix' => env('CRM_OPPORTUNITY_CODE_PREFIX', 'OPP-'),
        'default_probability' => env('CRM_OPPORTUNITY_DEFAULT_PROBABILITY', 10),
        'auto_probability_update' => env('CRM_OPPORTUNITY_AUTO_PROBABILITY', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Pipeline Configuration
    |--------------------------------------------------------------------------
    */

    'pipeline' => [
        'stages' => [
            'prospecting',
            'qualification',
            'needs_analysis',
            'proposal',
            'negotiation',
            'closed_won',
            'closed_lost',
        ],
    ],
];
