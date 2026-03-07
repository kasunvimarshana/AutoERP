<?php

namespace App\Http\Middleware;

use App\Services\KeycloakService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RBACMiddleware
{
    public function __construct(private readonly KeycloakService $keycloak) {}

    /**
     * Usage: middleware('rbac:manager,admin,super-admin')
     * Passes if the authenticated user holds ANY of the listed roles.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $claims = $request->attributes->get('jwt_claims');

        if (! $claims) {
            return response()->json([
                'error'   => 'Unauthorized',
                'message' => 'No JWT claims found. Ensure keycloak.auth middleware runs first.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        foreach ($roles as $role) {
            if ($this->keycloak->hasRole($claims, trim($role))) {
                return $next($request);
            }
        }

        return response()->json([
            'error'   => 'Forbidden',
            'message' => 'You do not have the required role to perform this action.',
        ], Response::HTTP_FORBIDDEN);
    }
}
