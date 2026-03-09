<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

/**
 * Auth Service Middleware
 *
 * Validates bearer tokens against the Auth Service for cross-service auth.
 */
class AuthServiceMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        try {
            $response = Http::withToken($token)
                ->timeout(5)
                ->post(config('services.auth.url') . '/api/auth/validate');

            if (!$response->successful()) {
                return response()->json(['success' => false, 'message' => 'Invalid or expired token.'], 401);
            }

            $userData = $response->json('data');
            $request->attributes->set('auth_user', $userData);
            $request->attributes->set('tenant_id', $userData['tenant_id'] ?? null);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Authentication service unavailable.'], 503);
        }

        return $next($request);
    }
}
