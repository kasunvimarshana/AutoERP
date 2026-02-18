<?php

return [
    'name' => 'Sales',
    'version' => '1.0.0',
    'description' => 'Sales and CRM module for managing customers, leads, opportunities, and sales orders',

    /*
    |--------------------------------------------------------------------------
    | Module Settings
    |--------------------------------------------------------------------------
    */
    'enabled' => true,

    /*
    |--------------------------------------------------------------------------
    | Default Settings
    |--------------------------------------------------------------------------
    */
    'defaults' => [
        'customer_number_prefix' => 'CUS-',
        'sales_order_number_prefix' => 'SO-',
        'lead_number_prefix' => 'LEAD-',
        'default_currency' => 'USD',
        'default_payment_terms' => 30,
    ],
];
