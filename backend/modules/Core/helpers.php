<?php

/**
 * Core Module Helper Functions
 *
 * Global helper functions for the Core module
 */
if (! function_exists('tenant')) {
    /**
     * Get the current tenant instance
     *
     * @return \Modules\Core\Models\Tenant|null
     */
    function tenant()
    {
        if (! app()->bound(\Modules\Core\Services\TenantContext::class)) {
            return null;
        }

        return app(\Modules\Core\Services\TenantContext::class)->getTenant();
    }
}

if (! function_exists('tenant_id')) {
    /**
     * Get the current tenant ID
     *
     * @return int|string|null
     */
    function tenant_id()
    {
        $tenant = tenant();

        return $tenant ? $tenant->id : null;
    }
}

if (! function_exists('tenant_database')) {
    /**
     * Get the current tenant database name
     *
     * @return string|null
     */
    function tenant_database()
    {
        $tenant = tenant();

        return $tenant ? $tenant->database : null;
    }
}

if (! function_exists('is_multi_tenant')) {
    /**
     * Check if multi-tenancy is enabled
     */
    function is_multi_tenant(): bool
    {
        return config('tenancy.enabled', true);
    }
}
