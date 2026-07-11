<?php

declare(strict_types=1);

return [
    'release' => [
        'id' => env('APP_RELEASE'),
        'commit' => env('APP_COMMIT_SHA'),
    ],
    'onboarding' => [
        'operation_lease_minutes' => (int) env('TENANT_ONBOARDING_OPERATION_LEASE_MINUTES', 15),
    ],
    'platform' => [
        'public_url' => env('PLATFORM_PUBLIC_URL'),
        'host_middleware_alias' => 'platform.host',
        'operator_middleware_alias' => 'platform.operator',
    ],
    'entitlements' => ['middleware_alias' => 'tenant.feature'],
    'infrastructure' => [
        // This application currently guarantees tenant isolation through one shared
        // database schema and mandatory tenant ownership. Dedicated databases require
        // a separate migration, connection lifecycle, and worker-reset implementation.
        'database_strategy' => env('TENANT_DATABASE_STRATEGY', 'shared_schema'),
    ],
    'pagination' => ['default_per_page' => 20, 'max_per_page' => 100],
    'resolution' => [
        'central_hosts' => array_values(array_filter(array_map(
            static fn (string $host): string => strtolower(trim($host)),
            explode(',', (string) env('TENANT_CENTRAL_HOSTS', '')),
        ))),
        'selection_headers' => [
            'id' => 'X-Tenant-Id',
            'code' => 'X-Tenant-Code',
        ],
        // Disabled by default. Explicit opt-in is still restricted to local/testing by the resolver.
        'local_fallback_enabled' => filter_var(
            env('TENANT_LOCAL_FALLBACK_ENABLED', false),
            FILTER_VALIDATE_BOOL,
        ),
        'local_fallback_tenant_code' => env('TENANT_LOCAL_FALLBACK_TENANT_CODE', env('AUTOERP_TENANT_CODE', 'AUTOERP')),
    ],
    'seeding' => [
        'tenant' => [
            'code' => env('AUTOERP_TENANT_CODE', 'AUTOERP'),
            'name' => env('AUTOERP_TENANT_NAME', 'AutoERP'),
        ],
    ],
    'event_outbox' => [
        'batch_size' => (int) env('TENANT_EVENT_OUTBOX_BATCH_SIZE', 100),
        'max_attempts' => (int) env('TENANT_EVENT_OUTBOX_MAX_ATTEMPTS', 10),
        'claim_timeout_seconds' => (int) env('TENANT_EVENT_OUTBOX_CLAIM_TIMEOUT_SECONDS', 600),
        'published_retention_days' => (int) env('TENANT_EVENT_OUTBOX_PUBLISHED_RETENTION_DAYS', 30),
    ],
    'storage_cleanup' => [
        'batch_size' => (int) env('TENANT_STORAGE_CLEANUP_BATCH_SIZE', 100),
        'max_attempts' => (int) env('TENANT_STORAGE_CLEANUP_MAX_ATTEMPTS', 10),
        'claim_timeout_seconds' => (int) env('TENANT_STORAGE_CLEANUP_CLAIM_TIMEOUT_SECONDS', 900),
    ],
    'branding' => [
        'max_logo_size_kb' => (int) env('TENANT_LOGO_MAX_SIZE_KB', 5120),
        'allowed_logo_mime_types' => ['image/jpeg', 'image/png', 'image/webp'],
    ],
    'documents' => [
        'disk' => env('TENANT_DOCUMENT_DISK', 'tenant_private'),
        'max_size_kb' => (int) env('TENANT_DOCUMENT_MAX_SIZE_KB', 10240),
        'allowed_mime_types' => ['application/pdf', 'image/jpeg', 'image/png'],
    ],
    'domains' => [
        'verification_ttl_minutes' => (int) env('TENANT_DOMAIN_VERIFICATION_TTL_MINUTES', 1440),
        'verification_txt_prefix' => '_autoerp-verification',
        'verification_value_prefix' => 'autoerp-verification=',
        'revalidation_interval_hours' => (int) env('TENANT_DOMAIN_REVALIDATION_INTERVAL_HOURS', 24),
        'verification_grace_days' => (int) env('TENANT_DOMAIN_VERIFICATION_GRACE_DAYS', 7),
        'revalidation_batch_size' => (int) env('TENANT_DOMAIN_REVALIDATION_BATCH_SIZE', 100),
        'revalidation_claim_timeout_minutes' => (int) env('TENANT_DOMAIN_REVALIDATION_CLAIM_TIMEOUT_MINUTES', 30),
        'operational_connect_timeout_seconds' => (int) env('TENANT_DOMAIN_OPERATIONAL_CONNECT_TIMEOUT_SECONDS', 5),
        'operational_timeout_seconds' => (int) env('TENANT_DOMAIN_OPERATIONAL_TIMEOUT_SECONDS', 10),
        'operational_retry_minutes' => (int) env('TENANT_DOMAIN_OPERATIONAL_RETRY_MINUTES', 15),
        'minimum_tls_validity_days' => (int) env('TENANT_DOMAIN_MINIMUM_TLS_VALIDITY_DAYS', 1),
        'probe_rate_limit_per_minute' => (int) env('TENANT_DOMAIN_PROBE_RATE_LIMIT_PER_MINUTE', 120),
        'verification_rate_limit_per_minute' => (int) env('TENANT_DOMAIN_VERIFICATION_RATE_LIMIT_PER_MINUTE', 10),
    ],
];