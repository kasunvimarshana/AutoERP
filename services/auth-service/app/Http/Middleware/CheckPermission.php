<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Check Permission Middleware (ABAC)
 *
 * Enforces attribute-based access control for specific permissions.
 */
class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param string $permission
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        if (!$user->hasPermission($permission)) {
            return response()->json([
                'success' => false,
                'message' => "Forbidden. Required permission: {$permission}",
            ], 403);
        }

        return $next($request);
    }
}
