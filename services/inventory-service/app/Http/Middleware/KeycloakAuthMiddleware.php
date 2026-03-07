<?php

namespace App\Http\Middleware;

use App\Services\KeycloakService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class KeycloakAuthMiddleware
{
    public function __construct(private readonly KeycloakService $keycloak) {}

    public function handle(Request $request, Closure $next): Response
    {
        $token = $this->extractToken($request);

        if (! $token) {
            return response()->json([
                'error'   => 'Unauthorized',
                'message' => 'Missing or malformed Bearer token.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        try {
            $claims = $this->keycloak->validateToken($token);
        } catch (\Throwable $e) {
            return response()->json([
                'error'   => 'Unauthorized',
                'message' => $e->getMessage(),
            ], Response::HTTP_UNAUTHORIZED);
        }

        $request->attributes->set('jwt_claims', $claims);
        $request->attributes->set('jwt_token',  $token);

        return $next($request);
    }

    private function extractToken(Request $request): ?string
    {
        $header = $request->header('Authorization', '');

        if (str_starts_with((string) $header, 'Bearer ')) {
            return substr((string) $header, 7);
        }

        return null;
    }
}
