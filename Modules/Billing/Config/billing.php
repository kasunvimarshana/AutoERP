<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Billing Module Configuration
    |--------------------------------------------------------------------------
    */

    'enabled' => env('BILLING_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Code Prefixes
    |--------------------------------------------------------------------------
    */

    'subscription_code_prefix' => env('BILLING_SUBSCRIPTION_PREFIX', 'SUB-'),
    'payment_code_prefix' => env('BILLING_PAYMENT_PREFIX', 'PAY-'),

    /*
    |--------------------------------------------------------------------------
    | Default Tax Rate
    |--------------------------------------------------------------------------
    */

    'default_tax_rate' => env('BILLING_DEFAULT_TAX_RATE', 0),

    /*
    |--------------------------------------------------------------------------
    | Trial Settings
    |--------------------------------------------------------------------------
    */

    'default_trial_days' => env('BILLING_DEFAULT_TRIAL_DAYS', 14),
    'allow_trial_extension' => env('BILLING_ALLOW_TRIAL_EXTENSION', false),

    /*
    |--------------------------------------------------------------------------
    | Payment Settings
    |--------------------------------------------------------------------------
    */

    'auto_renew_enabled' => env('BILLING_AUTO_RENEW_ENABLED', true),
    'grace_period_days' => env('BILLING_GRACE_PERIOD_DAYS', 7),
    'retry_failed_payments' => env('BILLING_RETRY_FAILED_PAYMENTS', true),
    'max_payment_retries' => env('BILLING_MAX_PAYMENT_RETRIES', 3),

    /*
    |--------------------------------------------------------------------------
    | Payment Processing
    |--------------------------------------------------------------------------
    */

    'payment_enabled' => env('BILLING_PAYMENT_ENABLED', true),
    'payment_provider' => env('BILLING_PAYMENT_PROVIDER', 'stripe'),
    'currency' => env('BILLING_CURRENCY', 'USD'),

    /*
    |--------------------------------------------------------------------------
    | Payment Gateway Configuration
    |--------------------------------------------------------------------------
    */

    'stripe' => [
        'enabled' => env('BILLING_STRIPE_ENABLED', true),
        'public_key' => env('STRIPE_PUBLIC_KEY'),
        'secret_key' => env('STRIPE_SECRET_KEY'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],

    'paypal' => [
        'enabled' => env('BILLING_PAYPAL_ENABLED', false),
        'client_id' => env('PAYPAL_CLIENT_ID'),
        'client_secret' => env('PAYPAL_CLIENT_SECRET'),
        'mode' => env('PAYPAL_MODE', 'sandbox'), // sandbox or live
        'webhook_id' => env('PAYPAL_WEBHOOK_ID'),
    ],

    'razorpay' => [
        'enabled' => env('BILLING_RAZORPAY_ENABLED', false),
        'key_id' => env('RAZORPAY_KEY_ID'),
        'key_secret' => env('RAZORPAY_KEY_SECRET'),
        'webhook_secret' => env('RAZORPAY_WEBHOOK_SECRET'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Supported Payment Gateways (Legacy - kept for backward compatibility)
    |--------------------------------------------------------------------------
    */

    'payment_gateways' => [
        'stripe' => [
            'enabled' => env('BILLING_STRIPE_ENABLED', true),
            'api_key' => env('STRIPE_PUBLIC_KEY'),
            'secret_key' => env('STRIPE_SECRET_KEY'),
            'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
        ],
        'paypal' => [
            'enabled' => env('BILLING_PAYPAL_ENABLED', false),
            'client_id' => env('PAYPAL_CLIENT_ID'),
            'secret' => env('PAYPAL_CLIENT_SECRET'),
            'mode' => env('PAYPAL_MODE', 'sandbox'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Subscription Limits
    |--------------------------------------------------------------------------
    */

    'max_subscriptions_per_organization' => env('BILLING_MAX_SUBSCRIPTIONS_PER_ORG', 10),
    'allow_multiple_active_subscriptions' => env('BILLING_ALLOW_MULTIPLE_ACTIVE_SUBS', false),

    /*
    |--------------------------------------------------------------------------
    | Usage Tracking
    |--------------------------------------------------------------------------
    */

    'usage_tracking_enabled' => env('BILLING_USAGE_TRACKING_ENABLED', true),
    'usage_aggregation_interval' => env('BILLING_USAGE_AGGREGATION_INTERVAL', 'daily'), // daily, hourly, realtime
];
