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
     * Usage in routes:  ->middleware('rbac:admin')
     *                or ->middleware('rbac:manager,admin')  (any one of the listed roles is sufficient)
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $claims = $request->attributes->get('jwt_claims');

        if (! $claims) {
            return response()->json([
                'error'   => 'Unauthorized',
                'message' => 'JWT claims not found. Ensure keycloak.auth middleware runs first.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        foreach ($roles as $role) {
            if ($this->keycloak->hasRole($claims, $role)) {
                return $next($request);
            }
        }

        return response()->json([
            'error'   => 'Forbidden',
            'message' => 'You do not have the required role to access this resource.',
            'required_roles' => $roles,
        ], Response::HTTP_FORBIDDEN);
    }
}
