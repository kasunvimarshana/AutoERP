<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * Role-Based Access Control Middleware.
 *
 * Usage in routes: ->middleware('rbac:products.write')
 *         or multiple roles: ->middleware('rbac:products.write,admin')
 */
class RBACMiddleware
{
    public function handle(Request $request, Closure $next, string ...$requiredRoles): SymfonyResponse
    {
        $userRoles = $request->attributes->get('jwt_roles', []);

        foreach ($requiredRoles as $role) {
            if (in_array($role, $userRoles, true)) {
                return $next($request);
            }
        }

        return response()->json([
            'status'  => 'error',
            'message' => 'Insufficient permissions. Required role(s): '.implode(', ', $requiredRoles),
        ], Response::HTTP_FORBIDDEN);
    }
}
