<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Modules\Core\Infrastructure\Models\Tenant;
use Symfony\Component\HttpFoundation\Response;

class TenantMiddleware
{
    /**
     * Handle an incoming request.
     * Reads X-Tenant-ID header or tenant_id claim from JWT.
     * Sets TenantContext singleton and enforces isolation.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenantId = $this->resolveTenantId($request);

        if ($tenantId === null) {
            return response()->json([
                'success' => false,
                'message' => 'Tenant identifier is required.',
                'data'    => null,
                'errors'  => ['tenant' => 'Missing X-Tenant-ID header or tenant claim.'],
            ], Response::HTTP_FORBIDDEN);
        }

        $tenant = Tenant::where('id', $tenantId)
            ->where('is_active', true)
            ->first();

        if ($tenant === null) {
            return response()->json([
                'success' => false,
                'message' => 'Tenant not found or inactive.',
                'data'    => null,
                'errors'  => ['tenant' => 'The specified tenant does not exist or is inactive.'],
            ], Response::HTTP_FORBIDDEN);
        }

        App::instance('tenant.id', (int) $tenant->id);
        App::instance('tenant', $tenant);

        $request->attributes->set('tenant_id', (int) $tenant->id);
        $request->attributes->set('tenant', $tenant);

        return $next($request);
    }

    /**
     * Resolve the tenant ID from header or JWT payload.
     */
    private function resolveTenantId(Request $request): ?int
    {
        // Check X-Tenant-ID header first
        $headerValue = $request->header('X-Tenant-ID');
        if ($headerValue !== null && is_numeric($headerValue)) {
            return (int) $headerValue;
        }

        // Fall back to JWT claim if auth guard is set
        try {
            $user = auth('api')->user();
            if ($user !== null && isset($user->tenant_id)) {
                return (int) $user->tenant_id;
            }
        } catch (\Throwable) {
            // JWT not present or invalid — fall through
        }

        return null;
    }
}
