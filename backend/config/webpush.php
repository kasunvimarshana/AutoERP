<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Web Push Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for native Web Push notifications using VAPID
    |
    */

    'vapid' => [
        'subject' => env('VAPID_SUBJECT', 'mailto:admin@autoerp.com'),
        'public_key' => env('VAPID_PUBLIC_KEY'),
        'private_key' => env('VAPID_PRIVATE_KEY'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Push Notification Settings
    |--------------------------------------------------------------------------
    */

    'ttl' => env('PUSH_TTL', 2419200), // 4 weeks default
    'urgency' => env('PUSH_URGENCY', 'normal'), // very-low, low, normal, high
    'topic' => env('PUSH_TOPIC', 'general'),

    /*
    |--------------------------------------------------------------------------
    | Automatic Subscription Cleanup
    |--------------------------------------------------------------------------
    |
    | Remove expired subscriptions automatically
    |
    */

    'cleanup_expired' => env('PUSH_CLEANUP_EXPIRED', true),
    'cleanup_after_days' => env('PUSH_CLEANUP_AFTER_DAYS', 90),
];
