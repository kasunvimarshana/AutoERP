<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\AuditService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

final class SuspiciousActivityDetection
{
    private const MAX_FAILED_ATTEMPTS = 10;
    private const LOCKOUT_DURATION = 900; // 15 minutes

    public function __construct(
        private readonly AuditService $auditService,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $ip = $request->ip() ?? 'unknown';
        $key = "suspicious_ip:{$ip}";
        $attempts = (int) Cache::get($key, 0);

        if ($attempts >= self::MAX_FAILED_ATTEMPTS) {
            return response()->json([
                'success' => false,
                'error'   => [
                    'code'    => 'AUTH_BLOCKED',
                    'message' => 'Too many failed attempts. Try again later.',
                ],
            ], Response::HTTP_TOO_MANY_REQUESTS);
        }

        $response = $next($request);

        if ($response->getStatusCode() === Response::HTTP_UNAUTHORIZED) {
            Cache::put($key, $attempts + 1, self::LOCKOUT_DURATION);
        }

        return $response;
    }
}
