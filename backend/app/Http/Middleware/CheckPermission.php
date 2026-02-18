<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Permission-Based Authorization Middleware
 *
 * Checks if the authenticated user has the required permission
 * to access the route. Permissions are defined in the route
 * configuration using the 'permission' key.
 *
 * Usage in routes:
 * Route::get('/products', [ProductController::class, 'index'])
 *     ->middleware('permission:products.view');
 *
 * Route::post('/products', [ProductController::class, 'store'])
 *     ->middleware('permission:products.create');
 */
class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$permissions  One or more permissions required
     */
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        if (! auth()->check()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $user = auth()->user();

        // Super admin bypass (optional - remove if you want strict permission checking)
        if (method_exists($user, 'hasRole') && $user->hasRole('super-admin')) {
            return $next($request);
        }

        // Check if user has any of the required permissions
        $hasPermission = false;
        foreach ($permissions as $permission) {
            if (method_exists($user, 'hasPermission') && $user->hasPermission($permission)) {
                $hasPermission = true;
                break;
            }
        }

        if (! $hasPermission) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to perform this action.',
                'required_permissions' => $permissions,
            ], 403);
        }

        return $next($request);
    }
}
