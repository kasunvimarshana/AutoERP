<?php

declare(strict_types=1);

namespace App\Core\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Check Permission Middleware
 * 
 * Verifies user has required permission (RBAC)
 */
class CheckPermission
{
    /**
     * Handle an incoming request
     *
     * @param Request $request
     * @param Closure $next
     * @param string $permission
     * @return Response
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        if (!$request->user()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
            ], Response::HTTP_UNAUTHORIZED);
        }

        if (!$request->user()->can($permission)) {
            return response()->json([
                'success' => false,
                'message' => "You do not have permission: {$permission}",
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
