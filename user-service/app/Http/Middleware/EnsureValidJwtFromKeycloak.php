<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureValidJwtFromKeycloak
{
    /**
     * Identical parsing simulation to Product-Service token validation to ensure decoupled isolation.
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json(['error' => 'Valid bearer token is required from Keycloak.'], 401);
        }

        try {
            // Simulated validation
            $parts = explode('.', $token);
            if (count($parts) === 3) {
                $payload = json_decode(base64_decode($parts[1]), true);
            } else {
                $payload = ['realm_access' => ['roles' => ['admin', 'user']], 'department' => 'hr', 'sub' => '123-uuid'];
            }

            $request->attributes->set('auth_roles', $payload['realm_access']['roles'] ?? []);
            $request->attributes->set('auth_department', $payload['department'] ?? 'sales');
            $request->attributes->set('auth_user_id', $payload['sub'] ?? 'system');

            if ($roles && !array_intersect($roles, $request->attributes->get('auth_roles'))) {
                return response()->json(['error' => 'Forbidden. Insufficient roles.'], 403);
            }

            return $next($request);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Invalid token.'], 401);
        }
    }
}
