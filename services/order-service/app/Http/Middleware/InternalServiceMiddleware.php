<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Validates that requests to internal-only endpoints originate
 * from trusted services within the microservices network.
 *
 * Services must include the X-Internal-Service header with a value
 * matching the configured INTERNAL_SERVICE_SECRET environment variable.
 */
class InternalServiceMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $secret          = config('app.internal_service_secret');
        $providedSecret  = $request->header('X-Internal-Service');

        if (empty($secret) || $providedSecret !== $secret) {
            return response()->json(
                ['error' => 'Forbidden – internal endpoint only.'],
                403
            );
        }

        return $next($request);
    }
}
