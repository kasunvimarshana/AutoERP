<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * RoleMiddleware
 * 
 * Checks if the authenticated user has the required role(s)
 * to access a route or perform an action.
 */
class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $roles  Role slug(s) required (comma-separated)
     * @param  string  $guard  Guard to check (default: 'all' requires all roles)
     */
    public function handle(Request $request, Closure $next, string $roles, string $guard = 'all'): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
            ], 401);
        }

        // Parse roles (comma-separated)
        $requiredRoles = explode(',', $roles);
        
        // Get user role slugs
        $userRoles = $user->roles->pluck('slug')->toArray();

        if ($guard === 'all') {
            // User must have ALL required roles
            $hasRole = empty(array_diff($requiredRoles, $userRoles));
        } else {
            // User must have AT LEAST ONE required role
            $hasRole = !empty(array_intersect($requiredRoles, $userRoles));
        }

        if (!$hasRole) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient role privileges',
                'required_roles' => $requiredRoles,
            ], 403);
        }

        return $next($request);
    }
}
