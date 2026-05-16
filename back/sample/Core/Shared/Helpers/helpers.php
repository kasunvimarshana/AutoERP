<?php

declare(strict_types=1);

if (! function_exists('tenant_id')) {
    function tenant_id(): ?int
    {
        if (! app()->bound('current_tenant_id')) {
            return null;
        }

        $tenantId = app('current_tenant_id');

        return is_int($tenantId) ? $tenantId : null;
    }
}
