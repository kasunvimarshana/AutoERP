<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\Entities\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tenant Middleware
 *
 * Resolves the current tenant from the request (header, subdomain, or route).
 * Binds tenant context to the request lifecycle.
 */
class TenantMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Resolve tenant from X-Tenant-ID header or subdomain
        $tenantId = $request->header('X-Tenant-ID')
            ?? $this->resolveFromSubdomain($request);

        if (!$tenantId) {
            return response()->json([
                'success' => false,
                'message' => 'Tenant context required. Provide X-Tenant-ID header.',
            ], 400);
        }

        $tenant = Tenant::where('id', $tenantId)
            ->where('is_active', true)
            ->first();

        if (!$tenant) {
            return response()->json([
                'success' => false,
                'message' => 'Tenant not found or inactive.',
            ], 404);
        }

        // Bind tenant to the request
        $request->attributes->set('tenant', $tenant);
        $request->attributes->set('tenant_id', $tenant->id);

        // Apply tenant-specific runtime config if provided
        if ($tenant->settings) {
            $this->applyTenantSettings($tenant->settings);
        }

        return $next($request);
    }

    private function resolveFromSubdomain(Request $request): ?string
    {
        $host = $request->getHost();
        $parts = explode('.', $host);

        if (count($parts) > 2) {
            $slug = $parts[0];
            $tenant = Tenant::where('slug', $slug)->first();
            return $tenant ? (string) $tenant->id : null;
        }

        return null;
    }

    private function applyTenantSettings(array $settings): void
    {
        // Apply runtime tenant-specific configurations
        foreach ($settings as $key => $value) {
            config([$key => $value]);
        }
    }
}
