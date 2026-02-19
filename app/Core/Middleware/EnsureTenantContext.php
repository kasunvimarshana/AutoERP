<?php

declare(strict_types=1);

namespace App\Core\Middleware;

use App\Core\Exceptions\TenantException;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ensure Tenant Context Middleware
 *
 * Ensures a tenant context is set for the request
 */
class EnsureTenantContext
{
    /**
     * Handle an incoming request
     *
     * @throws TenantException
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! tenancy()->initialized) {
            throw TenantException::missingContext();
        }

        return $next($request);
    }
}
