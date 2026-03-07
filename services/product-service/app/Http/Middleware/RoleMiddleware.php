<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Enforce that the authenticated user holds at least one of the required roles.
     *
     * Usage in routes:  ->middleware('role:admin,manager')
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $userRoles = $request->attributes->get('roles', []);

        if (empty($userRoles)) {
            return response()->json([
                'message' => 'Forbidden: No roles assigned to this token.',
            ], 403);
        }

        foreach ($roles as $required) {
            if (in_array($required, $userRoles, true)) {
                return $next($request);
            }
        }

        return response()->json([
            'message' => 'Forbidden: Insufficient permissions.',
            'required_roles' => $roles,
            'user_roles'     => $userRoles,
        ], 403);
    }
}
