<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Audit Logging Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains all configuration for the comprehensive audit
    | logging system. Configure what events are logged, retention policies,
    | and storage settings.
    |
    */

    'enabled' => env('AUDIT_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Audit Events
    |--------------------------------------------------------------------------
    |
    | Configure which events should trigger audit logging. By default,
    | all create, update, and delete events are logged.
    |
    */

    'events' => [
        'created' => env('AUDIT_LOG_CREATED', true),
        'updated' => env('AUDIT_LOG_UPDATED', true),
        'deleted' => env('AUDIT_LOG_DELETED', true),
        'restored' => env('AUDIT_LOG_RESTORED', true),
        'force_deleted' => env('AUDIT_LOG_FORCE_DELETED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Auditable Models
    |--------------------------------------------------------------------------
    |
    | Configure which models should be audited. By default, all models
    | using the Auditable trait are logged. This can be restricted to
    | specific models if needed.
    |
    */

    'models' => [
        'all' => env('AUDIT_ALL_MODELS', true),
        'whitelist' => [],
        'blacklist' => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Audit Data Capture
    |--------------------------------------------------------------------------
    |
    | Configure what data is captured in audit logs.
    |
    */

    'capture' => [
        'old_values' => env('AUDIT_CAPTURE_OLD_VALUES', true),
        'new_values' => env('AUDIT_CAPTURE_NEW_VALUES', true),
        'ip_address' => env('AUDIT_CAPTURE_IP', true),
        'user_agent' => env('AUDIT_CAPTURE_USER_AGENT', true),
        'url' => env('AUDIT_CAPTURE_URL', true),
        'metadata' => env('AUDIT_CAPTURE_METADATA', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | User Attribution
    |--------------------------------------------------------------------------
    |
    | Track which user performed each action. Requires authentication.
    |
    */

    'user_attribution' => [
        'enabled' => env('AUDIT_USER_ATTRIBUTION', true),
        'anonymous_user_id' => env('AUDIT_ANONYMOUS_USER_ID', null),
        'system_user_id' => env('AUDIT_SYSTEM_USER_ID', null),
    ],

    /*
    |--------------------------------------------------------------------------
    | Organization Context
    |--------------------------------------------------------------------------
    |
    | Track which organization context the action was performed in.
    | Requires multi-tenancy module.
    |
    */

    'organization_context' => [
        'enabled' => env('AUDIT_ORG_CONTEXT', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Retention Policy
    |--------------------------------------------------------------------------
    |
    | Configure how long audit logs are retained. Old logs can be
    | automatically pruned based on age or count.
    |
    */

    'retention' => [
        'enabled' => env('AUDIT_RETENTION_ENABLED', false),
        'days' => env('AUDIT_RETENTION_DAYS', 365), // 1 year
        'max_records' => env('AUDIT_RETENTION_MAX_RECORDS', null),
        'archive_before_delete' => env('AUDIT_ARCHIVE_BEFORE_DELETE', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance
    |--------------------------------------------------------------------------
    |
    | Configure performance-related settings for audit logging.
    |
    */

    'performance' => [
        'async' => env('AUDIT_ASYNC', true), // Queue audit logging
        'queue' => env('AUDIT_QUEUE', 'default'),
        'batch_size' => env('AUDIT_BATCH_SIZE', 100),
    ],

    /*
    |--------------------------------------------------------------------------
    | Storage
    |--------------------------------------------------------------------------
    |
    | Configure where audit logs are stored. By default, they are stored
    | in the database, but they can also be sent to external services.
    |
    */

    'storage' => [
        'driver' => env('AUDIT_STORAGE_DRIVER', 'database'), // database, file, syslog
        'connection' => env('AUDIT_DB_CONNECTION', null),
        'table' => env('AUDIT_TABLE', 'audit_logs'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Security
    |--------------------------------------------------------------------------
    |
    | Security settings for audit logs.
    |
    */

    'security' => [
        'encrypt_sensitive_data' => env('AUDIT_ENCRYPT_SENSITIVE', false),
        'hash_pii' => env('AUDIT_HASH_PII', false),
        'redact_fields' => env('AUDIT_REDACT_FIELDS', []),
    ],

    /*
    |--------------------------------------------------------------------------
    | Events
    |--------------------------------------------------------------------------
    |
    | Enable or disable event firing for audit events.
    |
    */

    'audit_events' => [
        'enabled' => env('AUDIT_EVENTS_ENABLED', true),
    ],
];
