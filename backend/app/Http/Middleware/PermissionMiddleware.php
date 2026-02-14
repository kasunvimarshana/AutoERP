<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * PermissionMiddleware
 * 
 * Checks if the authenticated user has the required permission(s)
 * to access a route or perform an action.
 */
class PermissionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string|array  $permissions  Permission slug(s) required
     * @param  string  $guard  Guard to check (default: 'all' requires all permissions)
     */
    public function handle(Request $request, Closure $next, string $permissions, string $guard = 'all'): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
            ], 401);
        }

        // Parse permissions (comma-separated)
        $requiredPermissions = explode(',', $permissions);
        
        // Get user permissions through roles
        $userPermissions = $user->roles()
            ->with('permissions')
            ->get()
            ->pluck('permissions')
            ->flatten()
            ->pluck('slug')
            ->unique()
            ->toArray();

        if ($guard === 'all') {
            // User must have ALL required permissions
            $hasPermission = empty(array_diff($requiredPermissions, $userPermissions));
        } else {
            // User must have AT LEAST ONE required permission
            $hasPermission = !empty(array_intersect($requiredPermissions, $userPermissions));
        }

        if (!$hasPermission) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient permissions',
                'required_permissions' => $requiredPermissions,
            ], 403);
        }

        return $next($request);
    }
}
