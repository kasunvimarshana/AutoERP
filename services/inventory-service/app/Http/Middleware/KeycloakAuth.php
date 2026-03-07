<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\KeycloakService;
use Symfony\Component\HttpFoundation\Response;

class KeycloakAuth
{
    public function __construct(private KeycloakService $keycloakService) {}

    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json(['message' => 'No token provided.'], 401);
        }

        $payload = $this->keycloakService->validateToken($token);

        if (!$payload) {
            return response()->json(['message' => 'Invalid or expired token.'], 401);
        }

        if (!empty($roles)) {
            $userRoles = $this->keycloakService->extractRoles($payload);
            if (empty(array_intersect($roles, $userRoles))) {
                return response()->json(['message' => 'Insufficient permissions.'], 403);
            }
        }

        $request->attributes->set('keycloak_payload', $payload);
        $request->attributes->set('user_id', $payload['sub'] ?? null);
        $request->attributes->set('user_roles', $this->keycloakService->extractRoles($payload));

        return $next($request);
    }
}
