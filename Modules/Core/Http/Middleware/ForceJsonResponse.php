<?php

declare(strict_types=1);

namespace Modules\Core\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Force JSON Response Middleware
 *
 * Ensures all API requests return JSON responses, even for errors.
 * This is particularly useful for API endpoints to provide consistent
 * response format regardless of Accept header.
 *
 * Usage: Apply to API route groups
 * Route::middleware(['api', 'force.json'])->group(...)
 */
class ForceJsonResponse
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $request->headers->set('Accept', 'application/json');

        return $next($request);
    }
}
