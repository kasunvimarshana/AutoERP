<?php

declare(strict_types=1);

namespace App\Core\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Audit Log Middleware
 *
 * Logs all requests for audit trail
 */
class AuditLog
{
    /**
     * Handle an incoming request
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);

        // Log request
        Log::info('API Request', [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'user_id' => $request->user()?->id,
            'tenant_id' => tenancy()->tenant?->id,
        ]);

        $response = $next($request);

        // Log response
        $duration = microtime(true) - $startTime;
        Log::info('API Response', [
            'status' => $response->getStatusCode(),
            'duration' => round($duration * 1000, 2).'ms',
            'user_id' => $request->user()?->id,
        ]);

        return $response;
    }
}
