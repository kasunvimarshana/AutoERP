<?php

declare(strict_types=1);

namespace Modules\Core\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/** Gives every request a safe support reference shared by logs and API errors. */
final class RequestCorrelationIdMiddleware
{
    public const ATTRIBUTE = 'correlation_id';
    public const HEADER = 'X-Correlation-ID';

    public function handle(Request $request, Closure $next): Response
    {
        $correlationId = (string) Str::ulid();
        $request->attributes->set(self::ATTRIBUTE, $correlationId);

        $response = $next($request);
        $response->headers->set(self::HEADER, $correlationId);

        return $response;
    }
}
