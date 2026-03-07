<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Infrastructure\MultiTenant\TenantResolver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware that resolves the active tenant from the request and sets it
 * on the TenantManager singleton.
 *
 * Must run before any middleware that reads tenant context.
 */
final class TenantMiddleware
{
    public function __construct(
        private readonly TenantResolver $tenantResolver
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $this->tenantResolver->resolve($request);

        return $next($request);
    }
}
