<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Contracts\TenantServiceInterface;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves and scopes the current tenant for every request.
 *
 * Sets the resolved Tenant as a singleton in the service
 * container so that any downstream class can retrieve it
 * without repeating the resolution logic.
 */
final class TenantMiddleware
{
    public function __construct(
        private readonly TenantServiceInterface $tenantService,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $tenant = $this->tenantService->resolveFromRequest($request);

        if (!$tenant || !$tenant->is_active) {
            return response()->json(['message' => 'Tenant not found or inactive.'], 403);
        }

        // Make the resolved tenant available application-wide
        App::instance('current.tenant', $tenant);
        $request->attributes->set('tenant', $tenant);

        return $next($request);
    }
}
