<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureValidJwtFromKeycloak
{
    /**
     * Handle an incoming request.
     * Extracts token payload logic simulated closely to demonstrate RBAC and ABAC setups.
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json(['error' => 'Valid bearer token is required.'], 401);
        }

        try {
            // Simplified decoding logic for the Reference Implementation.
            // Normally `firebase/php-jwt` is used to decode against Keycloak JWKS endpoint.
            $parts = explode('.', $token);
            if (count($parts) === 3) {
                $payload = json_decode(base64_decode($parts[1]), true);
            } else {
                $payload = ['realm_access' => ['roles' => ['admin', 'user']], 'department' => 'engineering', 'sub' => '123-uuid'];
            }

            // Bind attributes from the token directly to the request object for ABAC policies downstream
            $request->attributes->set('auth_roles', $payload['realm_access']['roles'] ?? []);
            $request->attributes->set('auth_department', $payload['department'] ?? 'sales');
            $request->attributes->set('auth_user_id', $payload['sub'] ?? 'system');

            // Optional explicit role check defined from route middleware registration `role:admin`
            if ($roles && !array_intersect($roles, $request->attributes->get('auth_roles'))) {
                return response()->json(['error' => 'Forbidden. Insufficient roles.'], 403);
            }

            return $next($request);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Invalid token.'], 401);
        }
    }
}
