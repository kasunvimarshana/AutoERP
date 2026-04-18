<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Services;

use Illuminate\Support\Facades\Auth;
use Modules\Auth\Application\Contracts\TenantContextResolverInterface;

class TenantContextResolver implements TenantContextResolverInterface
{
    public function resolveTenantId(): ?int
    {
        $authenticatedTenantId = Auth::user()?->tenant_id;
        if (is_numeric($authenticatedTenantId) && (int) $authenticatedTenantId > 0) {
            return (int) $authenticatedTenantId;
        }

        $headerTenantId = request()?->header('X-Tenant-ID');
        if (is_numeric($headerTenantId) && (int) $headerTenantId > 0) {
            return (int) $headerTenantId;
        }

        $payloadTenantId = request()?->input('tenant_id');
        if (is_numeric($payloadTenantId) && (int) $payloadTenantId > 0) {
            return (int) $payloadTenantId;
        }

        return null;
    }
}
