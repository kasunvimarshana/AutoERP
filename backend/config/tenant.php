<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Multi-Tenant Configuration
    |--------------------------------------------------------------------------
    */

    /*
     * Strategy used to resolve the tenant from the request.
     * Options: "header", "subdomain", "domain", "auto" (tries all)
     */
    'resolution_strategy' => env('TENANT_RESOLUTION_STRATEGY', 'auto'),

    /*
     * Header name used when strategy is "header".
     */
    'header_id'   => env('TENANT_HEADER_ID', 'X-Tenant-ID'),
    'header_slug' => env('TENANT_HEADER_SLUG', 'X-Tenant-Slug'),

    /*
     * Cache TTL in seconds for resolved tenants.
     */
    'cache_ttl' => (int) env('TENANT_CACHE_TTL', 600),

    /*
     * Runtime configuration overrides populated by TenantManager.
     * This key is used to store per-tenant runtime config at:
     *   config('tenant.runtime.<key>')
     */
    'runtime' => [],
];
