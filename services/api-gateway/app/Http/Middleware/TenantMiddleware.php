<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TenantMiddleware
{
    /**
     * Identify the active tenant and bind it to the request context.
     *
     * Resolution order:
     *   1. tenant_id claim embedded in the Passport JWT
     *   2. X-Tenant-ID header (service-to-service calls)
     *   3. Subdomain extracted from the Host header
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = $this->resolveFromToken($request)
            ?? $this->resolveFromHeader($request)
            ?? $this->resolveFromSubdomain($request);

        if ($tenant === null) {
            return response()->json([
                'message' => 'Tenant context could not be determined.',
            ], 400);
        }

        if (! $tenant->is_active) {
            return response()->json([
                'message' => 'Tenant is disabled.',
            ], 403);
        }

        // Bind tenant to the request so controllers and policies can read it.
        $request->attributes->set('tenant', $tenant);
        $request->attributes->set('tenant_id', $tenant->id);

        // Ensure the authenticated user (if any) belongs to this tenant.
        if ($user = $request->user()) {
            if ((int) $user->tenant_id !== (int) $tenant->id) {
                return response()->json([
                    'message' => 'User does not belong to the resolved tenant.',
                ], 403);
            }
        }

        return $next($request);
    }

    // -------------------------------------------------------------------------
    // Resolution strategies
    // -------------------------------------------------------------------------

    /**
     * Extract tenant_id from the authenticated Passport token claims.
     */
    private function resolveFromToken(Request $request): ?Tenant
    {
        $user = $request->user();

        if ($user === null) {
            return null;
        }

        $tenantId = $user->tenant_id;

        if (! $tenantId) {
            return null;
        }

        return Tenant::active()->find($tenantId);
    }

    /**
     * Read tenant_id from the X-Tenant-ID request header.
     * Useful for machine-to-machine calls that don't carry a user token.
     */
    private function resolveFromHeader(Request $request): ?Tenant
    {
        $tenantId = $request->header('X-Tenant-ID');

        if (! $tenantId) {
            return null;
        }

        return Tenant::active()->find((int) $tenantId);
    }

    /**
     * Derive the tenant from the request's subdomain (e.g. acme.api.example.com).
     */
    private function resolveFromSubdomain(Request $request): ?Tenant
    {
        $host = $request->getHost();

        // Strip port if present.
        $host = explode(':', $host)[0];

        // Only attempt subdomain resolution when there are at least 3 segments.
        $parts = explode('.', $host);
        if (count($parts) < 3) {
            return null;
        }

        // The subdomain is the first segment; build the full domain string that
        // matches how tenants store their domain in the database.
        $subdomain = $parts[0];
        $baseDomain = implode('.', array_slice($parts, 1));

        return Tenant::active()
            ->where(function ($q) use ($subdomain, $baseDomain) {
                $q->where('domain', "{$subdomain}.{$baseDomain}")
                  ->orWhere('domain', $subdomain);
            })
            ->first();
    }
}
